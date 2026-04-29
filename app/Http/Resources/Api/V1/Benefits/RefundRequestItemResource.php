<?php

namespace App\Http\Resources\Api\V1\Benefits;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RefundRequestItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $product = $this->orderDetail?->product;
        [$statusKey, $statusLabel] = $this->resolveItemStatus();

        return [
            'id' => (int) $this->id,
            'quantity' => (int) $this->quantity,
            'quantity_requested' => (int) ($this->quantity_requested ?? $this->quantity ?? 0),
            'quantity_approved' => $this->quantity_approved !== null ? (int) $this->quantity_approved : null,
            'item_status' => $statusKey,
            'item_status_label' => $statusLabel,
            'policy_name' => (string) (optional($this->appliedRefundPolicy)->name ?? ''),
            'product' => [
                'id' => $product?->id ? (int) $product->id : null,
                'name' => $product ? (string) $product->getTranslation('name') : translate('Product has been removed'),
                'thumbnail' => $product?->thumbnail_img ? api_asset($product->thumbnail_img) : '',
                'combinations' => $this->orderDetail?->variation ? filter_variation_combinations($this->orderDetail->variation->combinations) : [],
            ],
        ];
    }

    private function resolveItemStatus(): array
    {
        if (is_string($this->item_status) && $this->item_status !== '') {
            return match ($this->item_status) {
                'approved' => ['approved', translate('Approved')],
                'rejected' => ['rejected', translate('Rejected')],
                'processed' => ['processed', translate('Processed')],
                'cancelled' => ['cancelled', translate('Cancelled')],
                'under_review' => ['under_review', translate('Under Review')],
                default => ['pending', translate('pending')],
            };
        }

        return ['pending', translate('pending')];
    }
}
