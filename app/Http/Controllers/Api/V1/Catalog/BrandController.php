<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Catalog\BrandCollection;
use App\Services\Catalog\BrandCatalogService;
use Illuminate\Http\JsonResponse;

class BrandController extends Controller
{
    public function __invoke(BrandCatalogService $brandCatalogService): JsonResponse
    {
        $resource = new BrandCollection($brandCatalogService->all());

        return response()->json([
            'success' => true,
            ...$resource->toArray(request()),
            'status' => 200,
        ]);
    }
}
