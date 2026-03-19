<?php

namespace App\Http\Resources\Api\V1\Checkout;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CartItemCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => CartItemResource::collection($this->collection),
        ];
    }
}
