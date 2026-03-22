<?php

namespace App\Http\Requests\Api\V1\Checkout;

use Illuminate\Foundation\Http\FormRequest;

class GuestShippingQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'guest_city_id' => ['required', 'integer'],
            'shop_count' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
