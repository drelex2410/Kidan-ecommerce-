<?php

namespace App\Http\Requests\Api\V1\Account;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:6', 'same:confirmPassword'],
            'confirmPassword' => ['nullable', 'string', 'min:6'],
            'avatar' => ['nullable', 'image', 'max:5120'],
        ];
    }
}
