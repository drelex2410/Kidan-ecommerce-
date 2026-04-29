<?php

namespace App\Http\Resources\Api\V1\Benefits;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RefundRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        [$statusKey, $statusLabel] = $this->resolveStatus();
        $reasons = $this->parseReasons();

        return [
            'id' => (int) $this->id,
            'order_code' => (string) (optional($this->order?->combined_order)->code ?? ''),
            'amount' => (float) $this->amount,
            'status' => (int) ($this->admin_approval ?? 0),
            'status_key' => $statusKey,
            'status_label' => $statusLabel,
            'shop' => (string) (optional($this->shop)->name ?? ''),
            'reasons' => $reasons,
            'details' => (string) ($this->refund_note ?? ''),
            'requested_at' => $this->requested_at?->timestamp ?? $this->created_at?->timestamp,
            'reviewed_at' => $this->reviewed_at?->timestamp,
            'refunditems' => RefundRequestItemResource::collection($this->refundRequestItems)->resolve(),
            'date' => $this->created_at ? $this->created_at->toFormattedDateString() : null,
        ];
    }

    private function resolveStatus(): array
    {
        if (is_string($this->status) && $this->status !== '') {
            return match ($this->status) {
                'approved' => ['approved', translate('Approved')],
                'rejected' => ['rejected', translate('Rejected')],
                'processed' => ['processed', translate('Processed')],
                'cancelled' => ['cancelled', translate('Cancelled')],
                'under_review' => ['under_review', translate('Under Review')],
                default => ['pending', translate('pending')],
            };
        }

        return match ((int) ($this->admin_approval ?? 0)) {
            1 => ['approved', translate('accepted')],
            2 => ['rejected', translate('rejected')],
            default => ['pending', translate('pending')],
        };
    }

    private function parseReasons(): array
    {
        $reasons = $this->reasons;

        if (is_array($reasons)) {
            return array_values(array_filter($reasons, fn ($reason) => is_string($reason) && trim($reason) !== ''));
        }

        if (! is_string($reasons) || trim($reasons) === '') {
            return [];
        }

        $decoded = json_decode($reasons, true);
        if (is_array($decoded)) {
            return array_values(array_filter($decoded, fn ($reason) => is_string($reason) && trim($reason) !== ''));
        }

        return array_values(array_filter(array_map('trim', explode(',', $reasons))));
    }
}
