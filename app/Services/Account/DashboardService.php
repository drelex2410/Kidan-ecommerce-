<?php

namespace App\Services\Account;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class DashboardService
{
    public function summaryFor(User $user): array
    {
        $userOrderIds = Order::query()
            ->where('user_id', $user->id)
            ->pluck('id');

        $totalOrderProducts = OrderDetail::query()
            ->whereIn('order_id', $userOrderIds)
            ->distinct('product_variation_id')
            ->count('product_variation_id');

        $recentProductIds = OrderDetail::query()
            ->whereIn('order_id', $userOrderIds)
            ->latest('id')
            ->pluck('product_id')
            ->filter()
            ->unique()
            ->take(10);

        $recentProducts = Product::query()
            ->with(['variations', 'product_translations', 'taxes'])
            ->whereIn('id', $recentProductIds)
            ->get()
            ->sortBy(fn ($product) => $recentProductIds->search($product->id))
            ->values();

        $lastRecharge = null;

        if (Schema::hasTable('wallets')) {
            $lastRecharge = Wallet::query()
                ->where('user_id', $user->id)
                ->latest()
                ->first();
        }

        return [
            'last_recharge' => [
                'amount' => $lastRecharge ? (float) $lastRecharge->amount : 0.00,
                'date' => $lastRecharge && $lastRecharge->created_at ? $lastRecharge->created_at->format('d.m.Y') : '',
            ],
            'total_order_products' => $totalOrderProducts,
            'recent_purchased_products' => $recentProducts,
        ];
    }
}
