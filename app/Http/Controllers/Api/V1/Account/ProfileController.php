<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Account\UpdateProfileRequest;
use App\Http\Resources\Api\V1\Auth\AuthUserResource;
use App\Services\Account\ProfileService;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    public function __construct(private readonly ProfileService $profileService)
    {
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->profileService->update(
            $request->user(),
            $request->validated(),
            $request->file('avatar')
        );

        return response()->json([
            'success' => true,
            'message' => translate('Profile information has been updated successfully'),
            'user' => (new AuthUserResource($user))->resolve(),
        ]);
    }
}
