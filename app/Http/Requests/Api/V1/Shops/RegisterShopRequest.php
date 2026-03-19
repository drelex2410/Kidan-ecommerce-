<?php

namespace App\Http\Requests\Api\V1\Shops;

use Illuminate\Foundation\Http\FormRequest;

class RegisterShopRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'confirmPassword' => ['required', 'same:password'],
            'shopName' => ['required', 'string', 'max:255'],
            'shopPhone' => ['required', 'string', 'max:50'],
            'shopAddress' => ['required', 'string', 'max:1000'],
        ];
    }
}
