<?php

namespace App\Http\Requests\Api\V1\Benefits;

use Illuminate\Foundation\Http\FormRequest;

class AffiliatePaymentSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'paypalEmail' => ['required', 'email:rfc', 'max:255'],
            'bankInformations' => ['required', 'string', 'max:3000'],
        ];
    }
}
