<?php

namespace App\Http\Requests\Api\V1\Checkout;

use Illuminate\Foundation\Http\FormRequest;

class ChangeCartQuantityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cart_id' => ['required', 'integer'],
            'type' => ['required', 'in:plus,minus'],
            'temp_user_id' => ['nullable', 'string', 'max:64'],
        ];
    }
}
