<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Services\Auth\AuthSettingsService;
use Illuminate\Foundation\Http\FormRequest;

class VerifyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $channel = app(AuthSettingsService::class)->verificationChannel() ?? 'email';

        return [
            'email' => $channel === 'email' ? ['required', 'email'] : ['nullable', 'email'],
            'phone' => $channel === 'phone' ? ['required', 'string'] : ['nullable', 'string'],
            'code' => ['required', 'string', 'size:6'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
