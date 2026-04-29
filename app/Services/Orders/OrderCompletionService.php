<?php

namespace App\Services\Orders;

use App\Models\Order;
use Illuminate\Support\Facades\Schema;

class OrderCompletionService
{
    public function sync(Order $order, ?string $targetStatus = null): void
    {
        if (! Schema::hasColumn('orders', 'completed_at')) {
            return;
        }

        $status = $targetStatus ?? (string) $order->delivery_status;

        if ($status === 'delivered') {
            $order->completed_at = $order->completed_at ?: now();
        }
    }
}
