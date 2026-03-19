<?php

namespace App\Http\Requests\Api\V1\Content;

use Illuminate\Foundation\Http\FormRequest;

class BlogSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:24'],
            'category_slug' => ['nullable', 'string', 'max:255'],
            'searchKeyword' => ['nullable', 'string', 'max:255'],
        ];
    }
}
