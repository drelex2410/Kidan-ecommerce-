<?php

namespace App\Services\Auth;

use App\Models\Cart;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RegistrationService
{
    public function __construct(
        private readonly AuthSettingsService $authSettingsService,
        private readonly AuthCodeService $authCodeService
    ) {
    }

    public function register(array $payload): array
    {
        $user = User::query()->create([
            'name' => $payload['name'],
            'email' => $payload['email'] ?? null,
            'phone' => $payload['phone'] ?? null,
            'password' => Hash::make($payload['password']),
            'user_type' => 'customer',
            'banned' => false,
            'email_verified_at' => $this->authSettingsService->verificationEnabled() && $this->authSettingsService->verificationChannel() === 'email' ? null : now(),
            'phone_verified_at' => $this->authSettingsService->verificationEnabled() && $this->authSettingsService->verificationChannel() === 'phone' ? null : now(),
        ]);

        if (!empty($payload['temp_user_id'])) {
            Cart::query()
                ->where('temp_user_id', $payload['temp_user_id'])
                ->update([
                    'user_id' => $user->id,
                    'temp_user_id' => null,
                ]);
        }

        if (!$this->authSettingsService->verificationEnabled()) {
            return [
                'user' => $user,
                'verified' => true,
                'channel' => null,
            ];
        }

        $channel = $this->authSettingsService->verificationChannel();
        $target = $channel === 'phone'
            ? $this->normalizePhone($user->phone)
            : (string) $user->email;

        $code = $this->authCodeService->issueVerificationCode($user, $channel, $target);
        $this->authCodeService->sendVerificationCode($user, $channel, $target, $code->code);

        return [
            'user' => $user,
            'verified' => false,
            'channel' => $channel,
        ];
    }

    private function normalizePhone(?string $phone): ?string
    {
        return $phone ? Str::replace(' ', '', $phone) : null;
    }
}
