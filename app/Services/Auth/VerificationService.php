<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class VerificationService
{
    public function __construct(
        private readonly AuthSettingsService $authSettingsService,
        private readonly AuthCodeService $authCodeService
    ) {
    }

    public function verify(array $payload): array
    {
        [$channel, $target] = $this->resolveChannelAndTarget($payload);
        $user = $this->findUser($channel, $target);

        if (!$user) {
            throw new HttpException(404, 'No user found with this information.');
        }

        $code = $this->authCodeService->consumeValidCode(
            AuthCodeService::PURPOSE_VERIFICATION,
            $channel,
            $target,
            (string) $payload['code']
        );

        if (!$code) {
            throw new HttpException(422, 'Code does not match.');
        }

        $timestampColumn = $channel === 'phone' ? 'phone_verified_at' : 'email_verified_at';
        $user->forceFill([$timestampColumn => now()])->save();

        return [
            'user' => $user,
            'verified' => true,
            'channel' => $channel,
            'token' => $user->createToken($payload['device_name'] ?? 'customer')->plainTextToken,
        ];
    }

    public function resend(array $payload): array
    {
        [$channel, $target] = $this->resolveChannelAndTarget($payload);
        $user = $this->findUser($channel, $target);

        if (!$user) {
            throw new HttpException(404, 'No user found with this information.');
        }

        $code = $this->authCodeService->issueVerificationCode($user, $channel, $target);
        $this->authCodeService->sendVerificationCode($user, $channel, $target, $code->code);

        return [
            'user' => $user,
            'verified' => false,
            'channel' => $channel,
        ];
    }

    private function resolveChannelAndTarget(array $payload): array
    {
        $channel = $this->authSettingsService->verificationChannel() ?? 'email';
        $target = $channel === 'phone'
            ? Str::replace(' ', '', (string) ($payload['phone'] ?? ''))
            : (string) ($payload['email'] ?? '');

        return [$channel, $target];
    }

    private function findUser(string $channel, string $target): ?User
    {
        return User::query()->where($channel, $target)->first();
    }
}
