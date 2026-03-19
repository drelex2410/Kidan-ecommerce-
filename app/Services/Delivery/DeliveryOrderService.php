<?php

namespace App\Services\Delivery;

use App\Models\DeliveryBoy;
use App\Models\DeliveryHistory;
use App\Models\Order;
use App\Models\OrderUpdate;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DeliveryOrderService
{
    public function __construct(private readonly DeliveryDashboardService $dashboardService)
    {
    }

    public function assigned(User $user): LengthAwarePaginator
    {
        return $this->assignedBaseQuery($user)
            ->where(function ($query) {
                $query->where('delivery_status', 'pending')
                    ->where('cancel_request', '0');
            })->orWhere(function ($query) use ($user) {
                $query->where('assign_delivery_boy', $user->id)
                    ->where('delivery_status', 'confirmed')
                    ->where('cancel_request', '0');
            })
            ->paginate(10);
    }

    public function pending(User $user): LengthAwarePaginator
    {
        return $this->assignedBaseQuery($user)
            ->where('delivery_status', '!=', 'delivered')
            ->where('delivery_status', '!=', 'cancelled')
            ->where('cancel_request', '0')
            ->paginate(10);
    }

    public function pickedUp(User $user): LengthAwarePaginator
    {
        return $this->assignedBaseQuery($user)
            ->where('delivery_status', 'picked_up')
            ->where('cancel_request', '0')
            ->paginate(10);
    }

    public function onTheWay(User $user): LengthAwarePaginator
    {
        return $this->assignedBaseQuery($user)
            ->where('delivery_status', 'on_the_way')
            ->where('cancel_request', '0')
            ->paginate(10);
    }

    public function completed(User $user): LengthAwarePaginator
    {
        return $this->assignedBaseQuery($user)
            ->where('delivery_status', 'delivered')
            ->where('cancel_request', '0')
            ->paginate(10);
    }

    public function cancelled(User $user): LengthAwarePaginator
    {
        return $this->assignedBaseQuery($user)
            ->where('delivery_status', 'cancelled')
            ->paginate(10);
    }

    public function collections(User $user): LengthAwarePaginator
    {
        $this->dashboardService->ensureDeliveryBoy($user);

        return DeliveryHistory::query()
            ->with('order.combined_order')
            ->where('delivery_boy_id', $user->id)
            ->where('delivery_status', 'delivered')
            ->where('payment_type', 'cash_on_delivery')
            ->latest()
            ->paginate(10);
    }

    public function earnings(User $user): LengthAwarePaginator
    {
        $this->dashboardService->ensureDeliveryBoy($user);

        return DeliveryHistory::query()
            ->with('order.combined_order')
            ->where('delivery_boy_id', $user->id)
            ->where('delivery_status', 'delivered')
            ->latest()
            ->paginate(10);
    }

    public function updateStatus(User $user, int $orderId, string $targetStatus): array
    {
        $this->dashboardService->ensureDeliveryBoy($user);

        $order = $this->assignedOrder($user, $orderId);

        $allowed = $this->allowedTransitions()[$order->delivery_status] ?? [];
        if (!in_array($targetStatus, $allowed, true)) {
            throw new HttpException(422, 'Invalid delivery status transition.');
        }

        if ((int) ($order->cancel_request ?? 0) === 1) {
            throw new HttpException(422, 'This order already has a cancel request.');
        }

        DB::transaction(function () use ($user, $order, $targetStatus) {
            $order->delivery_status = $targetStatus;
            if ($targetStatus === 'delivered' && $order->payment_type === 'cash_on_delivery' && $order->payment_status === 'unpaid') {
                $order->payment_status = 'paid';
            }
            if (Schema::hasColumn('orders', 'delivery_history_date')) {
                $order->delivery_history_date = now();
            }
            $order->save();

            if (Schema::hasTable('order_updates')) {
                OrderUpdate::query()->create([
                    'order_id' => $order->id,
                    'user_id' => $user->id,
                    'note' => 'Order status updated to ' . $targetStatus . '.',
                ]);
            }

            $this->storeHistory($user, $order);
        });

        return [
            'success' => true,
            'message' => translate('Delivery status updated!'),
        ];
    }

    public function cancelRequest(User $user, int $orderId): array
    {
        $this->dashboardService->ensureDeliveryBoy($user);

        $order = $this->assignedOrder($user, $orderId);

        if (in_array($order->delivery_status, ['delivered', 'cancelled'], true)) {
            throw new HttpException(422, 'Cancel request can not be submitted for this order.');
        }

        if ((int) ($order->cancel_request ?? 0) === 1) {
            throw new HttpException(422, 'Cancel request already submitted.');
        }

        $order->cancel_request = 1;
        if (Schema::hasColumn('orders', 'cancel_request_at')) {
            $order->cancel_request_at = now();
        }
        $order->save();

        return [
            'success' => true,
            'message' => translate('Cancel request submitted successfully!'),
        ];
    }

    private function assignedBaseQuery(User $user)
    {
        $this->dashboardService->ensureDeliveryBoy($user);

        return Order::query()
            ->with('combined_order')
            ->where('assign_delivery_boy', $user->id);
    }

    private function assignedOrder(User $user, int $orderId): Order
    {
        $order = Order::query()
            ->with('combined_order')
            ->findOrFail($orderId);

        if ((int) ($order->assign_delivery_boy ?? 0) !== (int) $user->id) {
            throw (new ModelNotFoundException())->setModel(Order::class, [$orderId]);
        }

        return $order;
    }

    private function allowedTransitions(): array
    {
        return [
            'pending' => ['picked_up'],
            'confirmed' => ['picked_up'],
            'picked_up' => ['on_the_way'],
            'on_the_way' => ['delivered'],
        ];
    }

    private function storeHistory(User $user, Order $order): void
    {
        $history = new DeliveryHistory();
        $history->order_id = $order->id;
        $history->delivery_boy_id = $user->id;
        $history->delivery_status = $order->delivery_status;
        $history->payment_type = $order->payment_type;

        if ($order->delivery_status === 'delivered') {
            $deliveryBoy = DeliveryBoy::query()->firstOrCreate(
                ['user_id' => $user->id],
                ['total_collection' => 0, 'total_earning' => 0]
            );

            if ((string) get_setting('delivery_boy_payment_type') === 'commission') {
                $commission = (float) get_setting('delivery_boy_commission', 0);
                $history->earning = $commission;
                $deliveryBoy->total_earning = (float) ($deliveryBoy->total_earning ?? 0) + $commission;
            }

            if ($order->payment_type === 'cash_on_delivery') {
                $history->collection = (float) $order->grand_total;
                $deliveryBoy->total_collection = (float) ($deliveryBoy->total_collection ?? 0) + (float) $order->grand_total;
            }

            $deliveryBoy->save();
        }

        $history->save();
    }
}
