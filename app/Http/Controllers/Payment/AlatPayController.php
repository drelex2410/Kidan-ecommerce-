<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Jobs\Payments\ReconcileAlatPayTransactionJob;
use App\Models\Payment;
use App\Services\Payments\AlatPay\AlatPayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AlatPayController extends Controller
{
    public function __construct(private readonly AlatPayService $alatPayService)
    {
    }

    public function index(Request $request)
    {
        $paymentId = session('payment_id');
        if (!$paymentId) {
            throw new HttpException(422, 'Payment session has expired.');
        }

        $payment = Payment::query()->findOrFail($paymentId);
        $transaction = $this->alatPayService->initializePayment($payment);

        if ($this->alatPayService->shouldQueueDeferredReconciliation($transaction)) {
            ReconcileAlatPayTransactionJob::dispatch($transaction->id)->delay(now()->addMinutes(5));
        }

        return redirect()->route('alatpay.checkout', ['reference' => $transaction->reference]);
    }

    public function checkout(string $reference)
    {
        $transaction = $this->alatPayService->findTransactionByReferenceOrFail($reference);

        return view('payment.alatpay.checkout', $this->alatPayService->checkoutData($transaction));
    }

    public function status(string $reference, Request $request): JsonResponse
    {
        $transaction = $this->alatPayService->findTransactionByReferenceOrFail($reference);

        return response()->json(
            $this->alatPayService->statusPayload($transaction, $request->boolean('refresh', true))
        );
    }

    public function verify(string $reference, Request $request): JsonResponse
    {
        $transaction = $this->alatPayService->findTransactionByReferenceOrFail($reference);
        $pluginResponse = $request->input('plugin_response');

        return response()->json(
            $this->alatPayService->verify($transaction, is_array($pluginResponse) ? $pluginResponse : [])
        );
    }
}
