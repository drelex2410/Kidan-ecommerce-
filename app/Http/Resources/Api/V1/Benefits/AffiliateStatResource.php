<?php

namespace App\Http\Resources\Api\V1\Benefits;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AffiliateStatResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'click' => (int) ($this['click'] ?? 0),
            'item' => (int) ($this['item'] ?? 0),
            'delivered' => (int) ($this['delivered'] ?? 0),
            'cancel' => (int) ($this['cancel'] ?? 0),
        ];
    }
}
