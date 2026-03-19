<?php

namespace App\Http\Resources\Api\V1\Delivery;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryDashboardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'deliveryboy' => [
                'total_collection' => (double) ($this['deliveryboy']['total_collection'] ?? 0),
                'total_earning' => (double) ($this['deliveryboy']['total_earning'] ?? 0),
            ],
            'total_complete_delivery' => (int) ($this['total_complete_delivery'] ?? 0),
            'total_pending_delivery' => (int) ($this['total_pending_delivery'] ?? 0),
        ];
    }
}
