<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AlatPayRefund extends Model
{
    use SoftDeletes;

    protected $table = 'alatpay_refunds';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'provider_payload' => 'array',
        'metadata' => 'array',
        'requested_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function alatPayTransaction(): BelongsTo
    {
        return $this->belongsTo(AlatPayTransaction::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
