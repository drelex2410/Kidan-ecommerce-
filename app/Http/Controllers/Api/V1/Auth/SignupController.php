<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\SignupRequest;
use App\Http\Resources\Api\V1\Auth\AuthResponseResource;
use App\Services\Auth\RegistrationService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SignupController extends Controller
{
    public function __invoke(SignupRequest $request, RegistrationService $registrationService): AuthResponseResource|JsonResponse
    {
        try {
            $result = $registrationService->register($request->validated());
        } catch (HttpException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], $exception->getStatusCode());
        }

        $token = $result['verified']
            ? $result['user']->createToken($request->validated()['device_name'] ?? 'customer')->plainTextToken
            : null;

        return new AuthResponseResource([
            'message' => $result['verified']
                ? 'Registration successful.'
                : ($result['channel'] === 'phone'
                    ? 'A verification code has been sent to your phone.'
                    : 'A verification code has been sent to your email.'),
            'verified' => $result['verified'],
            'token' => $token,
            'user' => $result['user'],
            'followed_shops' => [],
        ]);
    }
}
