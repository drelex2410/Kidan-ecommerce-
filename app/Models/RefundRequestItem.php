<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefundRequestItem extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_PROCESSED = 'processed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $guarded = [];

    protected $casts = [
        'quantity_requested' => 'integer',
        'quantity_approved' => 'integer',
    ];

    public function refundRequest()
    {
        return $this->belongsTo(RefundRequest::class);
    }

    public function orderDetail()
    {
        return $this->belongsTo(OrderDetail::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function appliedRefundPolicy()
    {
        return $this->belongsTo(RefundPolicy::class, 'applied_refund_policy_id');
    }

    public function requestedQuantity(): int
    {
        return (int) ($this->quantity_requested ?? $this->quantity ?? 0);
    }

    public function approvedQuantity(): int
    {
        return (int) ($this->quantity_approved ?? 0);
    }

    public function workflowStatus(): string
    {
        if (in_array($this->item_status, RefundRequest::WORKFLOW_STATUSES, true)) {
            return $this->item_status;
        }

        return $this->refundRequest?->workflowStatus() ?? self::STATUS_PENDING;
    }

    public function workflowStatusLabel(): string
    {
        return match ($this->workflowStatus()) {
            self::STATUS_PENDING => translate('Pending'),
            self::STATUS_UNDER_REVIEW => translate('Under Review'),
            self::STATUS_APPROVED => translate('Approved'),
            self::STATUS_REJECTED => translate('Rejected'),
            self::STATUS_PROCESSED => translate('Processed'),
            self::STATUS_CANCELLED => translate('Cancelled'),
            default => translate('Pending'),
        };
    }
}
