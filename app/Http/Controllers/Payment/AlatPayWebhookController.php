<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Jobs\Payments\ProcessAlatPayWebhookJob;
use App\Models\AlatPayWebhookLog;
use App\Services\Payments\AlatPay\AlatPayWebhookVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlatPayWebhookController extends Controller
{
    public function __construct(private readonly AlatPayWebhookVerifier $verifier)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $verified = $this->verifier->verify($request);

        if ($this->verifier->isDuplicate($verified['fingerprint'])) {
            return response()->json([
                'success' => true,
                'message' => 'Duplicate webhook ignored.',
            ]);
        }

        $webhookLog = AlatPayWebhookLog::query()->create([
            'correlation_id' => $verified['correlation_id'],
            'event_type' => data_get($verified['payload'], 'event'),
            'tenant_id' => data_get($verified['payload'], 'data.metadata.tenant_id')
                ?: data_get($verified['payload'], 'data.customer.metadata.tenant_id'),
            'reference' => data_get($verified['payload'], 'data.orderId'),
            'provider_reference' => data_get($verified['payload'], 'data.sessionId'),
            'fingerprint' => $verified['fingerprint'],
            'signature' => $verified['signature'],
            'timestamp_header' => $verified['timestamp_header'],
            'status' => 'queued',
            'headers' => $verified['headers'],
            'payload' => $verified['payload'],
            'received_at' => now(),
        ]);

        ProcessAlatPayWebhookJob::dispatch($webhookLog->id);

        return response()->json([
            'success' => true,
            'message' => 'Webhook accepted.',
            'correlation_id' => $verified['correlation_id'],
        ]);
    }
}
