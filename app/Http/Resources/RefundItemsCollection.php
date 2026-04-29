<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RefundItemsCollection extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $product = $this->orderDetail->product;
        [$statusKey, $statusLabel] = $this->resolveItemStatus();
        return [
            'id' => $this->id,
            'quantity' => $this->quantity,
            'quantity_requested' => (int) ($this->quantity_requested ?? $this->quantity ?? 0),
            'quantity_approved' => $this->quantity_approved !== null ? (int) $this->quantity_approved : null,
            'item_status' => $statusKey,
            'item_status_label' => $statusLabel,
            'policy_name' => (string) (optional($this->appliedRefundPolicy)->name ?? ''),
            'product' => [
                'id' => $product ? $product->id : null,
                'name' => $product ? $product->getTranslation('name') : translate('Product has been removed'),
                'thumbnail' => $product? api_asset($product->thumbnail_img) : '',
                'combinations' => $this->orderDetail->variation ? filter_variation_combinations($this->orderDetail->variation->combinations) : [],
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
