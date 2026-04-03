<?php

namespace App\Http\Resources\Api\V1\Catalog;

use Illuminate\Http\Resources\Json\ResourceCollection;

class AllCategoryCollection extends ResourceCollection
{
    public function toArray($request): array
    {
        return [
            'data' => (new CategoryCollection($this->collection))
                ->toArray($request)['data'],
        ];
    }
}
