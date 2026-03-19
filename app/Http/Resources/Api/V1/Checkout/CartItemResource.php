<?php

namespace App\Http\Resources\Api\V1\Checkout;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $availableStock = $this->variation->current_stock ?? $this->variation->stock;
        $thumbnail = $this->variation->img
            ? api_asset($this->variation->img)
            : ($this->product->thumbnail_img ? api_asset($this->product->thumbnail_img) : '');

        return [
            'cart_id' => (int) $this->id,
            'product_id' => (int) $this->product_id,
            'shop_id' => (int) $this->product->shop_id,
            'earn_point' => (float) $this->product->earn_point,
            'variation_id' => (int) $this->product_variation_id,
            'name' => $this->product->name,
            'combinations' => filter_variation_combinations($this->variation->combinations),
            'thumbnail' => $thumbnail,
            'regular_price' => (float) variation_price($this->product, $this->variation),
            'dicounted_price' => (float) variation_discounted_price($this->product, $this->variation),
            'tax' => (float) product_variation_tax($this->product, $this->variation),
            'stock' => (int) $availableStock,
            'min_qty' => (int) $this->product->min_qty,
            'max_qty' => (int) $this->product->max_qty,
            'standard_delivery_time' => (int) $this->product->standard_delivery_time,
            'express_delivery_time' => (int) $this->product->express_delivery_time,
            'qty' => (int) $this->quantity,
            'is_digital' => (int) $this->product->digital,
            'for_pickup' => (int) $this->product->for_pickup,
        ];
    }
}
