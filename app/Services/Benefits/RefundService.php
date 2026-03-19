<?php

namespace App\Services\Benefits;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderUpdate;
use App\Models\RefundRequest;
use App\Models\RefundRequestItem;
use App\Models\Upload;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RefundService
{
    public function __construct(private readonly BenefitsFeatureService $featureService)
    {
    }

    public function listForUser(User $user, int $perPage = 12): LengthAwarePaginator
    {
        $this->featureService->ensureRefundEnabled();

        return RefundRequest::query()
            ->with([
                'shop',
                'order.combined_order',
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
                'refundRequests',
                'orderDetails.product.product_translations',
                'orderDetails.product.taxes',
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

        if (!$this->isEligible($order)) {
            throw new HttpException(422, "You can't send refund request for this order");
        }

        return [
            'order_code' => optional($order->combined_order)->code,
            'order' => $order,
            'has_refund_request' => $order->refundRequests->isNotEmpty(),
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
            ->with(['orderDetails', 'refundRequests'])
            ->find($payload['order_id']);

        if (!$order || (int) $order->user_id !== (int) $user->id) {
            throw new AccessDeniedHttpException('Something Went wrong.');
        }

        if (!$this->isEligible($order)) {
            throw new HttpException(422, "You can't send refund request for this order");
        }

        foreach ($refundItems as $refundItem) {
            $item = $order->orderDetails->firstWhere('id', $refundItem['order_detail_id'] ?? null);

            if (!$item) {
                throw new HttpException(422, 'Something Went wrong.');
            }

            if ((int) ($refundItem['quantity'] ?? 0) < 1 || (int) ($refundItem['quantity'] ?? 0) > (int) $item->quantity) {
                throw new HttpException(422, "You can't request more than ordered quantity");
            }
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
                'refund_note' => $payload['refund_note'],
                'attachments' => implode(',', $attachmentIds),
                'admin_approval' => 0,
            ]);

            foreach ($refundItems as $refundItem) {
                RefundRequestItem::query()->create([
                    'refund_request_id' => $refundRequest->id,
                    'order_detail_id' => $refundItem['order_detail_id'],
                    'quantity' => (int) $refundItem['quantity'],
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

    private function isEligible(Order $order): bool
    {
        $statuses = json_decode((string) get_setting('refund_request_order_status', '[]'), true) ?: [];
        $periodDays = (int) get_setting('refund_request_time_period', 0);
        $expiresAt = CarbonImmutable::parse($order->created_at)->addSeconds($periodDays * 86400);

        return $order->refundRequests->isEmpty()
            && $order->payment_status === 'paid'
            && in_array($order->delivery_status, $statuses, true)
            && CarbonImmutable::now()->lessThanOrEqualTo($expiresAt);
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

            $upload = new Upload();
            $upload->file_original_name = pathinfo($attachment->getClientOriginalName(), PATHINFO_FILENAME);
            $upload->file_name = $attachment->store('uploads/all');
            $upload->user_id = $user->id;
            $upload->extension = $attachment->getClientOriginalExtension();
            $upload->type = 'image';
            $upload->file_size = $attachment->getSize();
            $upload->save();

            $ids[] = (int) $upload->id;
        }

        return $ids;
    }
}
