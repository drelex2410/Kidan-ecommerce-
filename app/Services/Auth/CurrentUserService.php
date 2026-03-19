<?php

namespace App\Services\Auth;

use App\Models\User;

class CurrentUserService
{
    public function payloadFor(User $user): array
    {
        $user->loadMissing('followed_shops', 'addresses');

        return [
            'is_authenticated' => true,
            'user' => $user,
            'followed_shops' => $user->followed_shops->pluck('id')->values()->all(),
            'permissions' => [
                'conversation_system' => (bool) ((int) get_setting('conversation_system', 0)),
                'wallet_system' => (bool) ((int) get_setting('wallet_system', 0)),
                'club_point' => (bool) ((int) get_setting('club_point', 0)),
            ],
            'profile' => [
                'has_default_shipping_address' => $user->addresses->contains(fn ($address) => (bool) ($address->set_default ?? false) || (bool) ($address->default_shipping ?? false)),
                'has_default_billing_address' => $user->addresses->contains(fn ($address) => (bool) ($address->default_billing ?? false)),
                'profile_complete' => filled($user->name) && (filled($user->email) || filled($user->phone)),
            ],
        ];
    }
}
