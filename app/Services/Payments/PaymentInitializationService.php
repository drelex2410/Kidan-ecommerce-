<?php

namespace App\Services\Payments;

use App\Http\Controllers\Payment\AuthorizenetPaymentController;
use App\Http\Controllers\Payment\FlutterwavePaymentController;
use App\Http\Controllers\Payment\IyzicoPaymentController;
use App\Http\Controllers\Payment\MercadopagoPaymentController;
use App\Http\Controllers\Payment\MyfatoorahPaymentController;
use App\Http\Controllers\Payment\PayfastPaymentController;
use App\Http\Controllers\Payment\PayherePaymentController;
use App\Http\Controllers\Payment\PaypalPaymentController;
use App\Http\Controllers\Payment\PaystackPaymentController;
use App\Http\Controllers\Payment\PaytmPaymentController;
use App\Http\Controllers\Payment\PhonepePaymentController;
use App\Http\Controllers\Payment\RazorpayPaymentController;
use App\Http\Controllers\Payment\SSLCommerzPaymentController;
use App\Http\Controllers\Payment\StripePaymentController;
use App\Models\CombinedOrder;
use App\Models\Currency;
use App\Models\ManualPaymentMethod;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PaymentInitializationService
{
    public function __construct(
        private readonly PaymentGatewayManager $gatewayManager,
        private readonly PaymentCallbackService $callbackService,
    ) {
    }

    public function initializeApi(string $gateway, Request $request): array
    {
        $user = $request->user('api');
        $payment = $this->createPayment($gateway, $request, $user);

        if ($this->gatewayManager->isOffline($gateway)) {
            return $this->callbackService->markOfflinePending($payment, $request->input('transactionId'), $request->file('receipt'));
        }

        return [
            'success' => true,
            'go_to_payment' => true,
            'payment_method' => $gateway,
            'payment_type' => $payment->payment_type,
            'order_code' => $payment->order_code,
            'grand_total' => (float) $payment->amount,
            'redirect_url' => url("/payment/{$gateway}/pay"),
            'message' => 'Payment initialization is ready.',
        ];
    }

    public function initializeWeb(string $gateway, Request $request): mixed
    {
        $user = $this->resolveWebUser($request);
        $payment = $this->createPayment($gateway, $request, $user);

        if ($this->gatewayManager->isOffline($gateway)) {
            $this->callbackService->markOfflinePending($payment, $request->input('transactionId'), $request->file('receipt'));

            return redirect($this->buildRedirectUrl($payment, 'success', $gateway));
        }

        $this->storeLegacySession($payment, $request);

        return $this->dispatchGatewayInitializer($gateway, $request);
    }

    private function createPayment(string $gateway, Request $request, User $user): Payment
    {
        $this->gatewayManager->assertEnabled($gateway);

        $paymentType = (string) $request->input('payment_type');
        $paymentMethod = (string) $request->input('payment_method', $gateway);
        $redirectTo = (string) $request->input('redirect_to', '/');
        $amount = (float) $request->input('amount', 0);
        $orderCode = $request->input('order_code');
        $combinedOrder = null;

        if (in_array($paymentType, ['cart_payment', 'repayment'], true)) {
            $combinedOrder = CombinedOrder::query()->with('orders')->where('code', $orderCode)->first();

            if (!$combinedOrder) {
                throw new HttpException(404, 'Order not found.');
            }

            if ((int) $combinedOrder->user_id !== (int) $user->id) {
                throw new HttpException(403, 'You are not allowed to pay for this order.');
            }

            $allPaid = $combinedOrder->orders->isNotEmpty()
                && $combinedOrder->orders->every(fn ($order) => $order->payment_status === 'paid');

            if ($allPaid) {
                throw new HttpException(422, 'This order has already been paid.');
            }

            $amount = (float) $combinedOrder->grand_total;
        } elseif ($paymentType === 'wallet_payment') {
            $requestedUserId = (int) $request->input('user_id', $user->id);

            if ($requestedUserId !== (int) $user->id) {
                throw new HttpException(403, 'You are not allowed to recharge this wallet.');
            }

            if ($amount <= 0) {
                throw new HttpException(422, 'Recharge amount must be greater than zero.');
            }
        } else {
            throw new HttpException(422, 'Unsupported payment type.');
        }

        return DB::transaction(function () use ($gateway, $paymentType, $paymentMethod, $redirectTo, $amount, $orderCode, $combinedOrder, $user, $request) {
            return Payment::query()->create([
                'user_id' => $user->id,
                'combined_order_id' => $combinedOrder?->id,
                'gateway' => $gateway,
                'payment_type' => $paymentType,
                'payment_method' => $paymentMethod,
                'order_code' => $orderCode,
                'amount' => round($amount, 2),
                'currency' => optional(Currency::find(get_setting('system_default_currency')))->code,
                'status' => $this->gatewayManager->isOffline($gateway) ? 'pending' : 'initiated',
                'redirect_to' => $redirectTo,
                'meta' => Arr::except($request->all(), ['card_number', 'cvv', 'receipt']),
            ]);
        });
    }

    private function resolveWebUser(Request $request): User
    {
        $postedUserId = (int) $request->input('user_id');

        if ($postedUserId > 0) {
            $user = User::query()->find($postedUserId);

            if ($user) {
                return $user;
            }
        }

        $authUser = Auth::user();

        if ($authUser instanceof User) {
            return $authUser;
        }

        throw new HttpException(401, 'Authentication is required to initialize payment.');
    }

    private function storeLegacySession(Payment $payment, Request $request): void
    {
        session()->put('payment_id', $payment->id);
        session()->put('redirect_to', $payment->redirect_to);
        session()->put('amount', $payment->amount);
        session()->put('payment_method', $payment->payment_method);
        session()->put('payment_type', $payment->payment_type);
        session()->put('user_id', $payment->user_id);
        session()->put('order_code', $payment->order_code);
        session()->put('transactionId', $request->input('transactionId'));
        session()->put('receipt', $request->file('receipt'));
        session()->put('receiptFile', null);
        session()->put('card_number', $request->input('card_number'));
        session()->put('cvv', $request->input('cvv'));
        session()->put('expiration_month', $request->input('expiration_month'));
        session()->put('expiration_year', $request->input('expiration_year'));

        if ($request->hasFile('receipt') && $payment->payment_type === 'seller_package_payment') {
            session()->put('receiptFile', $request->file('receipt')->store('uploads/offline_payments'));
        }

        if ($this->gatewayManager->isOffline($payment->gateway)) {
            $segments = explode('-', $payment->gateway);
            $offlinePaymentId = (int) end($segments);
            session()->put('manualPaymentMethod', ManualPaymentMethod::query()->find($offlinePaymentId));
        }
    }

    private function dispatchGatewayInitializer(string $gateway, Request $request): mixed
    {
        return match ($gateway) {
            'paypal' => app(PaypalPaymentController::class)->index(),
            'stripe' => app(StripePaymentController::class)->index(),
            'sslcommerz' => app(SSLCommerzPaymentController::class)->index(),
            'paystack' => app(PaystackPaymentController::class)->index($request),
            'flutterwave' => app(FlutterwavePaymentController::class)->index(),
            'paytm' => app(PaytmPaymentController::class)->index(),
            'razorpay' => app(RazorpayPaymentController::class)->index(),
            'payfast' => app(PayfastPaymentController::class)->index(),
            'authorizenet' => app(AuthorizenetPaymentController::class)->index(),
            'mercadopago' => app(MercadopagoPaymentController::class)->index(),
            'iyzico' => app(IyzicoPaymentController::class)->index(),
            'myfatoorah' => app(MyfatoorahPaymentController::class)->index($request),
            'phonepe' => app(PhonepePaymentController::class)->index(),
            'payhere' => app(PayherePaymentController::class)->index(),
            default => throw new HttpException(Response::HTTP_UNPROCESSABLE_ENTITY, 'Unsupported payment gateway.'),
        };
    }

    private function buildRedirectUrl(Payment $payment, string $status, string $gateway): string
    {
        $query = [$payment->payment_type => $status, 'payment_method' => $gateway];

        if ($payment->order_code) {
            $query['order_code'] = $payment->order_code;
        }

        return $payment->redirect_to . '?' . http_build_query($query);
    }
}
