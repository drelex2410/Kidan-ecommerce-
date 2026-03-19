<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Account\CombinedOrderResource;
use App\Http\Resources\Api\V1\Account\DownloadableProductResource;
use App\Services\Account\OrderAccountService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class OrderController extends Controller
{
    public function __construct(private readonly OrderAccountService $orderService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $orders = $this->orderService->listForUser($request->user());

        return response()->json([
            'success' => true,
            'status' => 200,
            'data' => CombinedOrderResource::collection($orders->items())->resolve(),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    public function show(Request $request, string $order_code): JsonResponse
    {
        try {
            $order = $this->orderService->findByCodeForUser($request->user(), $order_code);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => translate('No order found by this code'),
                'status' => 404,
            ], 404);
        } catch (AccessDeniedHttpException) {
            return response()->json([
                'success' => false,
                'message' => translate("This order is not your. You can't check details of this order"),
                'status' => 200,
            ], 200);
        }

        return response()->json([
            'success' => true,
            'status' => 200,
            'data' => (new CombinedOrderResource($order))->resolve(),
        ]);
    }

    public function cancel(Request $request, int $order_id): JsonResponse
    {
        try {
            $response = $this->orderService->cancel($request->user(), $order_id);
        } catch (AccessDeniedHttpException) {
            return response()->json(null, 401);
        }

        return response()->json($response);
    }

    public function downloads(Request $request): JsonResponse
    {
        $downloads = $this->orderService->downloadableProducts($request->user());

        return response()->json([
            'success' => true,
            'status' => 200,
            'data' => DownloadableProductResource::collection($downloads->items())->resolve(),
            'meta' => [
                'current_page' => $downloads->currentPage(),
                'last_page' => $downloads->lastPage(),
                'per_page' => $downloads->perPage(),
                'total' => $downloads->total(),
            ],
        ]);
    }
}
