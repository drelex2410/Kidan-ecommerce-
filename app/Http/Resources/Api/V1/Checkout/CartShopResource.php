<?php

namespace App\Http\Resources\Api\V1\Checkout;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartShopResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $firstBanner = collect(explode(',', (string) $this->banners))->filter()->first();

        return [
            'id' => (int) $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'logo' => $this->logo ? api_asset($this->logo) : '',
            'banner' => $firstBanner ? api_asset($firstBanner) : '',
            'rating' => (float) ($this->rating ?? 0),
            'min_order' => (float) ($this->min_order ?? 0),
        ];
    }
}
