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
            'temp_user_id' => ['nullable', 'string', 'max:64'],
            'guest_name' => ['nullable', 'string', 'max:255'],
            'guest_email' => ['nullable', 'email', 'max:255'],
            'guest_phone' => ['nullable', 'string', 'max:50'],
            'guest_address' => ['nullable', 'string', 'max:2000'],
            'guest_country_id' => ['nullable', 'integer'],
            'guest_state_id' => ['nullable', 'integer'],
            'guest_city_id' => ['nullable', 'integer'],
            'guest_postal_code' => ['nullable', 'string', 'max:50'],
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

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($this->user('api')) {
                return;
            }

            foreach ([
                'temp_user_id',
                'guest_name',
                'guest_email',
                'guest_phone',
                'guest_address',
                'guest_country_id',
                'guest_state_id',
                'guest_city_id',
            ] as $field) {
                if (blank($this->input($field))) {
                    $validator->errors()->add($field, "The {$field} field is required for guest checkout.");
                }
            }
        });
    }
}
