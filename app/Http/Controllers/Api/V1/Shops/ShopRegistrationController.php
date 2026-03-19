<?php

namespace App\Http\Controllers\Api\V1\Shops;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Shops\RegisterShopRequest;
use App\Services\Shops\ShopRegistrationService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ShopRegistrationController extends Controller
{
    public function __construct(private readonly ShopRegistrationService $registrationService)
    {
    }

    public function __invoke(RegisterShopRequest $request): JsonResponse
    {
        try {
            $response = $this->registrationService->register($request->validated());
        } catch (HttpException $exception) {
            return response()->json([
                'success' => false,
                'message' => translate($exception->getMessage()),
            ], $exception->getStatusCode());
        }

        return response()->json($response);
    }
}
