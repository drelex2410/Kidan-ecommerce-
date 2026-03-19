<?php

namespace App\Services\Checkout;

use App\Models\Address;
use App\Models\City;
use App\Models\User;

class ShippingService
{
    public function quote(User $user, int $addressId, int $shopCount = 1): array
    {
        $address = Address::query()->find($addressId);

        if (!$address || (int) $address->user_id !== (int) $user->id) {
            throw new CheckoutException('The selected address is invalid.', 403);
        }

        $zone = City::query()->with('zone')->find($address->city_id)?->zone;

        if (!$zone) {
            return [
                'success' => false,
                'standard_delivery_cost' => 0,
                'express_delivery_cost' => 0,
                'totals' => [
                    'shipping_total_standard' => 0,
                    'shipping_total_express' => 0,
                ],
            ];
        }

        $standard = (float) $zone->standard_delivery_cost;
        $express = (float) $zone->express_delivery_cost;

        return [
            'success' => true,
            'standard_delivery_cost' => $standard,
            'express_delivery_cost' => $express,
            'totals' => [
                'shipping_total_standard' => round($standard * max(1, $shopCount), 2),
                'shipping_total_express' => round($express * max(1, $shopCount), 2),
            ],
        ];
    }
}
