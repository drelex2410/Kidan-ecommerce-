<?php

namespace App\Http\Resources\Api\V1\Catalog;

use Illuminate\Http\Resources\Json\ResourceCollection;

class AllCategoryCollection extends ResourceCollection
{
    public function toArray($request): array
    {
        return [
            'data' => $this->collection->map(function ($category) use ($request) {
                return [
                    'id' => (int) $category->id,
                    'name' => $category->getTranslation('name'),
                    'banner' => $category->banner ? api_asset($category->banner) : null,
                    'icon' => $category->icon ? api_asset($category->icon) : null,
                    'slug' => $category->slug,
                    'children' => new CategoryCollection($category->all_children ?? collect()),
                ];
            })->all(),
        ];
    }
}
