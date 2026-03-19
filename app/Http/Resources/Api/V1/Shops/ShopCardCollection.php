<?php

namespace App\Http\Resources\Api\V1\Shops;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ShopCardCollection extends ResourceCollection
{
    public function toArray($request): array
    {
        return [
            'data' => $this->collection->map(fn ($shop) => (new ShopCardResource($shop))->toArray($request))->all(),
        ];
    }
}
