<?php

namespace App\Http\Requests\Api\V1\Benefits;

use Illuminate\Foundation\Http\FormRequest;

class StoreRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'integer'],
            'refund_items' => ['required', 'string'],
            'refund_reasons' => ['nullable', 'string'],
            'refund_note' => ['required', 'string', 'max:5000'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'image', 'max:5120'],
        ];
    }
}
