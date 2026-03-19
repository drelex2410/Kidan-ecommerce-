<?php

namespace App\Http\Resources\Api\V1\Account;

use App\Http\Resources\Shipping\PickupPointResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderPackageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'code' => (string) $this->code,
            'shop' => [
                'name' => (string) (optional($this->shop)->name ?? translate('Shop not found')),
                'slug' => (string) (optional($this->shop)->slug ?? ''),
            ],
            'payment_type' => (string) ($this->payment_type ?? ''),
            'manual_payment' => (bool) ($this->manual_payment ?? false),
            'manual_payment_data' => $this->manual_payment_data ? json_decode((string) $this->manual_payment_data, true) : null,
            'delivery_type' => $this->delivery_type,
            'type_of_delivery' => $this->type_of_delivery,
            'pickup_point' => $this->pickupPoint ? new PickupPointResource($this->pickupPoint) : null,
            'delivery_status' => (string) ($this->delivery_status ?? ''),
            'payment_status' => (string) ($this->payment_status ?? ''),
            'coupon_discount' => (float) ($this->coupon_discount ?? 0),
            'shipping_cost' => (float) ($this->shipping_cost ?? 0),
            'grand_total' => (float) ($this->grand_total ?? 0),
            'subtotal' => (float) ($this->orderDetails->sum('total') - $this->calculateTax()),
            'tax' => (float) $this->calculateTax(),
            'products' => [
                'data' => OrderProductResource::collection($this->orderDetails)->resolve(),
            ],
            'created_at' => $this->created_at ? strtotime((string) $this->created_at) : null,
            'has_refund_request' => $this->relationLoaded('refundRequests') ? $this->refundRequests->count() > 0 : false,
            'courier_name' => $this->courier_name,
            'tracking_number' => $this->tracking_number,
            'tracking_url' => $this->tracking_url,
        ];
    }

    private function calculateTax(): float
    {
        return (float) $this->orderDetails->sum(function ($item) {
            return ((float) $item->tax) * ((int) $item->quantity);
        });
    }
}
