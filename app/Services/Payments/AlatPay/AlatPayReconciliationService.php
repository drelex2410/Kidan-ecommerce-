<?php

namespace App\Services\Payments\AlatPay;

use App\Models\AlatPayReconciliationLog;
use App\Models\AlatPayTransaction;
use App\Services\Payments\AlatPay\DTOs\AlatPayStatusData;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AlatPayReconciliationService
{
    public function __construct(
        private readonly AlatPayConfig $config,
        private readonly AlatPayTransformer $transformer,
    ) {
    }

    public function reconcile(AlatPayTransaction $transaction, string $action = 'status_poll'): AlatPayStatusData
    {
        foreach ((array) $this->config->path('status_candidates') as $candidatePath) {
            $startedAt = microtime(true);
            try {
                $response = $this->request('POST', (string) $candidatePath, [
                    'businessId' => $this->config->merchantId(),
                    'merchantId' => $transaction->merchant_id ?: $this->config->merchantId(),
                    'orderId' => $transaction->reference,
                    'transactionId' => $transaction->transaction_id,
                    'reference' => $transaction->provider_reference ?: $transaction->reference,
                ]);

                if ($response->successful()) {
                    $payload = $response->json();
                    $this->logAttempt($transaction, $action, 'success', $response, $payload, (int) round((microtime(true) - $startedAt) * 1000));

                    return $this->transformer->toStatusData($payload, $transaction->reference);
                }

                $this->logAttempt($transaction, $action, 'provider_error', $response, $response->json(), (int) round((microtime(true) - $startedAt) * 1000));
            } catch (\Throwable $exception) {
                $this->logAttempt($transaction, $action, 'exception', null, [
                    'message' => $exception->getMessage(),
                    'path' => $candidatePath,
                ], (int) round((microtime(true) - $startedAt) * 1000));
            }
        }

        return $this->reconcileViaSettlements($transaction, $action);
    }

    private function reconcileViaSettlements(AlatPayTransaction $transaction, string $action): AlatPayStatusData
    {
        $startAt = optional($transaction->created_at)->subDay()?->toIso8601String();
        $endAt = now()->toIso8601String();
        $query = array_filter([
            'businessId' => $this->config->merchantId(),
            'merchantId' => $transaction->merchant_id ?: $this->config->merchantId(),
            'startAt' => $startAt,
            'endAt' => $endAt,
        ], static fn ($value) => $value !== null && $value !== '');

        $startedAt = microtime(true);
        $response = $this->request('GET', (string) $this->config->path('settlements'), $query);
        $payload = $response->json();
        $matched = collect(Arr::wrap(data_get($payload, 'data.items', data_get($payload, 'data', []))))
            ->first(function ($item) use ($transaction) {
                $orderId = (string) data_get($item, 'orderId', '');
                $txId = (string) data_get($item, 'transactionId', '');
                $sessionId = (string) data_get($item, 'sessionId', '');

                return in_array($transaction->reference, [$orderId, $sessionId], true)
                    || ($transaction->transaction_id && $transaction->transaction_id === $txId);
            });

        $resultPayload = is_array($matched)
            ? ['status' => true, 'message' => 'Reconciled via settlements.', 'data' => $matched]
            : ['status' => false, 'message' => 'Transaction not yet available in settlement feed.', 'data' => []];

        $this->logAttempt($transaction, $action . '_settlements', $response->successful() ? 'success' : 'provider_error', $response, $resultPayload, (int) round((microtime(true) - $startedAt) * 1000));

        return $this->transformer->toStatusData($resultPayload, $transaction->reference);
    }

    private function request(string $method, string $path, array $payload): Response
    {
        $url = rtrim($this->config->baseUrl(), '/') . '/' . ltrim($path, '/');
        $client = Http::timeout(25)
            ->retry(2, 400, throw: false)
            ->acceptJson()
            ->withHeaders($this->config->authHeaders());

        return strtoupper($method) === 'GET'
            ? $client->get($url, $payload)
            : $client->post($url, array_filter($payload, static fn ($value) => $value !== null && $value !== ''));
    }

    private function logAttempt(AlatPayTransaction $transaction, string $action, string $status, ?Response $response, array $payload, ?int $latencyMs = null): void
    {
        $existingAttempts = AlatPayReconciliationLog::query()
            ->where('alatpay_transaction_id', $transaction->id)
            ->where('action', $action)
            ->count();

        $log = AlatPayReconciliationLog::query()->create([
            'alatpay_transaction_id' => $transaction->id,
            'payment_id' => $transaction->payment_id,
            'tenant_id' => $transaction->tenant_id,
            'correlation_id' => (string) Str::uuid(),
            'reference' => $transaction->reference,
            'provider_reference' => $transaction->provider_reference,
            'action' => $action,
            'status' => $status,
            'response_code' => $response ? (string) $response->status() : null,
            'message' => data_get($payload, 'message'),
            'latency_ms' => $latencyMs,
            'attempts' => $existingAttempts + 1,
            'payload' => $payload,
            'reconciled_at' => now(),
        ]);

        Log::info('ALATPay reconciliation attempt recorded.', [
            'gateway' => 'alatpay',
            'correlation_id' => $log->correlation_id,
            'transaction_reference' => $transaction->reference,
            'provider_reference' => $transaction->provider_reference,
            'status' => $status,
            'response_code' => $response?->status(),
            'latency' => $latencyMs,
            'environment' => $this->config->environment(),
        ]);
    }
}
