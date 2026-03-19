<?php

namespace App\Http\Resources\Api\V1\Delivery;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryLedgerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'code' => (string) optional(optional($this->order)->combined_order)->code,
            'date' => $this->created_at ? $this->created_at->format('d-m-Y h:i A') : null,
            'amount' => (double) ($this->collection ?? $this->earning ?? 0),
        ];
    }
}
