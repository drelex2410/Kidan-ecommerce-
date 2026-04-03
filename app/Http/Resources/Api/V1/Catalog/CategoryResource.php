<?php

namespace App\Http\Resources\Api\V1\Catalog;

use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => (int) $this->id,
            'name' => $this->getTranslation('name'),
            'banner' => $this->banner ? api_asset($this->banner) : null,
            'icon' => $this->icon ? api_asset($this->icon) : null,
            'slug' => $this->slug,
            'featured' => (int) ($this->featured ?? 0),
            'parent_id' => (int) ($this->parent_id ?: 0),
            'level' => (int) ($this->level ?? 0),
            'order_level' => is_null($this->order_level) ? null : (int) $this->order_level,
            'children' => $this->children($request),
        ];
    }

    private function children($request): array
    {
        if (! $this->relationLoaded('categories')) {
            return [];
        }

        return (new CategoryCollection($this->categories))
            ->toArray($request)['data'];
    }
}
