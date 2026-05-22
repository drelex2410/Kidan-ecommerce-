<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlatPayWebhookLog extends Model
{
    protected $table = 'alatpay_webhook_logs';

    protected $guarded = [];

    protected $casts = [
        'headers' => 'array',
        'payload' => 'array',
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
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
