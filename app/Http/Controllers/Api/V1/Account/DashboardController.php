<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Catalog\ProductCardResource;
use App\Services\Account\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboardService)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $summary = $this->dashboardService->summaryFor($request->user());

        return response()->json([
            'success' => true,
            'last_recharge' => $summary['last_recharge'],
            'total_order_products' => $summary['total_order_products'],
            'recent_purchased_products' => [
                'data' => ProductCardResource::collection($summary['recent_purchased_products'])->resolve(),
            ],
        ]);
    }
}
