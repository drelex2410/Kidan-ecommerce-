<?php

namespace App\Services\Checkout;

use App\Models\Cart;
use App\Models\Payment;

class CheckoutCartFinalizer
{
    public function clearByIds(?int $userId, ?string $tempUserId, array $cartItemIds): void
    {
        $cartItemIds = collect($cartItemIds)
            ->map(static fn ($id) => (int) $id)
            ->filter(static fn ($id) => $id > 0)
            ->values()
            ->all();

        if ($cartItemIds === []) {
            return;
        }

        $query = Cart::query()->whereIn('id', $cartItemIds);

        if ($userId) {
            $query->where('user_id', $userId);
        } elseif ($tempUserId) {
            $query->where('temp_user_id', $tempUserId);
        } else {
            return;
        }

        $query->delete();
    }

    public function clearForPayment(Payment $payment): void
    {
        $meta = is_array($payment->meta) ? $payment->meta : [];
        $cartItemIds = $meta['cart_item_ids'] ?? [];

        if (!is_array($cartItemIds)) {
            $cartItemIds = [$cartItemIds];
        }

        $this->clearByIds(
            $payment->user_id ? (int) $payment->user_id : null,
            isset($meta['temp_user_id']) ? (string) $meta['temp_user_id'] : null,
            $cartItemIds
        );
    }
}
