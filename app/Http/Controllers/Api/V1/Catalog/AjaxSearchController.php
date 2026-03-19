<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Catalog\BrandCollection;
use App\Http\Resources\Api\V1\Catalog\CategoryCollection;
use App\Http\Resources\Api\V1\Catalog\ProductCardCollection;
use App\Http\Resources\Api\V1\Catalog\ShopSuggestionCollection;
use App\Services\Catalog\AjaxSearchService;
use Illuminate\Http\JsonResponse;

class AjaxSearchController extends Controller
{
    public function __invoke(string $keyword, AjaxSearchService $ajaxSearchService): JsonResponse
    {
        $result = $ajaxSearchService->search($keyword);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
            ]);
        }

        return response()->json([
            'success' => true,
            'keywords' => $result['keywords'],
            'categories' => (new CategoryCollection($result['categories']))->toArray(request())['data'],
            'brands' => (new BrandCollection($result['brands']))->toArray(request())['data'],
            'products' => new ProductCardCollection($result['products']),
            'shops' => new ShopSuggestionCollection($result['shops']),
        ]);
    }
}
