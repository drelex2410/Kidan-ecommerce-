<?php

namespace App\Http\Controllers\Api\V1\Benefits;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Benefits\ConvertClubPointRequest;
use App\Http\Resources\Api\V1\Benefits\ClubPointHistoryResource;
use App\Services\Benefits\ClubPointService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ClubPointController extends Controller
{
    public function __construct(private readonly ClubPointService $clubPointService)
    {
    }

    public function history(Request $request): JsonResponse
    {
        try {
            $history = $this->clubPointService->historyForUser($request->user());
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
            'data' => ClubPointHistoryResource::collection($history->items())->resolve(),
            'meta' => [
                'current_page' => $history->currentPage(),
                'last_page' => $history->lastPage(),
                'per_page' => $history->perPage(),
                'total' => $history->total(),
            ],
        ]);
    }

    public function convert(ConvertClubPointRequest $request)
    {
        try {
            return $this->clubPointService->convertToWallet($request->user(), (int) $request->validated('id'));
        } catch (AccessDeniedHttpException) {
            return response()->json([
                'success' => false,
                'status' => 401,
                'message' => translate('Unauthorized action.'),
            ], 401);
        } catch (HttpException $exception) {
            return response()->json([
                'success' => false,
                'status' => $exception->getStatusCode(),
                'message' => translate($exception->getMessage()),
            ], $exception->getStatusCode());
        }
    }
}
