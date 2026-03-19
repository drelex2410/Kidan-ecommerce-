<?php

namespace App\Http\Requests\Api\V1\Account;

use Illuminate\Foundation\Http\FormRequest;

class StoreAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'address' => ['required', 'string'],
            'postal_code' => ['required', 'string', 'max:50'],
            'country' => ['required', 'integer', 'exists:countries,id'],
            'state' => ['required', 'integer', 'exists:states,id'],
            'city' => ['required', 'integer', 'exists:cities,id'],
            'phone' => ['required', 'string', 'max:50'],
        ];
    }
}
