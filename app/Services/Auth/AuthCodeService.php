<?php

namespace App\Services\Auth;

use App\Http\Services\SmsServices;
use App\Mail\EmailManager;
use App\Models\AuthCode;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
        $this->dispatchCode(
            self::PURPOSE_VERIFICATION,
            $channel,
            $target,
            $code,
            'Email Verification',
            'You verification code is ',
            fn (string $phone, string $verificationCode): bool => $this->smsServices->phoneVerificationSms($phone, $verificationCode)
        );
    }

    public function sendPasswordResetCode(string $channel, string $target, string $code): void
    {
        $this->dispatchCode(
            self::PURPOSE_PASSWORD_RESET,
            $channel,
            $target,
            $code,
            'Password Reset',
            'Password reset code is',
            fn (string $phone, string $verificationCode): bool => $this->smsServices->forgotPasswordSms($phone, $verificationCode)
        );
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

    private function dispatchCode(
        string $purpose,
        string $channel,
        string $target,
        string $code,
        string $subject,
        string $content,
        callable $smsCallback
    ): void {
        if ($channel === 'phone') {
            if (!$smsCallback($target, $code)) {
                Log::warning('Auth code SMS delivery failed', [
                    'purpose' => $purpose,
                    'channel' => $channel,
                    'target' => $this->maskTarget($channel, $target),
                ]);

                throw new HttpException(503, 'Unable to send verification code at the moment.');
            }

            Log::info('Auth code SMS dispatched', [
                'purpose' => $purpose,
                'channel' => $channel,
                'target' => $this->maskTarget($channel, $target),
            ]);

            return;
        }

        try {
            Mail::to($target)->send(new EmailManager([
                'view' => 'emails.verification',
                'from' => config('mail.from.address'),
                'subject' => translate($subject),
                'content' => translate($content),
                'verification_code' => $code,
            ]));

            Log::info('Auth code email dispatched', [
                'purpose' => $purpose,
                'channel' => $channel,
                'target' => $this->maskTarget($channel, $target),
                'mailer' => config('mail.default'),
                'queue_connection' => config('queue.default'),
            ]);
        } catch (\Throwable $exception) {
            Log::error('Auth code email delivery failed', [
                'purpose' => $purpose,
                'channel' => $channel,
                'target' => $this->maskTarget($channel, $target),
                'mailer' => config('mail.default'),
                'queue_connection' => config('queue.default'),
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
            report($exception);

            throw new HttpException(503, 'Unable to send verification code at the moment.');
        }
    }

    private function maskTarget(string $channel, string $target): string
    {
        if ($channel === 'phone') {
            return Str::mask($target, '*', 3, max(strlen($target) - 5, 1));
        }

        [$localPart, $domain] = array_pad(explode('@', $target, 2), 2, '');

        if ($domain === '') {
            return Str::mask($target, '*', 3);
        }

        return Str::mask($localPart, '*', 1) . '@' . $domain;
    }
}
