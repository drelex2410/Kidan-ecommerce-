<?php

namespace App\Services\Shops;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ShopRegistrationService
{
    public function register(array $payload): array
    {
        $existing = User::query()
            ->where('phone', $payload['phone'])
            ->orWhere('email', $payload['email'])
            ->first();

        if ($existing) {
            throw new HttpException(422, 'User already exists with this email or phone.');
        }

        $user = new User();
        $user->name = $payload['name'];
        $user->email = $payload['email'];
        $user->phone = $payload['phone'];
        $user->user_type = 'seller';
        $user->password = Hash::make($payload['password']);
        $user->email_verified_at = now();
        $user->save();

        $shop = new Shop();
        $shop->user_id = $user->id;
        $shop->approval = 1;
        $shop->verification_status = 0;
        $shop->name = $payload['shopName'];
        $shop->slug = $this->uniqueSlug($payload['shopName']);
        $shop->phone = $payload['shopPhone'];
        $shop->address = $payload['shopAddress'];
        $shop->save();

        $user->shop_id = $shop->id;
        $user->save();

        return [
            'success' => true,
            'message' => translate('Thanks for registering your shop.'),
        ];
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name, '-') ?: 'shop';
        $slug = $base;
        $counter = 2;

        while (Shop::query()->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $counter++;
        }

        return $slug;
    }
}
