<?php

namespace App\Http\Resources\Api\V1\Shops;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ShopCouponCollection extends ResourceCollection
{
    public function toArray($request): array
    {
        return [
            'data' => $this->collection->map(function ($coupon) {
                return [
                    'code' => (string) $coupon->code,
                    'banner' => $coupon->banner ? api_asset($coupon->banner) : null,
                ];
            })->all(),
        ];
    }
}
