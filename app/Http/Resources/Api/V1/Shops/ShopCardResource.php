<?php

namespace App\Http\Resources\Api\V1\Shops;

use App\Http\Resources\Api\V1\Catalog\CategoryCollection;
use App\Http\Resources\Api\V1\Catalog\ProductCardCollection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShopCardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'slug' => (string) $this->slug,
            'name' => (string) $this->name,
            'logo' => $this->logo ? api_asset($this->logo) : null,
            'banner' => $this->firstBanner(),
            'rating' => (double) ($this->rating ?? 0),
            'min_order' => (double) ($this->min_order ?? 0),
            'categories' => new CategoryCollection($this->categories ?? collect()),
            'top_3_products' => new ProductCardCollection($this->top_3_products ?? collect()),
            'reviews_count' => (int) ($this->reviews_count ?? 0),
            'products_count' => (int) ($this->products_count ?? 0),
            'since' => $this->created_at ? $this->created_at->format('d M, Y') : null,
            'isVarified' => (bool) ($this->verification_status == 1),
        ];
    }

    private function firstBanner(): ?string
    {
        $banner = collect(explode(',', (string) ($this->banners ?? '')))
            ->map(fn ($value) => trim($value))
            ->first(fn ($value) => $value !== '');

        return $banner ? api_asset($banner) : null;
    }
}
