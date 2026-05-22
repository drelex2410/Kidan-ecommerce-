<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AlatPayTransaction extends Model
{
    use SoftDeletes;

    protected $table = 'alatpay_transactions';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'instructions' => 'array',
        'provider_payload' => 'array',
        'metadata' => 'array',
        'expires_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'last_reconciled_at' => 'datetime',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function combinedOrder(): BelongsTo
    {
        return $this->belongsTo(CombinedOrder::class);
    }

    public function webhookLogs(): HasMany
    {
        return $this->hasMany(AlatPayWebhookLog::class);
    }

    public function reconciliationLogs(): HasMany
    {
        return $this->hasMany(AlatPayReconciliationLog::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(AlatPayRefund::class);
    }
}
