<?php

namespace App\Http\Controllers\Api\V1\Checkout;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Checkout\StoreOrderRequest;
use App\Services\Checkout\CheckoutException;
use App\Services\Checkout\OrderService;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function __invoke(StoreOrderRequest $request, OrderService $orderService): JsonResponse
    {
        try {
            $result = $orderService->place(
                $request->user('api'),
                $request->validated(),
                $request->file('receipt')
            );
        } catch (CheckoutException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], $exception->status());
        }

        return response()->json($result);
    }
}
