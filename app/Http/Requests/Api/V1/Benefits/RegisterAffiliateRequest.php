<?php

namespace App\Http\Requests\Api\V1\Benefits;

use Illuminate\Foundation\Http\FormRequest;

class RegisterAffiliateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'address' => ['required', 'string', 'max:1000'],
            'description' => ['required', 'string', 'max:3000'],
            'password' => ['nullable', 'string', 'min:8'],
            'confirmPassword' => ['nullable', 'same:password'],
        ];
    }
}
