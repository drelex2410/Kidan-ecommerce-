<?php

namespace App\Services\Benefits;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\RefundPolicy;
use App\Models\RefundRequest;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class RefundEligibilityService
{
    public function decorateOrder(Order $order): Order
    {
        $consumedQuantities = $this->consumedQuantitiesByOrderDetail($order);
        $items = $order->orderDetails->map(function (OrderDetail $orderDetail) use ($order, $consumedQuantities) {
            $eligibility = $this->evaluateOrderItem(
                $order,
                $orderDetail,
                (int) ($consumedQuantities[(int) $orderDetail->id] ?? 0)
            );

            $orderDetail->setAttribute('refund_eligibility', $eligibility);

            return $eligibility;
        });

        $order->setAttribute('refund_summary', $this->buildOrderSummary($order, $items));

        return $order;
    }

    public function evaluateOrderItem(Order $order, OrderDetail $orderDetail, int $consumedQuantity = 0): array
    {
        $product = $orderDetail->product;
        $policy = $product?->refundPolicy;
        $purchasedQuantity = max(0, (int) ($orderDetail->quantity ?? 0));
        $remainingQuantity = max(0, $purchasedQuantity - $consumedQuantity);
        $completionAt = $this->completionTimestamp($order);

        $base = [
            'is_eligible' => false,
            'message' => null,
            'policy_id' => $policy?->id,
            'policy_name' => $policy?->name,
            'allow_partial_refund' => (bool) ($policy?->allow_partial_refund ?? false),
            'requires_reason' => (bool) ($policy?->requires_reason ?? false),
            'requires_evidence' => (bool) ($policy?->requires_evidence ?? false),
            'quantity_purchased' => $purchasedQuantity,
            'quantity_already_requested' => $consumedQuantity,
            'max_requestable_quantity' => $remainingQuantity,
            'completed_at' => $completionAt?->timestamp,
            'expires_at' => $policy ? $this->expirationTimestamp($policy, $completionAt)?->timestamp : null,
        ];

        if (! $product) {
            return $this->withIneligibility($base, translate('This product is no longer available for refund.'));
        }

        if (! $policy) {
            return $this->withIneligibility($base, translate('Refund policy is not configured for this product.'));
        }

        if (! $policy->is_active) {
            return $this->withIneligibility($base, translate('Refund policy is inactive for this product.'));
        }

        if ($order->payment_status !== 'paid') {
            return $this->withIneligibility($base, translate('Only paid orders can be refunded.'));
        }

        $allowedStatuses = collect($policy->allowed_order_statuses ?? [])
            ->filter(fn ($status) => is_string($status) && $status !== '')
            ->values()
            ->all();

        if ($allowedStatuses === [] || ! in_array((string) $order->delivery_status, $allowedStatuses, true)) {
            return $this->withIneligibility($base, translate('This order status is not eligible for refund.'));
        }

        if ($policy->exclude_digital_products && (bool) ($product->digital ?? false)) {
            return $this->withIneligibility($base, translate('Digital products are excluded from refunds.'));
        }

        if ($policy->exclude_discounted_products && $this->isDiscountedPurchase($order, $orderDetail)) {
            return $this->withIneligibility($base, translate('Discounted items are excluded from refunds.'));
        }

        if (! $completionAt) {
            return $this->withIneligibility($base, translate('Refund completion time is not available for this order.'));
        }

        $expiresAt = $this->expirationTimestamp($policy, $completionAt);
        if ($expiresAt && CarbonImmutable::now()->greaterThan($expiresAt)) {
            return $this->withIneligibility($base, translate('The refund window for this item has expired.'));
        }

        if ($remainingQuantity < 1) {
            return $this->withIneligibility($base, translate('No refundable quantity remains for this item.'));
        }

        if (! $policy->allow_partial_refund && $consumedQuantity > 0) {
            return $this->withIneligibility($base, translate('This item only allows a single full refund request.'));
        }

        return array_merge($base, [
            'is_eligible' => true,
            'message' => translate('Eligible for refund request.'),
        ]);
    }

    public function eligibleItems(Order $order): Collection
    {
        $this->decorateOrder($order);

        return $order->orderDetails
            ->filter(fn (OrderDetail $detail) => (bool) data_get($detail->getAttribute('refund_eligibility'), 'is_eligible', false))
            ->values();
    }

    private function buildOrderSummary(Order $order, Collection $items): array
    {
        $activeRequestCount = $this->countActiveRequests($order);

        return [
            'has_eligible_items' => $items->contains(fn (array $item) => (bool) ($item['is_eligible'] ?? false)),
            'eligible_item_count' => $items->filter(fn (array $item) => (bool) ($item['is_eligible'] ?? false))->count(),
            'has_open_request' => $activeRequestCount > 0,
            'open_request_count' => $activeRequestCount,
            'completed_at' => $this->completionTimestamp($order)?->timestamp,
        ];
    }

    private function completionTimestamp(Order $order): ?CarbonImmutable
    {
        if ($order->completed_at) {
            return CarbonImmutable::parse($order->completed_at);
        }

        if ($order->delivery_history_date) {
            return CarbonImmutable::parse($order->delivery_history_date);
        }

        if ((string) $order->delivery_status === 'delivered') {
            return CarbonImmutable::parse($order->updated_at ?? $order->created_at);
        }

        return $order->created_at ? CarbonImmutable::parse($order->created_at) : null;
    }

    private function expirationTimestamp(RefundPolicy $policy, ?CarbonImmutable $completionAt): ?CarbonImmutable
    {
        if (! $completionAt) {
            return null;
        }

        return $completionAt->addDays(max(0, (int) $policy->refund_window_days));
    }

    private function consumedQuantitiesByOrderDetail(Order $order): array
    {
        if (!Schema::hasTable('refund_requests') || !Schema::hasTable('refund_request_items')) {
            return [];
        }

        $requests = $order->relationLoaded('refundRequests')
            ? $order->refundRequests
            : $order->refundRequests()->with('refundRequestItems')->get();

        if ($requests->isEmpty()) {
            return [];
        }

        $requests->loadMissing('refundRequestItems');

        return $requests
            ->filter(fn (RefundRequest $request) => $this->requestConsumesRefundCapacity($request))
            ->flatMap(function (RefundRequest $request) {
                return $request->refundRequestItems;
            })
            ->groupBy('order_detail_id')
            ->map(function (Collection $items) {
                return $items->sum(function ($item) {
                    return (int) ($item->quantity_requested ?? $item->quantity ?? 0);
                });
            })
            ->mapWithKeys(fn ($quantity, $orderDetailId) => [(int) $orderDetailId => (int) $quantity])
            ->all();
    }

    private function countActiveRequests(Order $order): int
    {
        if (!Schema::hasTable('refund_requests')) {
            return 0;
        }

        $requests = $order->relationLoaded('refundRequests')
            ? $order->refundRequests
            : $order->refundRequests()->get();

        return $requests
            ->filter(fn (RefundRequest $request) => $this->requestConsumesRefundCapacity($request))
            ->count();
    }

    private function requestConsumesRefundCapacity(RefundRequest $request): bool
    {
        if (is_string($request->status) && $request->status !== '') {
            return ! in_array($request->status, ['rejected', 'cancelled'], true);
        }

        if ((int) ($request->admin_approval ?? 0) === 2) {
            return false;
        }

        if ((int) ($request->seller_approval ?? 0) === 2) {
            return false;
        }

        return true;
    }

    private function isDiscountedPurchase(Order $order, OrderDetail $orderDetail): bool
    {
        $product = $orderDetail->product;

        if (! $product) {
            return false;
        }

        if ((float) ($product->discount ?? 0) > 0) {
            return true;
        }

        return (float) ($order->coupon_discount ?? 0) > 0;
    }

    private function withIneligibility(array $base, string $message): array
    {
        $base['is_eligible'] = false;
        $base['message'] = $message;
        $base['max_requestable_quantity'] = max(0, (int) ($base['max_requestable_quantity'] ?? 0));

        return $base;
    }
}
