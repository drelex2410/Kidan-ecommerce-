<?php

namespace App\Http\Resources\Api\V1\Benefits;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClubPointHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'user_id' => (int) $this->user_id,
            'points' => (int) $this->points,
            'convert_status' => (int) $this->convert_status,
            'created_at' => $this->created_at ? $this->created_at->format('d-m-Y') : null,
            'order_code' => optional($this->combined_order)->code,
        ];
    }
}
