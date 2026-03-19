<?php

namespace App\Http\Requests\Api\V1\Payments;

use Illuminate\Foundation\Http\FormRequest;

class InitializePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('api') !== null;
    }

    public function rules(): array
    {
        return [
            'redirect_to' => ['nullable', 'string', 'max:255'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['required', 'string', 'max:120'],
            'payment_type' => ['required', 'string', 'max:120'],
            'user_id' => ['nullable', 'integer'],
            'order_code' => ['nullable', 'string', 'max:120'],
            'transactionId' => ['nullable', 'string', 'max:255'],
            'receipt' => ['nullable', 'file', 'max:5120'],
            'card_number' => ['nullable', 'string', 'max:30'],
            'cvv' => ['nullable', 'string', 'max:10'],
            'expiration_month' => ['nullable', 'string', 'max:10'],
            'expiration_year' => ['nullable', 'string', 'max:10'],
        ];
    }
}
