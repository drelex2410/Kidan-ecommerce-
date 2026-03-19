<?php

namespace App\Http\Resources\Api\V1\Benefits;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RefundRequestItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $product = $this->orderDetail?->product;

        return [
            'id' => (int) $this->id,
            'quantity' => (int) $this->quantity,
            'product' => [
                'id' => $product?->id ? (int) $product->id : null,
                'name' => $product ? (string) $product->getTranslation('name') : translate('Product has been removed'),
                'thumbnail' => $product?->thumbnail_img ? api_asset($product->thumbnail_img) : '',
                'combinations' => $this->orderDetail?->variation ? filter_variation_combinations($this->orderDetail->variation->combinations) : [],
            ],
        ];
    }
}
