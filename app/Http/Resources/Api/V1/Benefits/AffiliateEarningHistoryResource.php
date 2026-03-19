<?php

namespace App\Http\Resources\Api\V1\Benefits;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AffiliateEarningHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $order = $this->order_detail?->order;
        $product = $this->order_detail?->product;

        return [
            'referral_user' => (string) (optional($this->user)->name ?? ''),
            'amount' => (float) $this->amount,
            'order_id' => (string) ($this->order_id ?? $order?->code ?? ''),
            'referrel_type' => (string) ($this->affiliate_type ?? ''),
            'product' => $product ? (string) $product->getTranslation('name') : '',
            'date' => $this->created_at ? $this->created_at->format('d-m-Y') : null,
        ];
    }
}
