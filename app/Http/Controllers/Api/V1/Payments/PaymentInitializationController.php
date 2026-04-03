<?php

namespace App\Http\Controllers\Api\V1\Payments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Payments\InitializePaymentRequest;
use App\Services\Payments\PaymentInitializationService;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

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
        } catch (Throwable $exception) {
            Log::error('API payment initialization failed unexpectedly.', [
                'gateway' => $gateway,
                'payment_type' => $request->input('payment_type'),
                'order_code' => $request->input('order_code'),
                'user_id' => $request->input('user_id'),
                'exception_class' => get_class($exception),
                'exception_message' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment provider is unavailable at the moment. Please try again or use another payment method.',
            ], 503);
        }
    }
}
