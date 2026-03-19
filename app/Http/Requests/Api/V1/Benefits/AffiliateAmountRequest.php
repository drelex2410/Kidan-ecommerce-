<?php

namespace App\Http\Requests\Api\V1\Benefits;

use Illuminate\Foundation\Http\FormRequest;

class AffiliateAmountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
        ];
    }
}
