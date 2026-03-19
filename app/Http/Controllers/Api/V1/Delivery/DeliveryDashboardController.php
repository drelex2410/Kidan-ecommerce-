<?php

namespace App\Http\Controllers\Api\V1\Delivery;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Delivery\DeliveryDashboardResource;
use App\Services\Delivery\DeliveryDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DeliveryDashboardController extends Controller
{
    public function __construct(private readonly DeliveryDashboardService $dashboardService)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $data = $this->dashboardService->summary($this->authenticatedUser($request));
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
            'data' => (new DeliveryDashboardResource($data))->resolve(),
        ], 200, [], JSON_PRESERVE_ZERO_FRACTION);
    }

    private function authenticatedUser(Request $request)
    {
        $token = $request->bearerToken();
        $tokenable = $token ? PersonalAccessToken::findToken($token)?->tokenable : null;

        return $tokenable ?? $request->user();
    }
}
