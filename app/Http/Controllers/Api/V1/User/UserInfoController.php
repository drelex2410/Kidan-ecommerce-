<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Auth\UserInfoResource;
use App\Services\Auth\CurrentUserService;
use Illuminate\Http\Request;

class UserInfoController extends Controller
{
    public function __invoke(Request $request, CurrentUserService $currentUserService): UserInfoResource
    {
        return new UserInfoResource($currentUserService->payloadFor($request->user()));
    }
}
