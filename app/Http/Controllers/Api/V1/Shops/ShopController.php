<?php

namespace App\Http\Controllers\Api\V1\Shops;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Catalog\ProductCardCollection;
use App\Http\Resources\Api\V1\Shops\ShopCardCollection;
use App\Http\Resources\Api\V1\Shops\ShopCouponCollection;
use App\Http\Resources\Api\V1\Shops\ShopDetailResource;
use App\Services\Shops\ShopPublicService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function __construct(private readonly ShopPublicService $shopService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $shops = $this->shopService->list($request->only(['category_id', 'brand_id']));

        return response()->json([
            'success' => true,
            'status' => 200,
            'data' => (new ShopCardCollection($shops->items()))->resolve()['data'],
            'meta' => [
                'current_page' => $shops->currentPage(),
                'last_page' => $shops->lastPage(),
                'per_page' => $shops->perPage(),
                'total' => $shops->total(),
            ],
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        try {
            $shop = $this->shopService->findBySlug($slug);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => translate('Shop not found'),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => (new ShopDetailResource($shop))->resolve(),
            'message' => translate('Shop found'),
        ]);
    }

    public function home(string $slug): JsonResponse
    {
        try {
            $data = $this->shopService->home($slug);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => translate('Shop not found'),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'featured_products' => new ProductCardCollection($data['featured_products']),
                'new_arrival_products' => new ProductCardCollection($data['new_arrival_products']),
                'best_rated_products' => new ProductCardCollection($data['best_rated_products']),
                'best_selling_products' => new ProductCardCollection($data['best_selling_products']),
                'latest_coupons' => new ShopCouponCollection($data['latest_coupons']),
                'banner_section_one' => $data['banner_section_one'],
                'banner_section_two' => $data['banner_section_two'],
                'banner_section_three' => $data['banner_section_three'],
                'banner_section_four' => $data['banner_section_four'],
            ],
            'message' => translate('Shop found'),
        ]);
    }

    public function coupons(string $slug): JsonResponse
    {
        try {
            $coupons = $this->shopService->coupons($slug);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => translate('Shop not found'),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'coupons' => new ShopCouponCollection($coupons),
            ],
            'message' => translate('Shop found'),
        ]);
    }

    public function products(Request $request, string $slug): JsonResponse
    {
        try {
            $data = $this->shopService->products($slug, $request->only([
                'category_slug',
                'brand_ids',
                'attribute_values',
                'keyword',
                'sort_by',
                'min_price',
                'max_price',
            ]));
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => translate('Shop not found'),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'products' => new ProductCardCollection($data['products']->items()),
            'totalPage' => $data['totalPage'],
            'currentPage' => $data['currentPage'],
            'total' => $data['total'],
            'parentCategory' => $data['parentCategory'] ? (new \App\Http\Resources\CategorySingleCollection($data['parentCategory']))->resolve() : null,
            'currentCategory' => $data['currentCategory'] ? (new \App\Http\Resources\CategorySingleCollection($data['currentCategory']))->resolve() : null,
            'childCategories' => new \App\Http\Resources\CategoryCollection($data['childCategories']),
            'rootCategories' => new \App\Http\Resources\CategoryCollection($data['rootCategories']),
            'allBrands' => new \App\Http\Resources\BrandCollection($data['allBrands']),
            'attributes' => new \App\Http\Resources\AttributeCollection($data['attributes']),
        ]);
    }
}
