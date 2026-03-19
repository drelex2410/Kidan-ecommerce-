<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Account\StoreWishlistRequest;
use App\Http\Resources\Api\V1\Account\WishlistProductResource;
use App\Services\Account\WishlistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function __construct(private readonly WishlistService $wishlistService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'status' => 200,
            'data' => WishlistProductResource::collection($this->wishlistService->listForUser($request->user()))->resolve(),
        ]);
    }

    public function store(StoreWishlistRequest $request): JsonResponse
    {
        $product = $this->wishlistService->add($request->user(), (int) $request->validated('product_id'));

        return response()->json([
            'success' => true,
            'message' => translate('Product is successfully added to your wishlist'),
            'product' => (new WishlistProductResource($product))->resolve(),
        ]);
    }

    public function destroy(Request $request, int $product_id): JsonResponse
    {
        $this->wishlistService->remove($request->user(), $product_id);

        return response()->json([
            'success' => true,
            'message' => translate('Product is successfully removed from your wishlist'),
        ]);
    }
}
