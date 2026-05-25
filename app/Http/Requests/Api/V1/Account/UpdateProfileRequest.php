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
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'password' => ['nullable', 'string', 'min:6', 'same:confirmPassword'],
            'confirmPassword' => ['nullable', 'string', 'min:6'],
            'avatar' => [
                'nullable',
                'file',
                'max:' . (int) config('uploads.image_max_file_size_kb', 15360),
                'mimes:' . implode(',', (array) config('uploads.allowed_image_extensions', ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'])),
            ],
        ];
    }
}
