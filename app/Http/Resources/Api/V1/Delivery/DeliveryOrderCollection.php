<?php

namespace App\Http\Resources\Api\V1\Delivery;

use Illuminate\Http\Resources\Json\ResourceCollection;

class DeliveryOrderCollection extends ResourceCollection
{
    public function toArray($request): array
    {
        return [
            'data' => $this->collection->map(fn ($order) => (new DeliveryOrderResource($order))->toArray($request))->all(),
        ];
    }
}
