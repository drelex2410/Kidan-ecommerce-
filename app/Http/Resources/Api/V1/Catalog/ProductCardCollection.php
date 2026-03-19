<?php

namespace App\Http\Resources\Api\V1\Catalog;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductCardCollection extends ResourceCollection
{
    public function toArray($request): array
    {
        return [
            'data' => $this->collection->map(fn ($product) => (new ProductCardResource($product))->toArray($request))->all(),
        ];
    }
}
