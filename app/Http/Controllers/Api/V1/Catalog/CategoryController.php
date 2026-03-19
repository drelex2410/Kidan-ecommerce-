<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Catalog\AllCategoryCollection;
use App\Http\Resources\Api\V1\Catalog\CategoryCollection;
use App\Services\Catalog\CategoryTreeService;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function index(CategoryTreeService $categoryTreeService): JsonResponse
    {
        $resource = new AllCategoryCollection($categoryTreeService->allCategories());

        return response()->json([
            'success' => true,
            ...$resource->toArray(request()),
            'status' => 200,
        ]);
    }

    public function firstLevel(CategoryTreeService $categoryTreeService): JsonResponse
    {
        $resource = new CategoryCollection($categoryTreeService->firstLevelCategories());

        return response()->json([
            'success' => true,
            ...$resource->toArray(request()),
            'status' => 200,
        ]);
    }
}
