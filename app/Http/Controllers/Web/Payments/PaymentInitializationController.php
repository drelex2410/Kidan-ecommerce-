<?php

namespace App\Http\Controllers\Web\Payments;

use App\Http\Controllers\Controller;
use App\Services\Payments\PaymentInitializationService;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PaymentInitializationController extends Controller
{
    public function __construct(
        private readonly PaymentInitializationService $paymentInitializationService
    ) {
    }

    public function __invoke(Request $request, string $gateway)
    {
        try {
            return $this->paymentInitializationService->initializeWeb($gateway, $request);
        } catch (HttpException $exception) {
            return redirect($request->input('redirect_to', '/'))
                ->with('payment_error', $exception->getMessage());
        }
    }
}
