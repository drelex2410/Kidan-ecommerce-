<?php

namespace App\Http\Resources\Api\V1\Catalog;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ShopSuggestionCollection extends ResourceCollection
{
    public function toArray($request): array
    {
        return [
            'data' => $this->collection->map(function ($shop) {
                return [
                    'id' => (int) $shop->id,
                    'slug' => $shop->slug,
                    'name' => $shop->name,
                    'logo' => $shop->logo ? api_asset($shop->logo) : null,
                    'banner' => ($banner = collect(explode(',', (string) $shop->banners))->filter()->first()) ? api_asset($banner) : null,
                    'rating' => (double) ($shop->rating ?? 0),
                    'min_order' => (double) ($shop->min_order ?? 0),
                ];
            })->all(),
        ];
    }
}
