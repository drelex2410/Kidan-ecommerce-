<?php

namespace App\Http\Controllers\Api\V1\Checkout;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Checkout\GuestShippingQuoteRequest;
use App\Services\Checkout\CartService;
use App\Services\Checkout\CheckoutException;
use App\Services\Checkout\ShippingService;
use Illuminate\Http\JsonResponse;

class ShippingQuoteController extends Controller
{
    public function __invoke(int $addressId, ShippingService $shippingService, CartService $cartService): JsonResponse
    {
        try {
            $cart = $cartService->fetch(request()->user('api'), request()->input('temp_user_id'));
            $quote = $shippingService->quoteForAddress(request()->user('api'), $addressId, max(1, count($cart['shops'])));
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

    public function guestQuote(GuestShippingQuoteRequest $request, ShippingService $shippingService): JsonResponse
    {
        $quote = $shippingService->quoteForGuestCity(
            (int) $request->validated('guest_city_id'),
            (int) ($request->validated('shop_count') ?? 1)
        );

        return response()->json($quote);
    }
}
