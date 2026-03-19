<?php

namespace App\Http\Requests\Api\V1\Checkout;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shipping_address_id' => ['nullable', 'integer'],
            'billing_address_id' => ['nullable', 'integer'],
            'payment_type' => ['required', 'string', 'max:120'],
            'delivery_type' => ['nullable', Rule::in(['standard', 'express'])],
            'type_of_delivery' => ['required', Rule::in(['home_delivery', 'pickup'])],
            'pickup_point_id' => ['nullable', 'integer'],
            'cart_item_ids' => ['required', 'array', 'min:1'],
            'cart_item_ids.*' => ['integer'],
            'coupon_codes' => ['nullable', 'array'],
            'coupon_codes.*' => ['string', 'max:120'],
            'transactionId' => ['nullable', 'string', 'max:255'],
            'receipt' => ['nullable', 'file', 'max:5120'],
        ];
    }
}
