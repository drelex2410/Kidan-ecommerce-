<?php

namespace App\Http\Resources\Api\V1\Shops;

use App\Http\Resources\Api\V1\Catalog\CategoryCollection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShopDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'slug' => (string) $this->slug,
            'name' => (string) $this->name,
            'logo' => $this->logo ? api_asset($this->logo) : null,
            'banners' => $this->banners(),
            'products_banners' => $this->sectionBanners($this->products_banners),
            'categories' => new CategoryCollection($this->categories ?? collect()),
            'rating' => (double) ($this->rating ?? 0),
            'reviews_count' => (int) ($this->reviews_count ?? 0),
        ];
    }

    private function banners(): array
    {
        return collect(explode(',', (string) ($this->banners ?? '')))
            ->map(fn ($value) => trim($value))
            ->filter()
            ->map(fn ($value) => api_asset($value))
            ->values()
            ->all();
    }

    private function sectionBanners(?string $value): array
    {
        $decoded = json_decode((string) $value, true);
        if (!is_array($decoded)) {
            return [];
        }

        return collect($decoded)
            ->map(function ($banner) {
                if (is_array($banner)) {
                    return [
                        'img' => !empty($banner['img']) ? api_asset($banner['img']) : '',
                        'link' => $banner['link'] ?? null,
                    ];
                }

                if (is_string($banner) && trim($banner) !== '') {
                    return [
                        'img' => api_asset($banner),
                        'link' => null,
                    ];
                }

                return null;
            })
            ->filter()
            ->values()
            ->all();
    }
}
