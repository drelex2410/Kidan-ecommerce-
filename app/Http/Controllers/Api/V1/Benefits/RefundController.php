<?php

namespace App\Http\Controllers\Api\V1\Benefits;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Benefits\StoreRefundRequest;
use App\Http\Resources\Api\V1\Account\OrderPackageResource;
use App\Http\Resources\Api\V1\Benefits\RefundRequestResource;
use App\Services\Benefits\RefundService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RefundController extends Controller
{
    public function __construct(private readonly RefundService $refundService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $refunds = $this->refundService->listForUser($request->user());

        return response()->json([
            'success' => true,
            'status' => 200,
            'data' => RefundRequestResource::collection($refunds->items())->resolve(),
            'meta' => [
                'current_page' => $refunds->currentPage(),
                'last_page' => $refunds->lastPage(),
                'per_page' => $refunds->perPage(),
                'total' => $refunds->total(),
            ],
        ]);
    }

    public function create(Request $request, int $order_id): JsonResponse
    {
        try {
            $context = $this->refundService->createContext($request->user(), $order_id);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => translate('No order found by this id'),
                'status' => 404,
            ], 404);
        } catch (AccessDeniedHttpException) {
            return response()->json([
                'success' => false,
                'message' => translate("This order is not your. You can't send refund request for this order"),
                'status' => 200,
            ]);
        } catch (HttpException $exception) {
            return response()->json([
                'success' => false,
                'message' => translate($exception->getMessage()),
                'status' => 200,
            ]);
        }

        return response()->json([
            'success' => true,
            'order_code' => $context['order_code'],
            'order' => (new OrderPackageResource($context['order']))->resolve(),
            'has_refund_request' => $context['has_refund_request'],
        ]);
    }

    public function store(StoreRefundRequest $request): JsonResponse
    {
        try {
            $response = $this->refundService->store(
                $request->user(),
                $request->validated(),
                $request->file('attachments', [])
            );
        } catch (AccessDeniedHttpException | HttpException $exception) {
            return response()->json([
                'success' => false,
                'message' => translate($exception->getMessage()),
                'status' => $exception->getStatusCode(),
            ], $exception->getStatusCode() === 401 ? 401 : 200);
        }

        return response()->json($response);
    }
}
