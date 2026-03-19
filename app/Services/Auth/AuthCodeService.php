<?php

namespace App\Services\Auth;

use App\Http\Services\SmsServices;
use App\Mail\EmailManager;
use App\Models\AuthCode;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Mail;

class AuthCodeService
{
    public const PURPOSE_VERIFICATION = 'verification';
    public const PURPOSE_PASSWORD_RESET = 'password_reset';

    public function __construct(
        private readonly AuthSettingsService $authSettingsService,
        private readonly SmsServices $smsServices
    ) {
    }

    public function issueVerificationCode(User $user, string $channel, string $target): AuthCode
    {
        return $this->issueCode($user, self::PURPOSE_VERIFICATION, $channel, $target, $this->authSettingsService->verificationExpiresInMinutes());
    }

    public function issuePasswordResetCode(User $user, string $channel, string $target): AuthCode
    {
        return $this->issueCode($user, self::PURPOSE_PASSWORD_RESET, $channel, $target, $this->authSettingsService->passwordResetExpiresInMinutes());
    }

    public function consumeValidCode(string $purpose, string $channel, string $target, string $code): ?AuthCode
    {
        $authCode = AuthCode::query()
            ->where('purpose', $purpose)
            ->where('channel', $channel)
            ->where('target', $target)
            ->where('code', $code)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (!$authCode) {
            return null;
        }

        $authCode->forceFill(['used_at' => now()])->save();

        return $authCode;
    }

    public function sendVerificationCode(User $user, string $channel, string $target, string $code): void
    {
        if ($channel === 'phone') {
            $this->smsServices->phoneVerificationSms($target, $code);

            return;
        }

        Mail::to($target)->queue(new EmailManager([
            'view' => 'emails.verification',
            'from' => env('MAIL_FROM_ADDRESS'),
            'subject' => translate('Email Verification'),
            'content' => translate('You verification code is '),
            'verification_code' => $code,
        ]));
    }

    public function sendPasswordResetCode(string $channel, string $target, string $code): void
    {
        if ($channel === 'phone') {
            $this->smsServices->forgotPasswordSms($target, $code);

            return;
        }

        Mail::to($target)->queue(new EmailManager([
            'view' => 'emails.verification',
            'from' => env('MAIL_FROM_ADDRESS'),
            'subject' => translate('Password Reset'),
            'content' => translate('Password reset code is'),
            'verification_code' => $code,
        ]));
    }

    private function issueCode(User $user, string $purpose, string $channel, string $target, int $expiresInMinutes): AuthCode
    {
        AuthCode::query()
            ->where('purpose', $purpose)
            ->where('channel', $channel)
            ->where('target', $target)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        $code = (string) random_int(100000, 999999);

        $authCode = AuthCode::query()->create([
            'user_id' => $user->id,
            'purpose' => $purpose,
            'channel' => $channel,
            'target' => $target,
            'code' => $code,
            'expires_at' => CarbonImmutable::now()->addMinutes($expiresInMinutes),
        ]);

        $user->forceFill([
            'verification_code' => $code,
            'verification_sent_at' => now(),
        ])->save();

        return $authCode;
    }
}
