<?php

namespace App\Http\Controllers\Api\V1\Benefits;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Benefits\WalletHistoryResource;
use App\Services\Benefits\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class WalletController extends Controller
{
    public function __construct(private readonly WalletService $walletService)
    {
    }

    public function recharge(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'status' => 422,
            'message' => translate('Wallet recharge must be initialized through the payment gateway endpoint.'),
        ], 422);
    }

    public function history(Request $request): JsonResponse
    {
        try {
            $history = $this->walletService->historyForUser($request->user());
        } catch (HttpException $exception) {
            return response()->json([
                'success' => false,
                'status' => $exception->getStatusCode(),
                'message' => translate($exception->getMessage()),
            ], $exception->getStatusCode());
        }

        return response()->json([
            'success' => true,
            'status' => 200,
            'data' => WalletHistoryResource::collection($history->items())->resolve(),
            'meta' => [
                'current_page' => $history->currentPage(),
                'last_page' => $history->lastPage(),
                'per_page' => $history->perPage(),
                'total' => $history->total(),
            ],
        ]);
    }
}
