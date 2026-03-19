<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Models\User;
use App\Services\Auth\AuthSettingsService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SignupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var AuthSettingsService $settings */
        $settings = app(AuthSettingsService::class);
        $loginWith = $settings->customerLoginWith();

        $emailRequired = in_array($loginWith, ['email', 'email_phone'], true) ? ['required'] : ['nullable'];
        $phoneRequired = in_array($loginWith, ['phone', 'email_phone'], true) ? ['required'] : ['nullable'];

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => array_merge($emailRequired, ['email', Rule::unique(User::class, 'email')]),
            'phone' => array_merge($phoneRequired, ['string', Rule::unique(User::class, 'phone')]),
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'temp_user_id' => ['nullable'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
