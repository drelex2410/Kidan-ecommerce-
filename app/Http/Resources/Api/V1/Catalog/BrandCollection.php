<?php

namespace App\Http\Resources\Api\V1\Catalog;

use Illuminate\Http\Resources\Json\ResourceCollection;

class BrandCollection extends ResourceCollection
{
    public function toArray($request): array
    {
        return [
            'data' => $this->collection->map(fn ($brand) => (new BrandResource($brand))->toArray($request))->all(),
        ];
    }
}
