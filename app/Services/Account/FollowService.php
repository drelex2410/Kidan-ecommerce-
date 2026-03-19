<?php

namespace App\Services\Account;

use App\Models\Shop;
use App\Models\ShopFollower;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class FollowService
{
    public function listForUser(User $user): Collection
    {
        return $user->followed_shops()->get();
    }

    public function follow(User $user, int $shopId): void
    {
        Shop::query()->findOrFail($shopId);

        ShopFollower::query()->updateOrCreate([
            'user_id' => $user->id,
            'shop_id' => $shopId,
        ]);
    }

    public function unfollow(User $user, int $shopId): void
    {
        ShopFollower::query()
            ->where('user_id', $user->id)
            ->where('shop_id', $shopId)
            ->delete();
    }
}
