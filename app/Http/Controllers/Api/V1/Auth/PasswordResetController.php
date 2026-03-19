<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\PasswordResetRequest;
use App\Services\Auth\PasswordResetService;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PasswordResetController extends Controller
{
    public function __invoke(PasswordResetRequest $request, PasswordResetService $passwordResetService)
    {
        try {
            $passwordResetService->reset($request->validated());
        } catch (HttpException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], $exception->getStatusCode());
        }

        return response()->json([
            'success' => true,
            'message' => 'Your password has been updated.',
        ]);
    }
}
