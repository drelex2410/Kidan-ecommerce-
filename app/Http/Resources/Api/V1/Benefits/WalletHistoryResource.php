<?php

namespace App\Http\Resources\Api\V1\Benefits;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'date' => $this->created_at ? $this->created_at->toDateString() : null,
            'amount' => (float) $this->amount,
            'payment_method' => (string) ($this->payment_method ?? ''),
            'type' => (string) ($this->type ?? ''),
            'details' => $this->details ?? $this->payment_details,
            'receipt' => $this->reciept ? my_asset($this->reciept) : null,
        ];
    }
}
