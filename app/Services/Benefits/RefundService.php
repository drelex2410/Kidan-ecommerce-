<?php

namespace App\Services\Benefits;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderUpdate;
use App\Models\RefundRequest;
use App\Models\RefundRequestItem;
use App\Services\Uploads\UploadManager;
use App\Support\Uploads\UploadValidationException;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RefundService
{
    public function __construct(
        private readonly BenefitsFeatureService $featureService,
        private readonly RefundEligibilityService $eligibilityService,
        private readonly UploadManager $uploadManager,
    )
    {
    }

    public function listForUser(User $user, int $perPage = 12): LengthAwarePaginator
    {
        $this->featureService->ensureRefundEnabled();

        return RefundRequest::query()
            ->with([
                'shop',
                'order.combined_order',
                'refundRequestItems.appliedRefundPolicy',
                'refundRequestItems.orderDetail.product.product_translations',
                'refundRequestItems.orderDetail.product.taxes',
                'refundRequestItems.orderDetail.variation.combinations.attribute.attribute_translations',
                'refundRequestItems.orderDetail.variation.combinations.attributeValue.attribute_value_translations',
            ])
            ->where('user_id', $user->id)
            ->latest()
            ->paginate($perPage);
    }

    public function createContext(User $user, int $orderId): array
    {
        $this->featureService->ensureRefundEnabled();

        $order = Order::query()
            ->with([
                'combined_order',
                'shop',
                'refundRequests.refundRequestItems',
                'orderDetails.product.product_translations',
                'orderDetails.product.taxes',
                'orderDetails.product.refundPolicy',
                'orderDetails.variation.combinations.attribute.attribute_translations',
                'orderDetails.variation.combinations.attributeValue.attribute_value_translations',
            ])
            ->find($orderId);

        if (!$order) {
            throw (new ModelNotFoundException())->setModel(Order::class, [$orderId]);
        }

        if ((int) $order->user_id !== (int) $user->id) {
            throw new AccessDeniedHttpException("This order is not yours.");
        }

        $order = $this->eligibilityService->decorateOrder($order);
        $refundSummary = (array) $order->getAttribute('refund_summary');

        if (!($refundSummary['has_eligible_items'] ?? false)) {
            throw new HttpException(422, "You can't send refund request for this order");
        }

        return [
            'order_code' => optional($order->combined_order)->code,
            'order' => $order,
            'has_refund_request' => (bool) ($refundSummary['has_open_request'] ?? false),
        ];
    }

    public function store(User $user, array $payload, array $attachments = []): array
    {
        $this->featureService->ensureRefundEnabled();

        $refundItems = collect(json_decode((string) ($payload['refund_items'] ?? '[]'), true) ?: [])
            ->filter(fn ($item) => !empty($item['status']))
            ->values();

        if ($refundItems->isEmpty()) {
            throw new HttpException(422, 'Please Select items first.');
        }

        $order = Order::query()
            ->with([
                'combined_order',
                'refundRequests.refundRequestItems',
                'orderDetails.product.refundPolicy',
                'orderDetails.product.product_translations',
                'orderDetails.product.taxes',
                'orderDetails.variation.combinations.attribute.attribute_translations',
                'orderDetails.variation.combinations.attributeValue.attribute_value_translations',
            ])
            ->find($payload['order_id']);

        if (!$order || (int) $order->user_id !== (int) $user->id) {
            throw new AccessDeniedHttpException('Something Went wrong.');
        }

        $order = $this->eligibilityService->decorateOrder($order);
        $refundSummary = (array) $order->getAttribute('refund_summary');

        if (!($refundSummary['has_eligible_items'] ?? false)) {
            throw new HttpException(422, "You can't send refund request for this order");
        }

        $selectedOrderDetails = collect();
        $requiresReason = false;
        $requiresEvidence = false;

        foreach ($refundItems as $refundItem) {
            $item = $order->orderDetails->firstWhere('id', $refundItem['order_detail_id'] ?? null);
            $eligibility = $item?->getAttribute('refund_eligibility');

            if (!$item || !is_array($eligibility)) {
                throw new HttpException(422, 'Something Went wrong.');
            }

            if (!($eligibility['is_eligible'] ?? false)) {
                throw new HttpException(422, (string) ($eligibility['message'] ?? "You can't send refund request for this item"));
            }

            $requestedQuantity = (int) ($refundItem['quantity'] ?? 0);

            if ($requestedQuantity < 1 || $requestedQuantity > (int) ($eligibility['max_requestable_quantity'] ?? 0)) {
                throw new HttpException(422, "You can't request more than refundable quantity");
            }

            if (!($eligibility['allow_partial_refund'] ?? false) && $requestedQuantity !== (int) $item->quantity) {
                throw new HttpException(422, translate('This item requires a full-quantity refund request.'));
            }

            $requiresReason = $requiresReason || (bool) ($eligibility['requires_reason'] ?? false);
            $requiresEvidence = $requiresEvidence || (bool) ($eligibility['requires_evidence'] ?? false);
            $selectedOrderDetails->push([$item, $eligibility, $requestedQuantity]);
        }

        if ($requiresReason && trim((string) ($payload['refund_reasons'] ?? '')) === '') {
            throw new HttpException(422, translate('Refund reason is required for the selected item(s).'));
        }

        if ($requiresReason && trim((string) ($payload['refund_note'] ?? '')) === '') {
            throw new HttpException(422, translate('Refund details are required for the selected item(s).'));
        }

        if ($requiresEvidence && empty($attachments)) {
            throw new HttpException(422, translate('Evidence is required for the selected item(s).'));
        }

        return DB::transaction(function () use ($user, $payload, $order, $refundItems, $attachments) {
            $amount = $refundItems->sum(function (array $refundItem) use ($order) {
                /** @var OrderDetail $item */
                $item = $order->orderDetails->firstWhere('id', $refundItem['order_detail_id']);

                return ((float) $item->price + (float) $item->tax) * (int) $refundItem['quantity'];
            });

            $attachmentIds = $this->persistAttachments($user, $attachments);

            $refundRequest = RefundRequest::query()->create([
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'shop_id' => $order->shop_id,
                'amount' => $amount,
                'reasons' => $payload['refund_reasons'] !== '' ? json_encode(explode(',', (string) $payload['refund_reasons'])) : '[]',
                'refund_note' => $payload['refund_note'] ?? '',
                'attachments' => implode(',', $attachmentIds),
                'admin_approval' => 0,
                'status' => 'pending',
                'requested_at' => now(),
            ]);

            foreach ($refundItems as $refundItem) {
                $item = $order->orderDetails->firstWhere('id', $refundItem['order_detail_id']);
                $eligibility = $item?->getAttribute('refund_eligibility') ?? [];

                RefundRequestItem::query()->create([
                    'refund_request_id' => $refundRequest->id,
                    'order_detail_id' => $refundItem['order_detail_id'],
                    'quantity' => (int) $refundItem['quantity'],
                    'product_id' => $item?->product_id,
                    'applied_refund_policy_id' => $eligibility['policy_id'] ?? null,
                    'quantity_requested' => (int) $refundItem['quantity'],
                    'item_status' => 'pending',
                ]);
            }

            if (Schema::hasTable('order_updates')) {
                OrderUpdate::query()->create([
                    'order_id' => $order->id,
                    'user_id' => $user->id,
                    'note' => 'Refund request created.',
                ]);
            }

            return [
                'success' => true,
                'message' => translate('Your request has been submitted successfully'),
            ];
        });
    }

    /**
     * @param  array<int, UploadedFile>  $attachments
     * @return array<int, int>
     */
    private function persistAttachments(User $user, array $attachments): array
    {
        if (!Schema::hasTable('uploads')) {
            return [];
        }

        $ids = [];

        foreach ($attachments as $attachment) {
            if (!$attachment instanceof UploadedFile) {
                continue;
            }

            try {
                $upload = $this->uploadManager->store($attachment, (int) $user->id);
            } catch (UploadValidationException $exception) {
                throw new HttpException($exception->status(), $exception->getMessage(), $exception);
            }

            $ids[] = (int) $upload->id;
        }

        return $ids;
    }
}
