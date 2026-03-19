<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Account\StoreFollowRequest;
use App\Http\Resources\Api\V1\Account\FollowedShopResource;
use App\Services\Account\FollowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FollowController extends Controller
{
    public function __construct(private readonly FollowService $followService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'status' => 200,
            'data' => FollowedShopResource::collection($this->followService->listForUser($request->user()))->resolve(),
        ]);
    }

    public function store(StoreFollowRequest $request): JsonResponse
    {
        $this->followService->follow($request->user(), (int) $request->validated('shop_id'));

        return response()->json([
            'success' => true,
            'message' => translate('This Shop is successfully added to your following list.'),
        ]);
    }

    public function destroy(Request $request, int $shop_id): JsonResponse
    {
        $this->followService->unfollow($request->user(), $shop_id);

        return response()->json([
            'success' => true,
            'message' => translate('This Shop is successfully removed from your following list.'),
        ]);
    }
}
