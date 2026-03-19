<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\VerifyRequest;
use App\Http\Resources\Api\V1\Auth\AuthResponseResource;
use App\Services\Auth\VerificationService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

class VerifyController extends Controller
{
    public function __invoke(VerifyRequest $request, VerificationService $verificationService): AuthResponseResource|JsonResponse
    {
        try {
            $result = $verificationService->verify($request->validated());
        } catch (HttpException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], $exception->getStatusCode());
        }

        return new AuthResponseResource([
            'message' => 'Account verified successfully.',
            'verified' => true,
            'token' => $result['token'],
            'user' => $result['user'],
            'followed_shops' => $result['user']->followed_shops()->pluck('shops.id')->values()->all(),
        ]);
    }
}
