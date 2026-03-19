<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Catalog\ProductCardCollection;
use App\Http\Resources\Api\V1\Catalog\ProductDetailResource;
use App\Services\Catalog\ProductDetailsService;
use Illuminate\Http\JsonResponse;

class ProductDetailsController extends Controller
{
    public function show(string $product_slug, ProductDetailsService $productDetailsService): JsonResponse
    {
        $product = $productDetailsService->findBySlug($product_slug);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => (new ProductDetailResource($product))->toArray(request()),
        ]);
    }

    public function related(int $product_id, ProductDetailsService $productDetailsService): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => (new ProductCardCollection($productDetailsService->related($product_id)))->toArray(request())['data'],
        ]);
    }

    public function boughtTogether(int $product_id, ProductDetailsService $productDetailsService): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => (new ProductCardCollection($productDetailsService->boughtTogether($product_id)))->toArray(request())['data'],
        ]);
    }

    public function random(int $limit, ProductDetailsService $productDetailsService, ?int $product_id = null): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => (new ProductCardCollection($productDetailsService->random(max(1, min($limit, 50)), $product_id)))->toArray(request())['data'],
        ]);
    }

    public function latest(int $limit, ProductDetailsService $productDetailsService): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => (new ProductCardCollection($productDetailsService->latest(max(1, min($limit, 50)))))->toArray(request())['data'],
        ]);
    }
}
