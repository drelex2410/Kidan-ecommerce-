<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\OrderResource;
use App\Http\Resources\RefundRequestCollection;
use App\Services\Benefits\RefundService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RefundRequestController extends Controller
{
    public function __construct(private readonly RefundService $refundService)
    {
    }

    public function index()
    {
        return new RefundRequestCollection(
            $this->refundService->listForUser(auth('api')->user(), 12)
        );
    }

    public function create($order_id)
    {
        try {
            $context = $this->refundService->createContext(auth('api')->user(), (int) $order_id);
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
            'order' => new OrderResource($context['order']),
            'has_refund_request' => $context['has_refund_request'],
        ]);
    }

    public function store(Request $request)
    {
        try {
            return response()->json(
                $this->refundService->store(auth('api')->user(), $request->all(), $request->file('attachments', []))
            );
        } catch (AccessDeniedHttpException | HttpException $exception) {
            return response()->json([
                'success' => false,
                'message' => translate($exception->getMessage()),
                'status' => $exception->getStatusCode(),
            ], $exception->getStatusCode() === 401 ? 401 : 200);
        }
    }
}
