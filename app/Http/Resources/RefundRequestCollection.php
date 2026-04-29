<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class RefundRequestCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'data' => $this->collection->map(function($data) {
                [$statusKey, $statusLabel] = $this->resolveStatus($data);
                return [
                    'id' => $data->id,
                    'order_code' => optional($data->order->combined_order)->code,
                    'amount' => $data->amount,
                    'status' => $data->admin_approval,
                    'status_key' => $statusKey,
                    'status_label' => $statusLabel,
                    'shop' => optional($data->shop)->name,
                    'reasons' => $this->parseReasons($data->reasons),
                    'details' => (string) ($data->refund_note ?? ''),
                    'requested_at' => optional($data->requested_at)->timestamp ?? optional($data->created_at)->timestamp,
                    'refunditems' => RefundItemsCollection::collection($data->refundRequestItems),
                    'date' => $data->created_at->toFormattedDateString()
                ];
            })
        ];
    }

    public function with($request)
    {
        return [
            'success' => true,
            'status' => 200
        ];
    }

    private function resolveStatus($data): array
    {
        if (is_string($data->status) && $data->status !== '') {
            return match ($data->status) {
                'approved' => ['approved', translate('Approved')],
                'rejected' => ['rejected', translate('Rejected')],
                'processed' => ['processed', translate('Processed')],
                'cancelled' => ['cancelled', translate('Cancelled')],
                'under_review' => ['under_review', translate('Under Review')],
                default => ['pending', translate('pending')],
            };
        }

        return match ((int) ($data->admin_approval ?? 0)) {
            1 => ['approved', translate('accepted')],
            2 => ['rejected', translate('rejected')],
            default => ['pending', translate('pending')],
        };
    }

    private function parseReasons($reasons): array
    {
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
