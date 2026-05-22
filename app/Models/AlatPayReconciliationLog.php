<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlatPayReconciliationLog extends Model
{
    protected $table = 'alatpay_reconciliation_logs';

    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
        'reconciled_at' => 'datetime',
        'next_retry_at' => 'datetime',
    ];

    public function alatPayTransaction(): BelongsTo
    {
        return $this->belongsTo(AlatPayTransaction::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
