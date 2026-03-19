<?php

namespace App\Http\Controllers\Api\V1\Checkout;

use App\Http\Controllers\Controller;
use App\Services\Checkout\CartService;
use App\Services\Checkout\CheckoutException;
use App\Services\Checkout\ShippingService;
use Illuminate\Http\JsonResponse;

class ShippingQuoteController extends Controller
{
    public function __invoke(int $addressId, ShippingService $shippingService, CartService $cartService): JsonResponse
    {
        try {
            $cart = $cartService->fetch(request()->user('api'), null);
            $quote = $shippingService->quote(request()->user('api'), $addressId, max(1, count($cart['shops'])));
        } catch (CheckoutException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'standard_delivery_cost' => 0,
                'express_delivery_cost' => 0,
            ], $exception->status());
        }

        return response()->json($quote);
    }
}
