<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Http\Controllers\Controller;
use App\Services\Account\OrderAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class InvoiceDownloadController extends Controller
{
    public function __construct(private readonly OrderAccountService $orderService)
    {
    }

    public function __invoke(Request $request, int $order_id): JsonResponse
    {
        try {
            return response()->json($this->orderService->invoiceDownloadPayload($request->user(), $order_id));
        } catch (AccessDeniedHttpException) {
            return response()->json(null, 401);
        }
    }
}
