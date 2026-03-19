<?php

namespace App\Http\Resources\Api\V1\Account;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FollowedShopResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $firstBanner = collect(explode(',', (string) $this->banners))
            ->map(fn ($banner) => trim($banner))
            ->first(fn ($banner) => $banner !== '');

        return [
            'id' => (int) $this->id,
            'name' => (string) $this->name,
            'slug' => (string) ($this->slug ?? ''),
            'logo' => $this->logo ? api_asset($this->logo) : '',
            'banner' => $firstBanner ? api_asset($firstBanner) : '',
            'rating' => (float) ($this->rating ?? 0),
            'min_order' => (float) ($this->min_order ?? 0),
            'verification_status' => (bool) ($this->verification_status ?? false),
        ];
    }
}
