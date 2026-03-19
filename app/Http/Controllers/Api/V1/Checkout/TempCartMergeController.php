<?php

namespace App\Http\Controllers\Api\V1\Checkout;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Checkout\TempCartMergeRequest;
use App\Http\Resources\Api\V1\Checkout\CartItemCollection;
use App\Http\Resources\Api\V1\Checkout\CartShopCollection;
use App\Services\Checkout\CartService;
use App\Services\Checkout\CheckoutException;
use Illuminate\Http\JsonResponse;

class TempCartMergeController extends Controller
{
    public function __invoke(TempCartMergeRequest $request, CartService $cartService): JsonResponse
    {
        try {
            $result = $cartService->mergeTempCart($request->user('api'), $request->validated('temp_user_id'));
        } catch (CheckoutException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], $exception->status());
        }

        return response()->json([
            'success' => true,
            'message' => 'Cart synchronized successfully.',
            'cart_items' => new CartItemCollection($result['items']),
            'shops' => new CartShopCollection($result['shops']),
            'summary' => $result['summary'],
        ]);
    }
}
