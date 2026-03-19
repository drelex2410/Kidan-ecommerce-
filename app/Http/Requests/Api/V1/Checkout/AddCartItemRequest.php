<?php

namespace App\Http\Requests\Api\V1\Checkout;

use Illuminate\Foundation\Http\FormRequest;

class AddCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'variation_id' => ['required', 'integer'],
            'qty' => ['required', 'integer', 'min:1'],
            'temp_user_id' => ['nullable', 'string', 'max:64'],
        ];
    }
}
