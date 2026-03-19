<?php

namespace App\Http\Resources\Api\V1\Account;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WishlistProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $photos = collect(explode(',', (string) $this->photos))
            ->map(fn ($item) => trim($item))
            ->filter()
            ->map(fn ($item) => api_asset($item))
            ->unique()
            ->values()
            ->all();

        return [
            'id' => (int) $this->id,
            'name' => (string) $this->name,
            'slug' => (string) $this->slug,
            'thumbnail_image' => $this->thumbnail_img ? api_asset($this->thumbnail_img) : '',
            'photos' => $photos,
            'base_price' => (float) product_base_price($this->resource),
            'base_discounted_price' => (float) product_discounted_base_price($this->resource),
            'stock' => (bool) $this->stock,
            'unit' => (string) ($this->unit ?? ''),
            'min_qty' => (int) ($this->min_qty ?? 1),
            'max_qty' => (int) ($this->max_qty ?? 0),
            'rating' => (float) ($this->rating ?? 0),
            'is_variant' => (int) ($this->is_variant ?? 0),
            'variations' => $this->relationLoaded('variations') ? $this->variations->toArray() : [],
        ];
    }
}
