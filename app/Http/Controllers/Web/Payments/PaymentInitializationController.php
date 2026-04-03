<?php

namespace App\Http\Controllers\Web\Payments;

use App\Http\Controllers\Controller;
use App\Services\Payments\PaymentInitializationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

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
        } catch (Throwable $exception) {
            Log::error('Web payment initialization failed unexpectedly.', [
                'gateway' => $gateway,
                'payment_type' => $request->input('payment_type'),
                'order_code' => $request->input('order_code'),
                'user_id' => $request->input('user_id'),
                'exception_class' => get_class($exception),
                'exception_message' => $exception->getMessage(),
            ]);

            return redirect($request->input('redirect_to', '/'))
                ->with('payment_error', 'Payment provider is unavailable at the moment. Please try again or use another payment method.');
        }
    }
}
