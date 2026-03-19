<?php

namespace App\Http\Requests\Api\V1\Benefits;

use Illuminate\Foundation\Http\FormRequest;

class ConvertClubPointRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'integer'],
        ];
    }
}
