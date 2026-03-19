<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PasswordResetService
{
    public function __construct(
        private readonly AuthCodeService $authCodeService
    ) {
    }

    public function create(array $payload): array
    {
        [$channel, $target] = $this->resolveChannelAndTarget($payload);
        $user = User::query()->where($channel, $target)->first();

        if (!$user) {
            throw new HttpException(404, 'No user found with this information.');
        }

        $code = $this->authCodeService->issuePasswordResetCode($user, $channel, $target);
        $this->authCodeService->sendPasswordResetCode($channel, $target, $code->code);

        return [
            'channel' => $channel,
            'target' => $target,
        ];
    }

    public function reset(array $payload): void
    {
        [$channel, $target] = $this->resolveChannelAndTarget($payload);
        $user = User::query()->where($channel, $target)->first();

        if (!$user) {
            throw new HttpException(404, 'No user found with this information.');
        }

        $code = $this->authCodeService->consumeValidCode(
            AuthCodeService::PURPOSE_PASSWORD_RESET,
            $channel,
            $target,
            (string) $payload['code']
        );

        if (!$code) {
            throw new HttpException(422, 'Code does not match.');
        }

        $user->forceFill([
            'password' => Hash::make($payload['password']),
        ])->save();

        $user->tokens()->delete();
    }

    private function resolveChannelAndTarget(array $payload): array
    {
        if (!empty($payload['email'])) {
            return ['email', (string) $payload['email']];
        }

        return ['phone', Str::replace(' ', '', (string) ($payload['phone'] ?? ''))];
    }
}
