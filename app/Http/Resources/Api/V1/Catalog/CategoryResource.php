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
        ];
    }
}
