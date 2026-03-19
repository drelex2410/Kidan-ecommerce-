<?php

namespace App\Http\Controllers\Api\V1\Delivery;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Delivery\CancelDeliveryRequest;
use App\Http\Requests\Api\V1\Delivery\UpdateDeliveryStatusRequest;
use App\Http\Resources\Api\V1\Delivery\DeliveryLedgerResource;
use App\Http\Resources\Api\V1\Delivery\DeliveryOrderCollection;
use App\Services\Delivery\DeliveryOrderService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DeliveryOrdersController extends Controller
{
    public function __construct(private readonly DeliveryOrderService $deliveryOrderService)
    {
    }

    public function assigned(Request $request): JsonResponse
    {
        return $this->paginatedOrderResponse(fn () => $this->deliveryOrderService->assigned($this->authenticatedUser($request)));
    }

    public function pending(Request $request): JsonResponse
    {
        return $this->paginatedOrderResponse(fn () => $this->deliveryOrderService->pending($this->authenticatedUser($request)));
    }

    public function pickedUp(Request $request): JsonResponse
    {
        return $this->paginatedOrderResponse(fn () => $this->deliveryOrderService->pickedUp($this->authenticatedUser($request)));
    }

    public function onTheWay(Request $request): JsonResponse
    {
        return $this->paginatedOrderResponse(fn () => $this->deliveryOrderService->onTheWay($this->authenticatedUser($request)));
    }

    public function completed(Request $request): JsonResponse
    {
        return $this->paginatedOrderResponse(fn () => $this->deliveryOrderService->completed($this->authenticatedUser($request)));
    }

    public function cancelled(Request $request): JsonResponse
    {
        return $this->paginatedOrderResponse(fn () => $this->deliveryOrderService->cancelled($this->authenticatedUser($request)));
    }

    public function collections(Request $request): JsonResponse
    {
        return $this->ledgerResponse(fn () => $this->deliveryOrderService->collections($this->authenticatedUser($request)));
    }

    public function earnings(Request $request): JsonResponse
    {
        return $this->ledgerResponse(fn () => $this->deliveryOrderService->earnings($this->authenticatedUser($request)));
    }

    public function updateStatus(UpdateDeliveryStatusRequest $request): JsonResponse
    {
        try {
            $response = $this->deliveryOrderService->updateStatus(
                $this->authenticatedUser($request),
                (int) $request->validated('order_id'),
                (string) $request->validated('status')
            );
        } catch (AccessDeniedHttpException) {
            return response()->json([
                'success' => false,
                'message' => translate('Unauthorized action.'),
            ], 403);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => translate('Order not found.'),
            ], 404);
        } catch (HttpException $exception) {
            return response()->json([
                'success' => false,
                'message' => translate($exception->getMessage()),
            ], $exception->getStatusCode());
        }

        return response()->json($response);
    }

    public function cancelRequest(CancelDeliveryRequest $request): JsonResponse
    {
        try {
            $response = $this->deliveryOrderService->cancelRequest(
                $this->authenticatedUser($request),
                (int) $request->validated('order_id')
            );
        } catch (AccessDeniedHttpException) {
            return response()->json([
                'success' => false,
                'message' => translate('Unauthorized action.'),
            ], 403);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => translate('Order not found.'),
            ], 404);
        } catch (HttpException $exception) {
            return response()->json([
                'success' => false,
                'message' => translate($exception->getMessage()),
            ], $exception->getStatusCode());
        }

        return response()->json($response);
    }

    private function paginatedOrderResponse(callable $resolver): JsonResponse
    {
        try {
            $orders = $resolver();
        } catch (HttpException $exception) {
            return response()->json([
                'success' => false,
                'message' => translate($exception->getMessage()),
                'status' => $exception->getStatusCode(),
            ], $exception->getStatusCode());
        }

        return response()->json([
            'status' => 200,
            'success' => true,
            'data' => [
                'data' => (new DeliveryOrderCollection($orders->items()))->resolve()['data'],
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    private function ledgerResponse(callable $resolver): JsonResponse
    {
        try {
            $history = $resolver();
        } catch (HttpException $exception) {
            return response()->json([
                'success' => false,
                'message' => translate($exception->getMessage()),
                'status' => $exception->getStatusCode(),
            ], $exception->getStatusCode());
        }

        return response()->json([
            'status' => 200,
            'success' => true,
            'data' => collect($history->items())->map(fn ($item) => (new DeliveryLedgerResource($item))->resolve())->all(),
            'meta' => [
                'current_page' => $history->currentPage(),
                'last_page' => $history->lastPage(),
                'per_page' => $history->perPage(),
                'total' => $history->total(),
            ],
        ]);
    }

    private function authenticatedUser(Request $request)
    {
        $token = $request->bearerToken();
        $tokenable = $token ? PersonalAccessToken::findToken($token)?->tokenable : null;

        return $tokenable ?? $request->user();
    }
}
