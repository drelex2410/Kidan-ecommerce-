<?php

namespace App\Http\Resources\Api\V1\Catalog;

use Illuminate\Http\Resources\Json\ResourceCollection;

class FilterAttributeCollection extends ResourceCollection
{
    public function toArray($request): array
    {
        return [
            'data' => $this->collection->map(function ($attribute) {
                return [
                    'id' => (int) $attribute->id,
                    'name' => $attribute->getTranslation('name'),
                    'values' => [
                        'data' => $attribute->attribute_values->map(function ($value) {
                            return [
                                'id' => (int) $value->id,
                                'name' => $value->getTranslation('name'),
                            ];
                        })->values()->all(),
                    ],
                ];
            })->all(),
        ];
    }
}
