<?php

namespace App\Services\Account;

use App\Models\CombinedOrder;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class OrderAccountService
{
    public function listForUser(User $user, int $perPage = 12): LengthAwarePaginator
    {
        return CombinedOrder::query()
            ->with($this->orderRelations())
            ->where('user_id', $user->id)
            ->latest()
            ->paginate($perPage);
    }

    public function findByCodeForUser(User $user, string $orderCode): CombinedOrder
    {
        $order = CombinedOrder::query()
            ->with($this->orderRelations())
            ->where('code', $orderCode)
            ->first();

        if (!$order) {
            throw (new ModelNotFoundException())->setModel(CombinedOrder::class, [$orderCode]);
        }

        if ((int) $order->user_id !== (int) $user->id) {
            if ($user->user_type !== 'delivery_boy' || !$order->orders->contains(fn ($shopOrder) => (int) ($shopOrder->assign_delivery_boy ?? 0) === (int) $user->id)) {
                throw new AccessDeniedHttpException("This order is not yours.");
            }
        }

        return $order;
    }

    public function cancel(User $user, int $orderId): array
    {
        $order = Order::query()
            ->with(['orderDetails.product.categories', 'orderDetails.variation', 'orderDetails.product.brand'])
            ->findOrFail($orderId);

        if ((int) $order->user_id !== (int) $user->id) {
            throw new AccessDeniedHttpException();
        }

        if ($order->delivery_status !== 'order_placed' || $order->payment_status !== 'unpaid') {
            return [
                'success' => false,
                'message' => translate("This order can't be cancelled."),
            ];
        }

        $order->delivery_status = 'cancelled';
        $order->save();

        foreach ($order->orderDetails as $detail) {
            if ($detail->variation && $detail->variation->current_stock !== null) {
                $detail->variation->increment('current_stock', (int) $detail->quantity);
            }
        }

        return [
            'success' => true,
            'message' => translate('Order has been cancelled'),
        ];
    }

    public function downloadableProducts(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return Product::query()
            ->select('products.*')
            ->join('order_details', 'order_details.product_id', '=', 'products.id')
            ->join('orders', 'orders.id', '=', 'order_details.order_id')
            ->where('orders.user_id', $user->id)
            ->where('orders.payment_status', 'paid')
            ->where('products.digital', 1)
            ->orderByDesc('orders.id')
            ->distinct('products.id')
            ->paginate($perPage);
    }

    public function invoiceDownloadPayload(User $user, int $orderId): array
    {
        $order = Order::query()->with('combined_order')->findOrFail($orderId);

        if ((int) $order->user_id !== (int) $user->id) {
            throw new AccessDeniedHttpException();
        }

        $code = optional($order->combined_order)->code ?: (string) $order->id;
        $fileName = 'order-invoice-' . $code . '.html';
        $path = 'invoices/' . $fileName;

        $content = view('api.v1.account.invoice', [
            'order' => $order,
            'combinedOrder' => $order->combined_order,
        ])->render();

        Storage::disk('public')->put($path, $content);

        return [
            'success' => true,
            'message' => translate('Invoice generated.'),
            'invoice_url' => Storage::disk('public')->url($path),
            'invoice_name' => $code,
        ];
    }

    public function orderRelations(): array
    {
        $relations = [
            'user',
            'orders.shop',
            'orders.orderDetails.product.product_translations',
            'orders.orderDetails.product.taxes',
            'orders.orderDetails.variation.combinations.attribute.attribute_translations',
            'orders.orderDetails.variation.combinations.attributeValue.attribute_value_translations',
        ];

        if (Schema::hasTable('pickup_points')) {
            $relations[] = 'orders.pickupPoint.user';
        }

        if (Schema::hasTable('refund_requests')) {
            $relations[] = 'orders.refundRequests';
        }

        return $relations;
    }
}
