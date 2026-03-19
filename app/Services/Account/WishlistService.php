<?php

namespace App\Services\Account;

use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Database\Eloquent\Collection;

class WishlistService
{
    public function listForUser(User $user): Collection
    {
        return Product::query()
            ->with('variations')
            ->whereIn('id', Wishlist::query()->where('user_id', $user->id)->pluck('product_id'))
            ->latest('id')
            ->get();
    }

    public function add(User $user, int $productId): Product
    {
        Wishlist::query()->updateOrCreate([
            'user_id' => $user->id,
            'product_id' => $productId,
        ]);

        return Product::query()->with('variations')->findOrFail($productId);
    }

    public function remove(User $user, int $productId): void
    {
        Wishlist::query()
            ->where('user_id', $user->id)
            ->where('product_id', $productId)
            ->delete();
    }
}
