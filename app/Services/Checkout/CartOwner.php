<?php

namespace App\Services\Checkout;

use App\Models\Cart;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class CartOwner
{
    public function query(?User $user, ?string $tempUserId): Builder
    {
        return Cart::query()->where(function (Builder $builder) use ($user, $tempUserId): void {
            if ($user) {
                $builder->where('user_id', $user->id);
            } elseif ($tempUserId) {
                $builder->where('temp_user_id', $tempUserId);
            } else {
                $builder->whereRaw('1 = 0');
            }
        });
    }

    public function owns(Cart $cart, ?User $user, ?string $tempUserId): bool
    {
        if ($user) {
            return (int) $cart->user_id === (int) $user->id;
        }

        return $tempUserId !== null && (string) $cart->temp_user_id === (string) $tempUserId;
    }
}
