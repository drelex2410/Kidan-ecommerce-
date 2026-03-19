<?php

namespace App\Http\Services;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Str;

class AdminShopService
{
    public function ensureShopForUser(?User $user): ?Shop
    {
        if (!$user || !in_array($user->user_type, ['admin', 'staff'], true)) {
            return optional($user)->shop;
        }

        $shop = $this->ensureAdminShop();

        if (!$shop) {
            return null;
        }

        $this->syncUserShop($user, $shop);

        return $shop;
    }

    public function ensureAdminShop(): ?Shop
    {
        $admin = User::where('user_type', 'admin')->orderBy('id')->first();

        if (!$admin) {
            return null;
        }

        $shop = Shop::where('user_id', $admin->id)->orderBy('id')->first();

        if (!$shop) {
            $shop = $this->createAdminShop($admin);
        }

        $this->syncUserShop($admin, $shop);

        return $shop;
    }

    public function syncUserShop(User $user, Shop $shop): void
    {
        if ((int) $user->shop_id !== (int) $shop->id) {
            $user->shop_id = $shop->id;
            $user->save();
        }

        $user->setRelation('shop', $shop);
    }

    private function createAdminShop(User $admin): Shop
    {
        $shop = new Shop;
        $shop->user_id = $admin->id;
        $shop->name = $this->resolveAdminShopName();
        $shop->slug = $this->generateUniqueSlug($shop->name);
        $shop->approval = 1;
        $shop->published = 1;
        $shop->min_order = 0;
        $shop->save();

        return $shop;
    }

    private function resolveAdminShopName(): string
    {
        $name = trim((string) config('app.name'));

        return $name !== '' ? $name : 'Inhouse Shop';
    }

    private function generateUniqueSlug(string $name): string
    {
        $slugBase = Str::slug($name, '-');
        $slugBase = $slugBase !== '' ? $slugBase : 'inhouse-shop';
        $slug = $slugBase;
        $counter = 2;

        while (Shop::where('slug', $slug)->exists()) {
            $slug = $slugBase . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
