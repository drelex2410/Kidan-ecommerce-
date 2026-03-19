<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Catalog\ProductSearchRequest;
use App\Http\Resources\Api\V1\Catalog\ProductListingResource;
use App\Services\Catalog\CatalogSearchService;
use Illuminate\Http\JsonResponse;

class ProductSearchController extends Controller
{
    public function __invoke(ProductSearchRequest $request, CatalogSearchService $catalogSearchService): JsonResponse
    {
        return response()->json((new ProductListingResource($catalogSearchService->search([
            ...$request->validated(),
            'brand_ids' => $request->brandIds(),
            'attribute_values' => $request->attributeValueIds(),
        ])))->resolve($request));
    }
}
