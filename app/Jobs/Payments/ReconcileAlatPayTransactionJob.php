<?php

namespace App\Jobs\Payments;

use App\Models\AlatPayTransaction;
use App\Services\Payments\AlatPay\AlatPayService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ReconcileAlatPayTransactionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 4;

    public array $backoff = [60, 300, 900];

    public function __construct(private readonly int $transactionId)
    {
    }

    public function handle(AlatPayService $alatPayService): void
    {
        $transaction = AlatPayTransaction::query()->find($this->transactionId);
        if (!$transaction) {
            return;
        }

        $alatPayService->verify($transaction);
    }
}
