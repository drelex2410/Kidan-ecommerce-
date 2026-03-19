<?php

namespace App\Http\Requests\Api\V1\Catalog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

class ProductSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'category_slug' => ['nullable', 'string', 'max:255'],
            'brand_ids' => ['nullable', 'string'],
            'attribute_values' => ['nullable', 'string'],
            'keyword' => ['nullable', 'string', 'max:255'],
            'sort_by' => ['nullable', 'string', 'in:popular,latest,oldest,highest_price,lowest_price'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function brandIds(): array
    {
        return $this->parseCsvIntegers($this->validated('brand_ids'));
    }

    public function attributeValueIds(): array
    {
        return $this->parseCsvIntegers($this->validated('attribute_values'));
    }

    private function parseCsvIntegers(?string $value): array
    {
        return collect(explode(',', (string) $value))
            ->map(static fn ($item) => trim($item))
            ->filter(static fn ($item) => $item !== '' && ctype_digit($item))
            ->map(static fn ($item) => (int) $item)
            ->values()
            ->all();
    }
}
