<?php

namespace App\Services\Payments\AlatPay;

use App\Models\AlatPayTransaction;

class AlatPayRoutes
{
    public const CHECKOUT = 'alatpay.checkout';
    public const STATUS = 'alatpay.status';
    public const VERIFY = 'alatpay.verify';
    public const WEBHOOK = 'api.alatpay.webhook';
    public const ADMIN_UPDATE = 'alatpay.settings.update';

    public function checkout(AlatPayTransaction $transaction): string
    {
        return route(self::CHECKOUT, ['reference' => $transaction->reference]);
    }

    public function status(AlatPayTransaction $transaction): string
    {
        return route(self::STATUS, ['reference' => $transaction->reference]);
    }

    public function verify(AlatPayTransaction $transaction): string
    {
        return route(self::VERIFY, ['reference' => $transaction->reference]);
    }

    public function webhook(): string
    {
        return route(self::WEBHOOK);
    }
}
