<?php

namespace App\Services\Payments\AlatPay;

use App\Models\AlatPayRefund;
use App\Models\AlatPayTransaction;
use App\Models\AlatPayWebhookLog;
use App\Models\CombinedOrder;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Services\Payments\PaymentCallbackService;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AlatPayService
{
    public function __construct(
        private readonly AlatPayConfig $config,
        private readonly AlatPayRoutes $routes,
        private readonly AlatPayTransformer $transformer,
        private readonly AlatPayReconciliationService $reconciliationService,
        private readonly PaymentCallbackService $callbackService,
    ) {
    }

    public function initializePayment(Payment $payment): AlatPayTransaction
    {
        $this->assertReadyForInitialization($payment);

        if ($this->config->webPluginReady()) {
            return $this->initializePluginPayment($payment);
        }

        return $this->initializeVirtualAccountPayment($payment);
    }

    private function initializeVirtualAccountPayment(Payment $payment): AlatPayTransaction
    {
        $this->assertReadyForInitialization($payment);

        $existing = AlatPayTransaction::query()
            ->where('payment_id', $payment->id)
            ->whereIn('status', ['pending', 'processing'])
            ->latest('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        $order = $payment->combinedOrder ?: ($payment->order_code
            ? CombinedOrder::query()->where('code', $payment->order_code)->first()
            : null);

        $reference = $this->transformer->merchantReference($payment);
        $sessionReference = $this->transformer->sessionReference($payment);
        $dto = $this->transformer->toInitializationData($payment, $order, $this->config);
        $payload = $dto->toArray();

        $startedAt = microtime(true);
        $response = $this->providerRequest('POST', (string) $this->config->path('virtual_account'), $payload);
        $latencyMs = (int) round((microtime(true) - $startedAt) * 1000);

        if (!$response->successful()) {
            $this->logProviderFailure('Initialization failed for ALATPay payment.', $payment, $response, $latencyMs);

            throw new HttpException(502, 'ALATPay is unavailable right now. Please try again in a moment.');
        }

        $body = $response->json();
        if (!(bool) data_get($body, 'status', false) || !is_array(data_get($body, 'data'))) {
            $this->logProviderFailure('ALATPay returned an invalid initialization response.', $payment, $response, $latencyMs);

            throw new HttpException(502, (string) (data_get($body, 'message') ?: 'ALATPay did not accept this transaction.'));
        }

        $data = data_get($body, 'data');
        $status = $this->transformer->normalizeStatus((string) data_get($data, 'status', 'pending'));

        $transaction = DB::transaction(function () use ($payment, $reference, $sessionReference, $status, $data, $body): AlatPayTransaction {
            $paymentChannel = (string) Arr::get($payment->meta ?? [], 'payment_channel', 'bank_transfer');
            $paymentMeta = array_merge($payment->meta ?? [], [
                'order_id' => $payment->combined_order_id,
                'order_code' => $payment->order_code,
                'escrow_id' => Arr::get($payment->meta ?? [], 'escrow_id'),
                'user_id' => $payment->user_id,
                'tenant_id' => Arr::get($payment->meta ?? [], 'tenant_id'),
                'payment_channel' => $paymentChannel,
                'currency' => $payment->currency,
                'amount' => (float) $payment->amount,
                'session_reference' => $sessionReference,
            ]);

            $payment->update([
                'status' => 'pending',
                'meta' => $paymentMeta,
            ]);

            $transaction = AlatPayTransaction::query()->create([
                'payment_id' => $payment->id,
                'user_id' => $payment->user_id,
                'combined_order_id' => $payment->combined_order_id,
                'reference' => $reference,
                'transaction_id' => data_get($data, 'transactionId'),
                'provider_reference' => data_get($data, 'sessionId'),
                'provider_record_id' => data_get($data, 'id'),
                'merchant_id' => data_get($data, 'merchantId', $this->config->merchantId()),
                'order_code' => $payment->order_code,
                'order_identifier' => $reference,
                'tenant_id' => Arr::get($paymentMeta, 'tenant_id'),
                'escrow_id' => Arr::get($paymentMeta, 'escrow_id'),
                'session_reference' => $sessionReference,
                'payment_channel' => $paymentChannel,
                'currency' => $payment->currency ?: 'NGN',
                'amount' => $payment->amount,
                'environment' => $this->config->environment(),
                'status' => $status,
                'checkout_url' => null,
                'expires_at' => data_get($data, 'expiredAt'),
                'instructions' => $this->buildInstructionPayload($payment, $data),
                'provider_payload' => $body,
                'metadata' => $paymentMeta,
            ]);

            $transaction->update([
                'checkout_url' => $this->routes->checkout($transaction),
            ]);

            return $transaction->fresh();
        });

        $this->recordPaymentEvent($payment, 'alatpay.initialized', $reference, 'pending', [
            'latency_ms' => $latencyMs,
            'response' => $body,
        ]);

        Log::info('ALATPay transaction initialized.', [
            'gateway' => 'alatpay',
            'transaction_reference' => $reference,
            'order_id' => $payment->order_code,
            'user_id' => $payment->user_id,
            'response_code' => $response->status(),
            'provider_reference' => data_get($data, 'sessionId'),
            'latency' => $latencyMs,
            'environment' => $this->config->environment(),
        ]);

        return $transaction;
    }

    private function initializePluginPayment(Payment $payment): AlatPayTransaction
    {
        $existing = AlatPayTransaction::query()
            ->where('payment_id', $payment->id)
            ->whereIn('status', ['pending', 'processing'])
            ->latest('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        $order = $payment->combinedOrder ?: ($payment->order_code
            ? CombinedOrder::query()->where('code', $payment->order_code)->first()
            : null);

        $reference = $this->transformer->merchantReference($payment);
        $sessionReference = $this->transformer->sessionReference($payment);
        $dto = $this->transformer->toInitializationData($payment, $order, $this->config);
        $paymentChannel = 'web_plugin';

        $transaction = DB::transaction(function () use ($payment, $reference, $sessionReference, $paymentChannel, $dto): AlatPayTransaction {
            $paymentMeta = array_merge($payment->meta ?? [], [
                'order_id' => $payment->combined_order_id,
                'order_code' => $payment->order_code,
                'escrow_id' => Arr::get($payment->meta ?? [], 'escrow_id'),
                'user_id' => $payment->user_id,
                'tenant_id' => Arr::get($payment->meta ?? [], 'tenant_id'),
                'payment_channel' => $paymentChannel,
                'currency' => $payment->currency,
                'amount' => (float) $payment->amount,
                'session_reference' => $sessionReference,
            ]);

            $payment->update([
                'status' => 'pending',
                'meta' => $paymentMeta,
            ]);

            $transaction = AlatPayTransaction::query()->create([
                'payment_id' => $payment->id,
                'user_id' => $payment->user_id,
                'combined_order_id' => $payment->combined_order_id,
                'reference' => $reference,
                'transaction_id' => null,
                'provider_reference' => null,
                'provider_record_id' => null,
                'merchant_id' => $this->config->merchantId(),
                'order_code' => $payment->order_code,
                'order_identifier' => $reference,
                'tenant_id' => Arr::get($paymentMeta, 'tenant_id'),
                'escrow_id' => Arr::get($paymentMeta, 'escrow_id'),
                'session_reference' => $sessionReference,
                'payment_channel' => $paymentChannel,
                'currency' => $payment->currency ?: 'NGN',
                'amount' => $payment->amount,
                'environment' => $this->config->environment(),
                'status' => 'pending',
                'checkout_url' => null,
                'instructions' => $this->buildPluginInstructionPayload($payment, $dto->toArray()),
                'provider_payload' => null,
                'metadata' => $paymentMeta,
            ]);

            $transaction->update([
                'checkout_url' => $this->routes->checkout($transaction),
            ]);

            return $transaction->fresh();
        });

        $this->recordPaymentEvent($payment, 'alatpay.plugin.initialized', $reference, 'pending', [
            'checkout_mode' => 'web_plugin',
            'public_key_present' => filled($this->config->publicKey()),
        ]);

        Log::info('ALATPay plugin checkout initialized.', [
            'gateway' => 'alatpay',
            'transaction_reference' => $reference,
            'order_id' => $payment->order_code,
            'user_id' => $payment->user_id,
            'environment' => $this->config->environment(),
        ]);

        return $transaction;
    }

    public function checkoutData(AlatPayTransaction $transaction): array
    {
        $transaction->loadMissing(['payment.user', 'combinedOrder']);

        $checkoutMode = $this->usesWebPlugin($transaction) ? 'web_plugin' : 'virtual_account';

        return [
            'transaction' => $transaction,
            'instructions' => $transaction->instructions ?? [],
            'status_url' => $this->routes->status($transaction),
            'verify_url' => $this->routes->verify($transaction),
            'checkout_mode' => $checkoutMode,
            'plugin_script_url' => $checkoutMode === 'web_plugin' ? $this->config->pluginScriptUrl() : null,
            'plugin_payload' => $checkoutMode === 'web_plugin' ? $this->pluginPayload($transaction) : null,
        ];
    }

    public function findTransactionByReferenceOrFail(string $reference): AlatPayTransaction
    {
        return AlatPayTransaction::query()
            ->with('payment')
            ->where('reference', $reference)
            ->firstOrFail();
    }

    public function shouldQueueDeferredReconciliation(AlatPayTransaction $transaction): bool
    {
        return !$this->usesWebPlugin($transaction);
    }

    public function statusPayload(AlatPayTransaction $transaction, bool $forceReconcile = false): array
    {
        if ($forceReconcile && in_array($transaction->status, ['pending', 'processing'], true)) {
            $lastReconciledAt = $transaction->last_reconciled_at;
            if (!$lastReconciledAt || $lastReconciledAt->lt(now()->subSeconds(15))) {
                $statusData = $this->reconciliationService->reconcile($transaction);
                $this->applyStatusData($transaction->fresh(), $statusData, 'reconciliation');
            }
        }

        $transaction->refresh();

        $terminal = in_array($transaction->status, ['successful', 'failed', 'cancelled', 'reversed'], true);

        return [
            'success' => true,
            'gateway' => 'alatpay',
            'reference' => $transaction->reference,
            'status' => $transaction->status,
            'provider_reference' => $transaction->provider_reference,
            'transaction_id' => $transaction->transaction_id,
            'message' => $this->statusMessage($transaction->status),
            'instructions' => $transaction->instructions ?? [],
            'expires_at' => optional($transaction->expires_at)?->toIso8601String(),
            'redirect_url' => $terminal ? $this->redirectUrlForTransaction($transaction) : null,
        ];
    }

    public function verify(AlatPayTransaction $transaction, array $pluginResponse = []): array
    {
        if ($pluginResponse !== []) {
            $this->capturePluginResponse($transaction, $pluginResponse);
        }

        $statusData = $this->reconciliationService->reconcile($transaction, 'manual_verify');
        $this->applyStatusData($transaction, $statusData, 'verify');

        return $this->statusPayload($transaction->fresh());
    }

    public function processWebhookLog(AlatPayWebhookLog $webhookLog): ?AlatPayTransaction
    {
        $webhookData = $this->transformer->toWebhookData($webhookLog->payload ?? []);
        $this->processRefundWebhookIfApplicable($webhookData, $webhookLog);

        if ($webhookData->reference === '' && !$webhookData->transactionId) {
            $webhookLog->update([
                'status' => 'ignored',
                'attempts' => $webhookLog->attempts + 1,
                'processed_at' => now(),
                'error_message' => 'Unable to correlate webhook payload to a local transaction.',
            ]);

            return null;
        }

        $transaction = AlatPayTransaction::query()
            ->with('payment')
            ->where(function ($query) use ($webhookData) {
                $hasPrimaryCondition = false;

                if ($webhookData->reference !== '') {
                    $query->where('reference', $webhookData->reference)
                        ->orWhere('order_identifier', $webhookData->reference);
                    $hasPrimaryCondition = true;
                }

                if ($webhookData->transactionId) {
                    if ($hasPrimaryCondition) {
                        $query->orWhere('transaction_id', $webhookData->transactionId);
                    } else {
                        $query->where('transaction_id', $webhookData->transactionId);
                    }
                }
            })
            ->first();

        if (!$transaction) {
            $webhookLog->update([
                'status' => 'orphaned',
                'attempts' => $webhookLog->attempts + 1,
                'processed_at' => now(),
                'error_message' => 'No local ALATPay transaction matched the webhook.',
            ]);

            return null;
        }

        $webhookLog->update([
            'alatpay_transaction_id' => $transaction->id,
            'payment_id' => $transaction->payment_id,
            'tenant_id' => $transaction->tenant_id,
        ]);

        $statusData = $this->transformer->toStatusData($webhookData->payload, $transaction->reference);
        $this->applyStatusData($transaction, $statusData, 'webhook', $webhookLog);

        return $transaction->fresh();
    }

    public function requestRefund(
        AlatPayTransaction $transaction,
        float $amount,
        ?string $reason = null,
        ?int $refundRequestId = null,
        ?int $requestedBy = null,
        array $metadata = []
    ): AlatPayRefund {
        $reference = 'ALAT-REF-' . strtoupper(Str::random(18));

        $refund = AlatPayRefund::query()->create([
            'payment_id' => $transaction->payment_id,
            'alatpay_transaction_id' => $transaction->id,
            'refund_request_id' => $refundRequestId,
            'requested_by' => $requestedBy,
            'reference' => $reference,
            'tenant_id' => $transaction->tenant_id,
            'order_code' => $transaction->order_code,
            'amount' => round($amount, 2),
            'currency' => $transaction->currency,
            'status' => 'pending',
            'reason' => $reason,
            'requested_at' => now(),
            'metadata' => $metadata,
        ]);

        try {
            $response = $this->providerRequest('POST', (string) $this->config->path('refund'), array_filter([
                'businessId' => $this->config->merchantId(),
                'merchantId' => $transaction->merchant_id ?: $this->config->merchantId(),
                'orderId' => $transaction->reference,
                'transactionId' => $transaction->transaction_id,
                'amount' => round($amount, 2),
                'currency' => $transaction->currency,
                'reason' => $reason,
                'reference' => $reference,
            ], static fn ($value) => $value !== null && $value !== ''));

            $payload = $response->json();
            $normalizedStatus = $response->successful()
                ? $this->transformer->normalizeStatus((string) (data_get($payload, 'data.status') ?: data_get($payload, 'status')))
                : 'failed';

            $refund->update([
                'provider_reference' => data_get($payload, 'data.transactionId') ?: data_get($payload, 'data.id'),
                'status' => $normalizedStatus,
                'provider_payload' => $payload,
                'completed_at' => $normalizedStatus === 'successful' ? now() : null,
                'failed_at' => $normalizedStatus === 'failed' ? now() : null,
            ]);
        } catch (\Throwable $exception) {
            $refund->update([
                'status' => 'pending_manual_review',
                'provider_payload' => [
                    'message' => $exception->getMessage(),
                    'fallback' => 'The refund request was stored locally for manual follow-up because the provider refund endpoint could not be confirmed.',
                ],
            ]);

            Log::warning('ALATPay refund call failed and was left for manual review.', [
                'gateway' => 'alatpay',
                'transaction_reference' => $transaction->reference,
                'refund_reference' => $reference,
                'order_id' => $transaction->order_code,
                'exception_message' => $exception->getMessage(),
            ]);
        }

        return $refund->fresh();
    }

    public function redirectUrlForTransaction(AlatPayTransaction $transaction): string
    {
        $payment = $transaction->payment;
        $status = $transaction->status === 'successful' ? 'success' : 'failed';
        $query = [$payment->payment_type => $status, 'payment_method' => 'alatpay'];

        if ($payment->order_code) {
            $query['order_code'] = $payment->order_code;
        }

        return $payment->redirect_to . '?' . http_build_query($query);
    }

    private function assertReadyForInitialization(Payment $payment): void
    {
        if (!$this->config->enabled()) {
            throw new HttpException(422, 'ALATPay is disabled.');
        }

        if (!$this->config->isConfigured()) {
            throw new HttpException(422, 'ALATPay is not fully configured yet.');
        }

        $currency = strtoupper((string) ($payment->currency ?: 'NGN'));
        if (!in_array($currency, $this->config->supportedCurrencies(), true)) {
            throw new HttpException(422, 'ALATPay does not support the selected currency.');
        }

        if ((float) $payment->amount <= 0) {
            throw new HttpException(422, 'ALATPay cannot initialize a zero-value payment.');
        }
    }

    private function providerRequest(string $method, string $path, array $payload): Response
    {
        $url = rtrim($this->config->baseUrl(), '/') . '/' . ltrim($path, '/');

        $client = Http::timeout(25)
            ->retry(2, 400, throw: false)
            ->acceptJson()
            ->withHeaders($this->config->authHeaders());

        return strtoupper($method) === 'GET'
            ? $client->get($url, $payload)
            : $client->post($url, $payload);
    }

    private function usesWebPlugin(AlatPayTransaction $transaction): bool
    {
        return $this->config->webPluginReady()
            && data_get($transaction->instructions ?? [], 'checkout_mode') === 'web_plugin';
    }

    private function pluginPayload(AlatPayTransaction $transaction): array
    {
        $payment = $transaction->payment;
        if (!$payment) {
            throw new HttpException(422, 'ALATPay payment session is missing.');
        }

        $order = $transaction->combinedOrder;
        if (!$order && $payment->order_code) {
            $order = CombinedOrder::query()->where('code', $payment->order_code)->first();
        }

        $dto = $this->transformer->toInitializationData($payment, $order, $this->config);

        return [
            'apiKey' => $this->config->publicKey(),
            'businessId' => $dto->businessId,
            'email' => $dto->customer->email,
            'phone' => $dto->customer->phone,
            'firstName' => $dto->customer->firstName,
            'lastName' => $dto->customer->lastName,
            'currency' => $dto->currency,
            'amount' => $dto->amount,
            'orderId' => $transaction->reference,
            'description' => $dto->description,
            'channel' => $dto->channel,
            'metadata' => array_merge($dto->metadata, [
                'alatpay_reference' => $transaction->reference,
                'session_reference' => $transaction->session_reference,
            ]),
        ];
    }

    private function buildInstructionPayload(Payment $payment, array $data): array
    {
        return [
            'business_name' => data_get($data, 'businessName', config('app.name')),
            'account_number' => data_get($data, 'virtualBankAccountNumber'),
            'bank_code' => data_get($data, 'virtualBankCode'),
            'business_bank_code' => data_get($data, 'businessBankCode'),
            'business_bank_account_number' => data_get($data, 'businessBankAccountNumber'),
            'amount' => (float) data_get($data, 'amount', $payment->amount),
            'currency' => data_get($data, 'currency', $payment->currency),
            'status' => data_get($data, 'status'),
            'status_reason' => data_get($data, 'statusReason'),
            'expires_at' => data_get($data, 'expiredAt'),
            'transaction_id' => data_get($data, 'transactionId'),
        ];
    }

    private function buildPluginInstructionPayload(Payment $payment, array $data): array
    {
        return [
            'checkout_mode' => 'web_plugin',
            'business_name' => config('app.name'),
            'amount' => (float) data_get($data, 'amount', $payment->amount),
            'currency' => data_get($data, 'currency', $payment->currency),
            'order_id' => data_get($data, 'orderId'),
            'description' => data_get($data, 'description'),
            'customer' => data_get($data, 'customer'),
        ];
    }

    private function capturePluginResponse(AlatPayTransaction $transaction, array $payload): void
    {
        $statusCandidate = data_get($payload, 'data.status');
        if ($statusCandidate === null) {
            $statusCandidate = data_get($payload, 'status');
        }

        $normalizedStatus = is_bool($statusCandidate)
            ? ($statusCandidate ? 'processing' : 'failed')
            : $this->transformer->normalizeStatus(is_scalar($statusCandidate) ? (string) $statusCandidate : null);

        $transactionId = $this->firstString([
            data_get($payload, 'data.transactionId'),
            data_get($payload, 'transactionId'),
            data_get($payload, 'data.nipTransaction.transactionId'),
            data_get($payload, 'nipTransaction.transactionId'),
        ]);

        $providerReference = $this->firstString([
            data_get($payload, 'data.sessionId'),
            data_get($payload, 'sessionId'),
            data_get($payload, 'data.nipTransaction.paymentreference'),
            data_get($payload, 'paymentreference'),
            data_get($payload, 'reference'),
        ]);

        $providerRecordId = $this->firstString([
            data_get($payload, 'data.id'),
            data_get($payload, 'id'),
        ]);

        $transaction->update([
            'transaction_id' => $transactionId ?: $transaction->transaction_id,
            'provider_reference' => $providerReference ?: $transaction->provider_reference,
            'provider_record_id' => $providerRecordId ?: $transaction->provider_record_id,
            'status' => in_array($normalizedStatus, ['failed', 'cancelled', 'reversed'], true) ? $normalizedStatus : $transaction->status,
            'provider_payload' => $payload,
            'last_reconciled_at' => now(),
        ]);

        $this->recordPaymentEvent(
            $transaction->payment,
            'alatpay.plugin.response',
            $providerReference ?: $transactionId ?: $transaction->reference,
            $normalizedStatus,
            $payload
        );
    }

    private function applyStatusData(
        AlatPayTransaction $transaction,
        \App\Services\Payments\AlatPay\DTOs\AlatPayStatusData $statusData,
        string $source,
        ?AlatPayWebhookLog $webhookLog = null
    ): void {
        DB::transaction(function () use ($transaction, $statusData, $source, $webhookLog): void {
            $transaction->refresh();

            $updates = [
                'status' => $statusData->status,
                'provider_reference' => $statusData->providerReference ?: $transaction->provider_reference,
                'transaction_id' => $statusData->transactionId ?: $transaction->transaction_id,
                'provider_payload' => $statusData->payload,
                'last_reconciled_at' => now(),
            ];

            if ($statusData->status === 'successful' && !$transaction->completed_at) {
                $updates['completed_at'] = now();
            }

            if (in_array($statusData->status, ['failed', 'cancelled', 'reversed'], true) && !$transaction->failed_at) {
                $updates['failed_at'] = now();
            }

            $transaction->update($updates);

            if ($webhookLog) {
                $webhookLog->update([
                    'status' => 'processed',
                    'attempts' => $webhookLog->attempts + 1,
                    'processed_at' => now(),
                    'error_message' => null,
                ]);
            }

            $payment = $transaction->payment;
            if ($payment) {
                if ($statusData->status === 'successful') {
                    if (!$this->amountAndCurrencyAreConsistent($payment, $statusData->payload)) {
                        $transaction->update([
                            'status' => 'processing',
                            'provider_payload' => $statusData->payload,
                            'last_reconciled_at' => now(),
                        ]);

                        if ($webhookLog) {
                            $webhookLog->update([
                                'status' => 'failed',
                                'attempts' => $webhookLog->attempts + 1,
                                'processed_at' => now(),
                                'error_message' => 'Provider amount or currency did not match the local payment.',
                            ]);
                        }

                        Log::warning('ALATPay amount mismatch detected; payment not finalized.', [
                            'gateway' => 'alatpay',
                            'transaction_reference' => $transaction->reference,
                            'order_id' => $payment->order_code,
                            'user_id' => $payment->user_id,
                            'environment' => $this->config->environment(),
                        ]);

                        $this->recordPaymentEvent(
                            $payment,
                            'alatpay.' . $source . '.amount_mismatch',
                            $statusData->providerReference ?: $statusData->transactionId ?: $transaction->reference,
                            'processing',
                            $statusData->payload
                        );

                        return;
                    }

                    $this->callbackService->markSuccessForPayment($payment, 'alatpay', $statusData->payload);
                } elseif (in_array($statusData->status, ['failed', 'cancelled'], true)) {
                    $this->callbackService->markFailureForPayment($payment, 'alatpay', $statusData->payload, $statusData->status);
                }
            }

            $this->recordPaymentEvent(
                $payment,
                'alatpay.' . $source . '.' . $statusData->status,
                $statusData->providerReference ?: $statusData->transactionId ?: $transaction->reference,
                $statusData->status,
                $statusData->payload
            );
        });
    }

    private function statusMessage(string $status): string
    {
        return match ($status) {
            'successful' => 'Payment confirmed successfully.',
            'failed' => 'ALATPay marked this payment as failed.',
            'cancelled' => 'This payment was cancelled or expired.',
            'reversed' => 'This payment has been reversed.',
            'processing' => 'Payment is being processed. We are still waiting for confirmation.',
            default => 'We are waiting for the transfer to reach ALATPay.',
        };
    }

    private function logProviderFailure(string $message, Payment $payment, Response $response, int $latencyMs): void
    {
        Log::error($message, [
            'gateway' => 'alatpay',
            'transaction_reference' => $this->transformer->merchantReference($payment),
            'order_id' => $payment->order_code,
            'user_id' => $payment->user_id,
            'response_code' => $response->status(),
            'provider_reference' => data_get($response->json(), 'data.transactionId'),
            'latency' => $latencyMs,
            'environment' => $this->config->environment(),
        ]);
    }

    private function recordPaymentEvent(?Payment $payment, string $eventType, ?string $reference, string $status, mixed $payload): void
    {
        if (!$payment) {
            return;
        }

        $fingerprint = sha1($payment->id . '|alatpay|' . $eventType . '|' . ($reference ?: json_encode($payload)));

        PaymentTransaction::query()->firstOrCreate(
            ['fingerprint' => $fingerprint],
            [
                'payment_id' => $payment->id,
                'gateway' => 'alatpay',
                'event_type' => $eventType,
                'reference' => $reference,
                'status' => $status,
                'payload' => is_array($payload) ? $payload : ['payload' => $payload],
                'processed_at' => now(),
            ]
        );
    }

    private function processRefundWebhookIfApplicable(
        \App\Services\Payments\AlatPay\DTOs\AlatPayWebhookData $webhookData,
        AlatPayWebhookLog $webhookLog
    ): void {
        if ($webhookData->eventType !== 'refund.completed') {
            return;
        }

        $refund = AlatPayRefund::query()
            ->where(function ($query) use ($webhookData) {
                $hasPrimaryCondition = false;

                if ($webhookData->reference !== '') {
                    $query->where('reference', $webhookData->reference)
                        ->orWhere('provider_reference', $webhookData->reference);
                    $hasPrimaryCondition = true;
                }

                if ($webhookData->providerReference) {
                    if ($hasPrimaryCondition) {
                        $query->orWhere('provider_reference', $webhookData->providerReference);
                    } else {
                        $query->where('provider_reference', $webhookData->providerReference);
                    }
                }
            })
            ->latest('id')
            ->first();

        if (!$refund) {
            return;
        }

        $refund->update([
            'provider_reference' => $webhookData->providerReference ?: $refund->provider_reference,
            'status' => 'successful',
            'provider_payload' => $webhookData->payload,
            'completed_at' => now(),
            'failed_at' => null,
        ]);

        $webhookLog->update([
            'status' => 'processed',
            'attempts' => $webhookLog->attempts + 1,
            'processed_at' => now(),
            'error_message' => null,
        ]);
    }

    private function amountAndCurrencyAreConsistent(Payment $payment, array $payload): bool
    {
        $providerAmount = $this->extractProviderAmount($payload);
        $providerCurrency = strtoupper((string) (data_get($payload, 'data.currency') ?: data_get($payload, 'currency') ?: $payment->currency));
        $expectedAmount = round((float) $payment->amount, 2);

        $amountMatches = $providerAmount === null
            || abs($providerAmount - $expectedAmount) < 0.01
            || abs($providerAmount - ($expectedAmount * 100)) < 0.01;

        $currencyMatches = $providerCurrency === '' || strtoupper((string) $payment->currency) === $providerCurrency;

        return $amountMatches && $currencyMatches;
    }

    private function extractProviderAmount(array $payload): ?float
    {
        $candidates = [
            data_get($payload, 'data.amount'),
            data_get($payload, 'amount'),
            data_get($payload, 'data.nipTransaction.amount'),
            data_get($payload, 'data.virtualAccount.amount'),
        ];

        foreach ($candidates as $candidate) {
            if (is_numeric($candidate)) {
                return (float) $candidate;
            }
        }

        return null;
    }

    private function firstString(array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        return null;
    }
}
