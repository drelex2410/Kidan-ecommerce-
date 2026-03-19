<?php

namespace App\Services\Payments;

use App\Models\ManualPaymentMethod;
use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PaymentGatewayManager
{
    private const SETTINGS = [
        'paypal' => 'paypal_payment',
        'stripe' => 'stripe_payment',
        'sslcommerz' => 'sslcommerz_payment',
        'paystack' => 'paystack_payment',
        'flutterwave' => 'flutterwave_payment',
        'razorpay' => 'razorpay_payment',
        'paytm' => 'paytm_payment',
        'payfast' => 'payfast_payment',
        'authorizenet' => 'authorizenet_payment',
        'mercadopago' => 'mercadopago_payment',
        'iyzico' => 'iyzico_payment',
        'myfatoorah' => 'myfatoorah_payment',
        'phonepe' => 'phonepe_payment',
        'payhere' => 'payhere_payment',
        'cash_on_delivery' => 'cash_payment',
    ];

    public function __construct()
    {
    }

    public function assertEnabled(string $gateway): void
    {
        if ($this->isOffline($gateway)) {
            if ((int) $this->settings()->get('offline_payment', 0) !== 1) {
                throw new HttpException(422, 'This payment method is disabled.');
            }

            $segments = explode('-', $gateway);
            $offlinePaymentId = (int) end($segments);
            $method = ManualPaymentMethod::query()->find($offlinePaymentId);

            if (!$method) {
                throw new HttpException(404, 'Offline payment method not found.');
            }

            return;
        }

        $settingKey = self::SETTINGS[$gateway] ?? null;

        if ($settingKey === null) {
            throw new HttpException(422, 'Unsupported payment gateway.');
        }

        if ((int) $this->settings()->get($settingKey, 0) !== 1) {
            throw new HttpException(422, 'This payment gateway is disabled.');
        }
    }

    public function isOffline(string $gateway): bool
    {
        return Str::contains($gateway, 'offline_payment');
    }

    public function isOnline(string $gateway): bool
    {
        return !$this->isOffline($gateway);
    }

    private function settings(): Collection
    {
        return Setting::query()->pluck('value', 'type');
    }
}
