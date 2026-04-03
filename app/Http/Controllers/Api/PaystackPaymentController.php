<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
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
        session()->put('redirect_to', $request->redirect_to);
        session()->put('amount', $request->amount);
        session()->put('payment_method', $request->payment_method);
        session()->put('payment_type', $request->payment_type);
        session()->put('user_id', $request->user_id);
        session()->put('order_code', $request->order_code);

        $paymentType = (string) $request->payment_type;
        $order = $this->resolveOrder($request);
        $email = $this->resolveCheckoutEmail($request, $order);

        if (!$email) {
            throw new HttpException(422, 'Unable to initialize Paystack payment because no customer email address was found.');
        }

        $this->assertPaystackConfigured();

        if ($request->payment_type == 'cart_payment') {
            if (!$order) {
                throw new HttpException(404, 'Unable to initialize Paystack payment because the order could not be found.');
            }

            $request->email = $email;
            $request->amount = round($order->grand_total * 100);
            $request->currency = env('PAYSTACK_CURRENCY_CODE', 'NGN');
            $request->reference = Paystack::genTranxRef();
        } elseif ($request->payment_type == 'wallet_payment') {
            $request->email = $email;
            $request->amount = round($request->amount * 100);
            $request->currency = env('PAYSTACK_CURRENCY_CODE', 'NGN');
            $request->reference = Paystack::genTranxRef();
        } else {
            throw new HttpException(422, 'Unsupported payment type.');
        }

        try {
            return Paystack::getAuthorizationUrl()->redirectNow();
        } catch (Throwable $exception) {
            throw $this->paystackInitializationException($exception, $request, [
                'legacy_api_controller' => true,
                'order_id' => $order?->id,
                'payment_type' => $paymentType,
                'email_present' => true,
            ]);
        }
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
                if (session('payment_type') == 'cart_payment') {
                    $order = Order::where('code', session('order_code'))->first();
                    $ordercontroller = new OrderController;
                    $ordercontroller->paymentDone($order, session('payment_method'), $payment_details);
                } else if (session('payment_type') == 'wallet_payment') {
                    $payment_data['amount'] = session('amount');
                    $payment_data['user_id'] = session('user_id');
                    $payment_data['payment_method'] = session('payment_method');

                    $walletController = new WalletController;
                    $walletController->wallet_payment_done($payment_data, $payment_details);
                }

                $redirect_to = session('redirect_to') . "?" . session('payment_type') . "=success&order_code=" . session('order_code');

                session()->forget('redirect_to');
                session()->forget('amount');
                session()->forget('payment_method');
                session()->forget('payment_type');
                session()->forget('user_id');
                session()->forget('order_code');

                return redirect($redirect_to);
            } else {
                $redirect_to = session('redirect_to') . "?" . session('payment_type') . "=failed&order_code=" . session('order_code') . "&payment_method=" . session('payment_method');
                return redirect($redirect_to);
            }
        } catch (\Exception $e) {
            $redirect_to = session('redirect_to') . "?" . session('payment_type') . "=failed&order_code=" . session('order_code') . "&payment_method=" . session('payment_method');
            return redirect($redirect_to);
        }
    }

    private function resolveOrder(Request $request): ?Order
    {
        $orderCode = $request->input('order_code', session('order_code'));

        if (!$orderCode) {
            return null;
        }

        return Order::where('code', $orderCode)->first();
    }

    private function resolveCheckoutEmail(Request $request, ?Order $order): ?string
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

        return auth('api')->user();
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
