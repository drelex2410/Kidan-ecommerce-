<?php

namespace App\Http\Resources\Api\V1\Account;

use App\Http\Resources\Api\V1\Auth\AuthUserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CombinedOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'code' => (string) $this->code,
            'user' => new AuthUserResource($this->user),
            'shipping_address' => $this->decodeAddress($this->shipping_address),
            'billing_address' => $this->decodeAddress($this->billing_address),
            'grand_total' => (float) ($this->grand_total ?? 0),
            'orders' => OrderPackageResource::collection($this->orders)->resolve(),
            'date' => $this->created_at ? $this->created_at->toFormattedDateString() : null,
        ];
    }

    private function decodeAddress(mixed $value): ?array
    {
        if (is_array($value)) {
            return $value;
        }

        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : null;
    }
}
