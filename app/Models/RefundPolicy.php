<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefundPolicy extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'refund_window_days' => 'integer',
        'allowed_order_statuses' => 'array',
        'allow_partial_refund' => 'boolean',
        'refund_shipping_fee' => 'boolean',
        'requires_admin_approval' => 'boolean',
        'requires_reason' => 'boolean',
        'requires_evidence' => 'boolean',
        'exclude_opened_items' => 'boolean',
        'exclude_digital_products' => 'boolean',
        'exclude_discounted_products' => 'boolean',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function refundRequestItems()
    {
        return $this->hasMany(RefundRequestItem::class, 'applied_refund_policy_id');
    }
}
