<?php

namespace App\Http\Controllers\Api\V1\Checkout;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Checkout\AddCartItemRequest;
use App\Http\Requests\Api\V1\Checkout\CartIndexRequest;
use App\Http\Requests\Api\V1\Checkout\ChangeCartQuantityRequest;
use App\Http\Requests\Api\V1\Checkout\DestroyCartItemRequest;
use App\Http\Resources\Api\V1\Checkout\CartItemCollection;
use App\Http\Resources\Api\V1\Checkout\CartItemResource;
use App\Http\Resources\Api\V1\Checkout\CartShopCollection;
use App\Http\Resources\Api\V1\Checkout\CartShopResource;
use App\Services\Checkout\CartService;
use App\Services\Checkout\CheckoutException;
use Illuminate\Http\JsonResponse;

class CartController extends Controller
{
    public function index(CartIndexRequest $request, CartService $cartService): JsonResponse
    {
        $cart = $cartService->fetch($request->user('api'), $request->validated('temp_user_id'));

        return response()->json([
            'success' => true,
            'cart_items' => new CartItemCollection($cart['items']),
            'shops' => new CartShopCollection($cart['shops']),
            'summary' => $cart['summary'],
        ]);
    }

    public function add(AddCartItemRequest $request, CartService $cartService): JsonResponse
    {
        try {
            $result = $cartService->add(
                $request->user('api'),
                $request->validated('temp_user_id'),
                (int) $request->validated('variation_id'),
                (int) $request->validated('qty')
            );
        } catch (CheckoutException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], $exception->status());
        }

        return response()->json([
            'success' => true,
            'data' => new CartItemResource($result['item']),
            'shop' => new CartShopResource($result['shop']),
            'summary' => $result['summary'],
            'message' => 'Product added to cart successfully',
        ]);
    }

    public function changeQuantity(ChangeCartQuantityRequest $request, CartService $cartService): JsonResponse
    {
        try {
            $result = $cartService->changeQuantity(
                $request->user('api'),
                $request->validated('temp_user_id'),
                (int) $request->validated('cart_id'),
                $request->validated('type')
            );
        } catch (CheckoutException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], $exception->status());
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'summary' => $result['summary'],
        ]);
    }

    public function destroy(DestroyCartItemRequest $request, CartService $cartService): JsonResponse
    {
        try {
            $result = $cartService->destroy(
                $request->user('api'),
                $request->validated('temp_user_id'),
                (int) $request->validated('cart_id')
            );
        } catch (CheckoutException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], $exception->status());
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'summary' => $result['summary'],
        ]);
    }
}
