<?php

namespace App\Http\Resources\Api\V1\Delivery;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'grand_total' => (double) ($this->grand_total ?? 0),
            'delivery_status' => (string) ($this->delivery_status ?? ''),
            'payment_status' => (string) ($this->payment_status ?? ''),
            'payment_type' => (string) ($this->payment_type ?? ''),
            'created_at' => $this->created_at ? $this->created_at->format('d-m-Y h:i A') : null,
            'delivery_history_date' => $this->delivery_history_date ? date('d-m-Y h:i A', strtotime((string) $this->delivery_history_date)) : null,
            'combined_order' => [
                'code' => (string) optional($this->combined_order)->code,
            ],
        ];
    }
}
