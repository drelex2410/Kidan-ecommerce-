<?php

namespace App\Services\Delivery;

use App\Models\DeliveryBoy;
use App\Models\User;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DeliveryDashboardService
{
    public function summary(User $user): array
    {
        $this->ensureDeliveryBoy($user);

        $deliveryBoy = DeliveryBoy::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['total_collection' => 0, 'total_earning' => 0]
        );

        $assignedOrders = \App\Models\Order::query()->where('assign_delivery_boy', $user->id);

        return [
            'deliveryboy' => [
                'total_collection' => (double) ($deliveryBoy->total_collection ?? 0),
                'total_earning' => (double) ($deliveryBoy->total_earning ?? 0),
            ],
            'total_complete_delivery' => (clone $assignedOrders)->where('delivery_status', 'delivered')->count(),
            'total_pending_delivery' => (clone $assignedOrders)
                ->where('delivery_status', '!=', 'delivered')
                ->where('delivery_status', '!=', 'cancelled')
                ->where('cancel_request', '0')
                ->count(),
        ];
    }

    public function ensureDeliveryBoy(User $user): void
    {
        if ($user->user_type !== 'delivery_boy') {
            throw new HttpException(403, 'This action is only available for delivery boys.');
        }
    }
}
