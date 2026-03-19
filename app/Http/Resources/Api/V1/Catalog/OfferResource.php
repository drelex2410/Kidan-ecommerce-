<?php

namespace App\Http\Resources\Api\V1\Catalog;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class OfferResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'title' => $this->title,
            'slug' => $this->slug,
            'banner' => $this->banner ? api_asset($this->banner) : null,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
        ];
    }
}
