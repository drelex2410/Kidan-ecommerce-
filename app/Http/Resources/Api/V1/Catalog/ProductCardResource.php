<?php

namespace App\Http\Resources\Api\V1\Catalog;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductCardResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => (int) $this->id,
            'name' => $this->getTranslation('name'),
            'slug' => $this->slug,
            'thumbnail_image' => $this->thumbnail_img ? api_asset($this->thumbnail_img) : null,
            'photos' => collect(explode(',', (string) $this->photos))
                ->filter()
                ->map(fn ($photo) => api_asset(trim($photo)))
                ->values()
                ->all(),
            'base_price' => (double) product_base_price($this->resource),
            'base_discounted_price' => (double) product_discounted_base_price($this->resource),
            'stock' => (int) $this->stock,
            'unit' => $this->getTranslation('unit'),
            'min_qty' => (int) $this->min_qty,
            'max_qty' => (int) $this->max_qty,
            'rating' => (double) $this->rating,
            'earn_point' => (float) $this->earn_point,
            'is_variant' => (int) $this->is_variant,
            'variations' => $this->variations->map(function ($variation) {
                return [
                    'id' => (int) $variation->id,
                    'code' => $variation->code === null ? null : array_values(array_filter(explode('/', (string) $variation->code))),
                    'img' => $variation->img,
                    'image' => $variation->img ? api_asset($variation->img) : null,
                    'price' => variation_discounted_price($this->resource, $variation),
                    'stock' => (int) $variation->stock,
                    'current_stock' => (int) ($variation->current_stock ?? $variation->stock),
                ];
            })->values()->all(),
            'is_digital' => (bool) ($this->digital == 1),
        ];
    }
}
