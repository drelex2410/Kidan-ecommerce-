<?php

namespace App\Services\Auth;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class AuthSettingsService
{
    public function customerLoginWith(): string
    {
        return $this->setting('customer_login_with', 'email');
    }

    public function customerOtpWith(): string
    {
        return $this->setting('customer_otp_with', 'disabled');
    }

    public function verificationEnabled(): bool
    {
        return $this->customerOtpWith() !== 'disabled';
    }

    public function verificationChannel(): ?string
    {
        return $this->verificationEnabled() ? $this->customerOtpWith() : null;
    }

    public function verificationExpiresInMinutes(): int
    {
        return 15;
    }

    public function passwordResetExpiresInMinutes(): int
    {
        return (int) config('auth.passwords.users.expire', 60);
    }

    private function setting(string $key, string $default): string
    {
        if (!Schema::hasTable('settings')) {
            return $default;
        }

        return (string) Cache::remember("auth-settings.{$key}", 300, static function () use ($key, $default) {
            return Setting::query()->where('type', $key)->value('value') ?? $default;
        });
    }
}
