<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ResendCodeRequest;
use App\Http\Resources\Api\V1\Auth\AuthResponseResource;
use App\Services\Auth\VerificationService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ResendCodeController extends Controller
{
    public function __invoke(ResendCodeRequest $request, VerificationService $verificationService): AuthResponseResource|JsonResponse
    {
        try {
            $result = $verificationService->resend($request->validated());
        } catch (HttpException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], $exception->getStatusCode());
        }

        return new AuthResponseResource([
            'message' => $result['channel'] === 'phone'
                ? 'A verification code has been sent to your phone.'
                : 'A verification code has been sent to your email.',
            'verified' => false,
            'token' => null,
            'user' => $result['user'],
            'followed_shops' => $result['user']->followed_shops()->pluck('shops.id')->values()->all(),
        ]);
    }
}
