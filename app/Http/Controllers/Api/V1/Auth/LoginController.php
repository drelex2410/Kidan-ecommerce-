<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Resources\Api\V1\Auth\AuthResponseResource;
use App\Services\Auth\AuthenticationService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

class LoginController extends Controller
{
    public function __invoke(LoginRequest $request, AuthenticationService $authenticationService): AuthResponseResource|JsonResponse
    {
        try {
            $result = $authenticationService->login($request->validated());
        } catch (HttpException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], $exception->getStatusCode());
        }

        return new AuthResponseResource([
            'message' => $result['verified'] ? 'Login successful.' : 'Please verify your account.',
            'verified' => $result['verified'],
            'token' => $result['token'],
            'user' => $result['user'],
            'followed_shops' => $result['user']->followed_shops()->pluck('shops.id')->values()->all(),
        ]);
    }
}
