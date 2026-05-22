<?php

namespace App\Jobs\Payments;

use App\Models\AlatPayWebhookLog;
use App\Services\Payments\AlatPay\AlatPayService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessAlatPayWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public array $backoff = [60, 180, 600, 1800];

    public function __construct(private readonly int $webhookLogId)
    {
    }

    public function handle(AlatPayService $alatPayService): void
    {
        $webhookLog = AlatPayWebhookLog::query()->find($this->webhookLogId);
        if (!$webhookLog) {
            return;
        }

        $alatPayService->processWebhookLog($webhookLog);
    }

    public function failed(\Throwable $exception): void
    {
        $webhookLog = AlatPayWebhookLog::query()->find($this->webhookLogId);
        if ($webhookLog) {
            $webhookLog->update([
                'status' => 'failed',
                'attempts' => $webhookLog->attempts + 1,
                'error_message' => $exception->getMessage(),
            ]);
        }

        Log::error('ALATPay webhook job failed.', [
            'gateway' => 'alatpay',
            'webhook_log_id' => $this->webhookLogId,
            'exception_message' => $exception->getMessage(),
        ]);
    }
}
