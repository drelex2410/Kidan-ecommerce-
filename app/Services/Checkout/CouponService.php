<?php

namespace App\Services\Checkout;

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\User;
use Illuminate\Support\Collection;

class CouponService
{
    public function __construct(
        private readonly CartService $cartService
    ) {
    }

    public function apply(User $user, string $couponCode, ?int $shopId, array $cartItemIds): array
    {
        $coupon = Coupon::query()->where('code', $couponCode)->first();

        if (!$coupon || !$this->isActive($coupon)) {
            throw new CheckoutException('The coupon is invalid.');
        }

        if ($shopId !== null && $coupon->shop_id !== null && (int) $coupon->shop_id !== $shopId) {
            throw new CheckoutException('The coupon is invalid for this shop.');
        }

        if (CouponUsage::query()->where('user_id', $user->id)->where('coupon_id', $coupon->id)->exists()) {
            throw new CheckoutException("You've already used the coupon.");
        }

        $cartItems = $this->cartService->selectedItemsForUser($user, $cartItemIds);

        if ($cartItems->isEmpty()) {
            throw new CheckoutException('Please select cart products before applying a coupon.');
        }

        if ($shopId !== null) {
            $cartItems = $cartItems->filter(fn ($item) => (int) $item->product->shop_id === $shopId)->values();
        }

        if ($cartItems->isEmpty()) {
            throw new CheckoutException('The selected cart items are invalid for this coupon.');
        }

        $details = $this->decodedDetails($coupon);

        if ($coupon->type === 'cart_base') {
            $subtotal = $cartItems->sum(fn ($item) => variation_discounted_price($item->product, $item->variation, false) * $item->quantity);
            $minBuy = (float) data_get($details, 'min_buy', 0);

            if ($subtotal < $minBuy) {
                throw new CheckoutException('Minimum order total of ' . format_price($minBuy) . ' is required to use this coupon code');
            }
        } elseif ($coupon->type === 'product_base') {
            $applicableProductIds = collect($details)->pluck('product_id')->map(fn ($id) => (int) $id)->all();
            $applicable = $cartItems->contains(fn ($item) => in_array((int) $item->product_id, $applicableProductIds, true));

            if (!$applicable) {
                throw new CheckoutException('This coupon code is no applicable for your cart products.');
            }
        }

        $discountAmount = $this->calculateDiscount($coupon, $cartItems);
        $taxTotal = $cartItems->sum(fn ($item) => product_variation_tax($item->product, $item->variation) * $item->quantity);
        $subtotal = $cartItems->sum(fn ($item) => variation_discounted_price($item->product, $item->variation, false) * $item->quantity);

        return [
            'coupon' => $coupon,
            'coupon_details' => [
                'coupon_type' => $coupon->type,
                'discount' => (float) $coupon->discount,
                'discount_type' => $coupon->discount_type,
                'conditions' => $details,
            ],
            'discount_amount' => round($discountAmount, 2),
            'totals' => [
                'subtotal' => round($subtotal, 2),
                'tax_total' => round($taxTotal, 2),
                'discount_total' => round($discountAmount, 2),
                'grand_total' => round(($subtotal + $taxTotal) - $discountAmount, 2),
            ],
        ];
    }

    public function calculateDiscount(Coupon $coupon, Collection $cartItems): float
    {
        $discount = 0.0;
        $details = $this->decodedDetails($coupon);

        if ($coupon->type === 'cart_base') {
            $subtotal = $cartItems->sum(fn ($item) => variation_discounted_price($item->product, $item->variation, false) * $item->quantity);

            if ($coupon->discount_type === 'percent') {
                $discount = ($subtotal * (float) $coupon->discount) / 100;
                $maxDiscount = (float) data_get($details, 'max_discount', $discount);
                $discount = min($discount, $maxDiscount);
            } else {
                $discount = (float) $coupon->discount;
            }
        } elseif ($coupon->type === 'product_base') {
            $applicableProductIds = collect($details)->pluck('product_id')->map(fn ($id) => (int) $id)->all();

            foreach ($cartItems as $item) {
                if (!in_array((int) $item->product_id, $applicableProductIds, true)) {
                    continue;
                }

                if ($coupon->discount_type === 'percent') {
                    $discount += (((float) variation_discounted_price($item->product, $item->variation, false) * (float) $coupon->discount) / 100) * $item->quantity;
                } else {
                    $discount += (float) $coupon->discount * $item->quantity;
                }
            }
        }

        return $discount;
    }

    public function resolveCheckoutCoupons(User $user, array $couponCodes, Collection $cartItems): array
    {
        $resolved = [];

        foreach (array_filter($couponCodes) as $couponCode) {
            $coupon = Coupon::query()->where('code', $couponCode)->first();

            if (!$coupon || !$this->isActive($coupon)) {
                throw new CheckoutException('The coupon is invalid.');
            }

            if (CouponUsage::query()->where('user_id', $user->id)->where('coupon_id', $coupon->id)->exists()) {
                throw new CheckoutException("You've already used the coupon.");
            }

            $shopItems = $cartItems->filter(fn ($item) => (int) $item->product->shop_id === (int) $coupon->shop_id)->values();

            if ($shopItems->isEmpty()) {
                throw new CheckoutException('The coupon is invalid for this shop.');
            }

            $applyResult = $this->apply($user, $couponCode, $coupon->shop_id ? (int) $coupon->shop_id : null, $shopItems->pluck('id')->all());

            $resolved[(int) $coupon->shop_id] = [
                'coupon' => $coupon,
                'discount' => $applyResult['discount_amount'],
            ];
        }

        return $resolved;
    }

    private function isActive(Coupon $coupon): bool
    {
        $today = strtotime(date('d-m-Y'));

        return $coupon->start_date <= $today && $coupon->end_date >= $today;
    }

    private function decodedDetails(Coupon $coupon): mixed
    {
        return json_decode((string) $coupon->details, false, 512, JSON_THROW_ON_ERROR);
    }
}
