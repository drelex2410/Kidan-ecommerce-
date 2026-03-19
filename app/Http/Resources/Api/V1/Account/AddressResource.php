<?php

namespace App\Http\Resources\Api\V1\Account;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'user_id' => (int) $this->user_id,
            'address' => (string) $this->address,
            'country' => (string) $this->country,
            'state' => (string) $this->state,
            'city' => (string) $this->city,
            'postal_code' => (string) ($this->postal_code ?? ''),
            'phone' => (string) ($this->phone ?? ''),
            'default_shipping' => (bool) $this->default_shipping,
            'default_billing' => (bool) $this->default_billing,
        ];
    }
}
