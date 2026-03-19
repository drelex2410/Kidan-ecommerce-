<?php

namespace App\Http\Resources\Api\V1\Account;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'code' => (string) $this->code,
            'banner' => $this->banner ? api_asset($this->banner) : '',
        ];
    }
}
