<?php

namespace App\Http\Controllers\Api\V1\Checkout;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Checkout\ApplyCouponRequest;
use App\Services\Checkout\CheckoutException;
use App\Services\Checkout\CouponService;
use Illuminate\Http\JsonResponse;

class CouponController extends Controller
{
    public function __invoke(ApplyCouponRequest $request, CouponService $couponService): JsonResponse
    {
        try {
            $result = $couponService->apply(
                $request->user('api'),
                $request->validated('coupon_code'),
                $request->validated('shop_id'),
                $request->validated('cart_item_ids')
            );
        } catch (CheckoutException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], $exception->status());
        }

        return response()->json([
            'success' => true,
            'coupon_details' => $result['coupon_details'],
            'discount_amount' => $result['discount_amount'],
            'totals' => $result['totals'],
            'message' => 'Coupon code applied successfully',
        ]);
    }
}
