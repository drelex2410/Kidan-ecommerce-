<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\PasswordCreateRequest;
use App\Services\Auth\PasswordResetService;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PasswordCreateController extends Controller
{
    public function __invoke(PasswordCreateRequest $request, PasswordResetService $passwordResetService)
    {
        try {
            $result = $passwordResetService->create($request->validated());
        } catch (HttpException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], $exception->getStatusCode());
        }

        return response()->json([
            'success' => true,
            'message' => $result['channel'] === 'phone'
                ? 'A password reset code has been sent to your phone number.'
                : 'A password reset code has been sent to your email.',
            'email' => $result['channel'] === 'email',
            'phone' => $result['channel'] === 'phone',
        ]);
    }
}
