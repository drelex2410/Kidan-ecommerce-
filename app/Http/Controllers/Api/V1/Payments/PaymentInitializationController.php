<?php

namespace App\Http\Controllers\Api\V1\Payments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Payments\InitializePaymentRequest;
use App\Services\Payments\PaymentInitializationService;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PaymentInitializationController extends Controller
{
    public function __construct(
        private readonly PaymentInitializationService $paymentInitializationService
    ) {
    }

    public function __invoke(InitializePaymentRequest $request, string $gateway)
    {
        try {
            return response()->json(
                $this->paymentInitializationService->initializeApi($gateway, $request)
            );
        } catch (HttpException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], $exception->getStatusCode());
        }
    }
}
