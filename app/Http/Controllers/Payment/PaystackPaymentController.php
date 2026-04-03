<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\CombinedOrder;
use App\Models\User;
use App\Support\Payments\HandlesPaystackInitialization;
use Illuminate\Http\Request;
use Paystack;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class PaystackPaymentController extends Controller
{
    use HandlesPaystackInitialization;

    public function index(Request $request)
    {
        $paymentType = $request->input('payment_type', session('payment_type'));
        $order = $this->resolveCombinedOrder($request);
        $email = $this->resolveCheckoutEmail($request, $order);

        if (!$email) {
            throw new HttpException(422, 'Unable to initialize Paystack payment because no customer email address was found.');
        }

        $this->assertPaystackConfigured();

        $request->merge([
            'email' => $email,
            'currency' => env('PAYSTACK_CURRENCY_CODE', 'NGN'),
        ]);

        if ($paymentType == 'cart_payment' || $paymentType == 'repayment') {
            if (!$order) {
                throw new HttpException(404, 'Unable to initialize Paystack payment because the order could not be found.');
            }

            $request->merge([
                'amount' => round($order->grand_total * 100),
            ]);
        } elseif ($paymentType == 'wallet_payment') {
            $request->merge([
                'amount' => round($request->amount * 100),
            ]);
        } elseif ($paymentType == 'seller_package_payment') {
            $request->merge([
                'amount' => round($request->amount * 100),
            ]);
        }

        $request->merge([
            'reference' => Paystack::genTranxRef(),
        ]);

        try {
            return Paystack::getAuthorizationUrl()->redirectNow();
        } catch (Throwable $exception) {
            throw $this->paystackInitializationException($exception, $request, [
                'combined_order_id' => $order?->id,
                'email_present' => true,
            ]);
        }
    }

    public function paystackNewCallback()
    {
        Paystack::getCallbackData();
    }

    /**
     * Obtain Paystack payment information
     * @return void
     */
    public function return()
    {
        // Now you have the payment details,
        // you can store the authorization_code in your db to allow for recurrent subscriptions
        // you can then redirect or do whatever you want

        try {
            $payment = Paystack::getPaymentData();
            $payment_details = json_encode($payment);
            if (!empty($payment['data']) && $payment['data']['status'] == 'success') {
                return (new PaymentController)->payment_success($payment_details);
            } else {
                return (new PaymentController)->payment_failed();
            }
        } catch (\Exception $e) {
            return (new PaymentController)->payment_failed();
        }
    }

    private function resolveCombinedOrder(Request $request): ?CombinedOrder
    {
        $orderCode = $request->input('order_code', session('order_code'));

        if (!$orderCode) {
            return null;
        }

        return CombinedOrder::where('code', $orderCode)->first();
    }

    private function resolveCheckoutEmail(Request $request, ?CombinedOrder $order): ?string
    {
        $user = $this->resolveUser($request);
        $shippingAddress = $this->decodeAddress($order?->shipping_address);
        $billingAddress = $this->decodeAddress($order?->billing_address);

        $candidates = [
            $user?->email,
            $request->input('email'),
            $shippingAddress['email'] ?? null,
            $billingAddress['email'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (!is_string($candidate)) {
                continue;
            }

            $candidate = trim($candidate);
            if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
                return $candidate;
            }
        }

        return null;
    }

    private function resolveUser(Request $request): ?User
    {
        if ($request->filled('user_id')) {
            $user = User::find($request->user_id);
            if ($user) {
                return $user;
            }
        }

        return $request->user();
    }

    private function decodeAddress($address): array
    {
        if (!is_string($address) || trim($address) === '') {
            return [];
        }

        $decoded = json_decode($address, true);

        return is_array($decoded) ? $decoded : [];
    }

}
