<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Account\CouponResource;
use App\Services\Account\AccountCouponService;
use Illuminate\Http\JsonResponse;

class CouponController extends Controller
{
    public function __construct(private readonly AccountCouponService $couponService)
    {
    }

    public function __invoke(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => '',
            'data' => [
                'data' => CouponResource::collection($this->couponService->activeCoupons())->resolve(),
            ],
        ]);
    }
}
