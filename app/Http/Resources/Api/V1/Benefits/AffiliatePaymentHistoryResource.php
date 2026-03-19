<?php

namespace App\Http\Resources\Api\V1\Benefits;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AffiliatePaymentHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'amount' => (float) $this->amount,
            'date' => $this->created_at ? $this->created_at->format('d-M-Y') : null,
            'payment_method' => (string) ($this->payment_method ?? ''),
        ];
    }
}
