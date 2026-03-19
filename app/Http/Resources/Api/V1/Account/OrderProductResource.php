<?php

namespace App\Http\Resources\Api\V1\Account;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->product ? (int) $this->product->id : null,
            'name' => $this->product ? (string) $this->product->getTranslation('name') : translate('Product has been removed'),
            'thumbnail' => $this->product && $this->product->thumbnail_img ? api_asset($this->product->thumbnail_img) : '',
            'combinations' => $this->variation ? filter_variation_combinations($this->variation->combinations) : [],
            'price' => (float) $this->price,
            'tax' => (float) $this->tax,
            'total' => (float) $this->total,
            'quantity' => (int) $this->quantity,
            'order_detail_id' => (int) $this->id,
            'product_variation_id' => $this->product_variation_id ? (int) $this->product_variation_id : null,
        ];
    }
}
