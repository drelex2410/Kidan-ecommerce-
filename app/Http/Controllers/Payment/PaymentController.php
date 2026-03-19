<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Services\Payments\PaymentCallbackService;

class PaymentController extends Controller
{
    public function payment_success($payment_details = null)
    {
        return app(PaymentCallbackService::class)->markSuccess((string) session('payment_method'), $payment_details);
    }

    public function payment_failed()
    {
        return app(PaymentCallbackService::class)->markFailed((string) session('payment_method'));
    }
}
