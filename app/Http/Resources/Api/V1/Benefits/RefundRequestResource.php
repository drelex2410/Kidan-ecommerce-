<?php

namespace App\Http\Resources\Api\V1\Benefits;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RefundRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'order_code' => (string) (optional($this->order?->combined_order)->code ?? ''),
            'amount' => (float) $this->amount,
            'status' => (int) ($this->admin_approval ?? 0),
            'shop' => (string) (optional($this->shop)->name ?? ''),
            'refunditems' => RefundRequestItemResource::collection($this->refundRequestItems)->resolve(),
            'date' => $this->created_at ? $this->created_at->toFormattedDateString() : null,
        ];
    }
}
