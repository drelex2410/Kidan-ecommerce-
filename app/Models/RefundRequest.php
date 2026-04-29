<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefundRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_PROCESSED = 'processed';
    public const STATUS_CANCELLED = 'cancelled';

    public const WORKFLOW_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_UNDER_REVIEW,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_PROCESSED,
        self::STATUS_CANCELLED,
    ];

    protected $guarded = [];

    protected $casts = [
        'requested_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'policy_snapshot' => 'array',
    ];

    public function refundRequestItems()
    {
        return $this->hasMany(RefundRequestItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shop() {
        return $this->belongsTo(Shop::class);
    }

    public function order() {
        return $this->belongsTo(Order::class);
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function workflowStatus(): string
    {
        if (in_array($this->status, self::WORKFLOW_STATUSES, true)) {
            return $this->status;
        }

        return match ((int) $this->admin_approval) {
            1 => self::STATUS_APPROVED,
            2 => self::STATUS_REJECTED,
            default => (int) $this->seller_approval === 1 ? self::STATUS_UNDER_REVIEW : self::STATUS_PENDING,
        };
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

    public function workflowStatusBadgeClass(): string
    {
        return match ($this->workflowStatus()) {
            self::STATUS_PENDING => 'badge-info',
            self::STATUS_UNDER_REVIEW => 'badge-warning',
            self::STATUS_APPROVED => 'badge-success',
            self::STATUS_REJECTED => 'badge-danger',
            self::STATUS_PROCESSED => 'badge-primary',
            self::STATUS_CANCELLED => 'badge-dark',
            default => 'badge-info',
        };
    }

    public function sellerStatusLabel(): string
    {
        return match ((int) $this->seller_approval) {
            1 => translate('Accepted'),
            2 => translate('Rejected'),
            default => translate('Pending'),
        };
    }

    public function sellerStatusBadgeClass(): string
    {
        return match ((int) $this->seller_approval) {
            1 => 'badge-success',
            2 => 'badge-danger',
            default => 'badge-info',
        };
    }

    public function isWorkflowFinal(): bool
    {
        return in_array($this->workflowStatus(), [
            self::STATUS_REJECTED,
            self::STATUS_PROCESSED,
            self::STATUS_CANCELLED,
        ], true);
    }
}
