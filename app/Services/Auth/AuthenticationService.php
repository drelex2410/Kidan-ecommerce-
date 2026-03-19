<?php

namespace App\Services\Auth;

use App\Models\Cart;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AuthenticationService
{
    public function __construct(
        private readonly AuthSettingsService $authSettingsService,
        private readonly AuthCodeService $authCodeService
    ) {
    }

    public function login(array $credentials): array
    {
        $user = $this->resolveUser($credentials);

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw new HttpException(422, 'Invalid login information');
        }

        if ((bool) $user->banned) {
            throw new HttpException(403, 'You are banned!');
        }

        if (($credentials['form_type'] ?? 'customer') !== $user->user_type) {
            throw new HttpException(403, 'You can not login from here. This panel is only for ' . ($credentials['form_type'] ?? 'customer'));
        }

        if (!empty($credentials['temp_user_id'])) {
            Cart::query()
                ->where('temp_user_id', $credentials['temp_user_id'])
                ->update([
                    'user_id' => $user->id,
                    'temp_user_id' => null,
                ]);
        }

        if ($this->requiresVerification($user)) {
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
                'token' => null,
            ];
        }

        return [
            'user' => $user,
            'verified' => true,
            'channel' => null,
            'token' => $user->createToken($credentials['device_name'] ?? ($credentials['form_type'] ?? 'customer'))->plainTextToken,
        ];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }

    private function resolveUser(array $credentials): ?User
    {
        $phone = isset($credentials['phone']) ? $this->normalizePhone($credentials['phone']) : null;

        if (!empty($credentials['email'])) {
            return User::query()->where('email', $credentials['email'])->first();
        }

        if (!empty($phone)) {
            return User::query()->where('phone', $phone)->first();
        }

        return null;
    }

    private function requiresVerification(User $user): bool
    {
        if (!$this->authSettingsService->verificationEnabled()) {
            return false;
        }

        return $this->authSettingsService->verificationChannel() === 'phone'
            ? $user->phone_verified_at === null
            : $user->email_verified_at === null;
    }

    private function normalizePhone(?string $phone): ?string
    {
        return $phone ? Str::replace(' ', '', $phone) : null;
    }
}
