<?php

namespace App\Http\Requests\Api\V1\Account;

class UpdateAddressRequest extends StoreAddressRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'id' => ['required', 'integer', 'exists:addresses,id'],
        ]);
    }
}
