<?php

namespace App\Services\Shops;

use App\Models\Attribute;
use App\Models\AttributeCategory;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\Shop;
use App\Utility\CategoryUtility;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ShopPublicService
{
    public function list(array $filters, int $perPage = 12): LengthAwarePaginator
    {
        $query = $this->visibleShops()
            ->with(['categories.category_translations', 'top_3_products.product_translations', 'top_3_products.taxes', 'top_3_products.variations'])
            ->withCount(['products', 'reviews']);

        if (!empty($filters['category_id'])) {
            $query->whereHas('shop_categories', fn ($builder) => $builder->where('category_id', (int) $filters['category_id']));
        }

        if (!empty($filters['brand_id'])) {
            $query->whereHas('brands', fn ($builder) => $builder->where('brand_id', (int) $filters['brand_id']));
        }

        return $query->paginate($perPage);
    }

    public function findBySlug(string $slug): Shop
    {
        $shop = $this->visibleShops()
            ->with(['categories.category_translations'])
            ->withCount(['reviews'])
            ->where('slug', $slug)
            ->first();

        if (!$shop) {
            throw (new ModelNotFoundException())->setModel(Shop::class, [$slug]);
        }

        return $shop;
    }

    public function home(string $slug): array
    {
        $shop = $this->findBySlug($slug);
        $productsQuery = $this->shopProductsQuery($shop->id);

        $featuredIds = collect(json_decode((string) ($shop->featured_products ?? '[]'), true) ?: [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->all();

        return [
            'featured_products' => Product::query()
                ->with(['product_translations', 'taxes', 'variations'])
                ->whereIn('id', $featuredIds)
                ->frontendVisible()
                ->get(),
            'new_arrival_products' => (clone $productsQuery)->latest()->limit(10)->get(),
            'best_rated_products' => (clone $productsQuery)->orderByDesc('rating')->limit(10)->get(),
            'best_selling_products' => (clone $productsQuery)->orderByDesc('num_of_sale')->limit(10)->get(),
            'latest_coupons' => $this->validCouponsQuery($shop->id)->limit(5)->get(),
            'banner_section_one' => $this->normalizeBanners($shop->banners_1),
            'banner_section_two' => $this->normalizeBanners($shop->banners_2),
            'banner_section_three' => $this->normalizeBanners($shop->banners_3),
            'banner_section_four' => $this->normalizeBanners($shop->banners_4),
        ];
    }

    public function coupons(string $slug)
    {
        $shop = $this->findBySlug($slug);

        return $this->validCouponsQuery($shop->id)->get();
    }

    public function products(string $slug, array $filters): array
    {
        $shop = $this->findBySlug($slug);
        $query = $this->shopProductsQuery($shop->id);

        $category = !empty($filters['category_slug'])
            ? Category::query()->with('category_translations')->where('slug', $filters['category_slug'])->first()
            : null;

        $keyword = trim((string) ($filters['keyword'] ?? ''));
        $brandIds = array_values(array_filter(array_map('intval', explode(',', (string) ($filters['brand_ids'] ?? '')))));
        $attributeValues = array_values(array_filter(array_map('intval', explode(',', (string) ($filters['attribute_values'] ?? '')))));
        $minPrice = $filters['min_price'] ?? null;
        $maxPrice = $filters['max_price'] ?? null;
        $sortBy = $filters['sort_by'] ?? 'popular';

        $shopCategoriesQuery = $shop->categories()->with('category_translations');
        $rootCategories = (clone $shopCategoriesQuery)->where('level', 0)->orderByDesc('order_level')->get();
        $allBrands = $shop->brands()->with('brand_translations')->get();
        $attributes = Attribute::query()->with(['attribute_translations', 'attribute_values.attribute_value_translations'])->whereIn('id', $shopCategoriesQuery->pluck('categories.id')->all())->get();

        if ($category) {
            $categoryIds = CategoryUtility::children_ids($category->id);
            $categoryIds[] = $category->id;

            $query->whereHas('product_categories', fn ($builder) => $builder->whereIn('category_id', $categoryIds));

            $attributeIds = AttributeCategory::query()
                ->whereIn('category_id', $categoryIds)
                ->pluck('attribute_id')
                ->all();

            $attributes = Attribute::query()
                ->with(['attribute_translations', 'attribute_values.attribute_value_translations'])
                ->whereIn('id', $attributeIds)
                ->get();
        }

        if ($brandIds !== []) {
            $query->whereIn('brand_id', $brandIds);
        }

        if ($keyword !== '') {
            $query->where(function ($builder) use ($keyword) {
                foreach (preg_split('/\s+/', $keyword) as $word) {
                    $builder->orWhere('name', 'like', '%' . $word . '%')
                        ->orWhere('tags', 'like', '%' . $word . '%');
                }
            });
        }

        if ($minPrice !== null && $minPrice !== '') {
            $query->where('lowest_price', '>=', (float) $minPrice);
        }

        if ($maxPrice !== null && $maxPrice !== '') {
            $query->where('highest_price', '<=', (float) $maxPrice);
        }

        if ($attributeValues !== []) {
            $query->whereHas('attribute_values', fn ($builder) => $builder->whereIn('attribute_value_id', $attributeValues));
        }

        match ($sortBy) {
            'latest' => $query->orderByDesc('created_at'),
            'oldest' => $query->orderBy('created_at'),
            'highest_price' => $query->orderByDesc('highest_price'),
            'lowest_price' => $query->orderBy('lowest_price'),
            default => $query->orderByDesc('num_of_sale'),
        };

        $products = $query->paginate(20);

        return [
            'products' => $products,
            'totalPage' => $products->lastPage(),
            'currentPage' => $products->currentPage(),
            'total' => $products->total(),
            'parentCategory' => $category && (int) $category->parent_id !== 0
                ? Category::query()->with('category_translations')->find($category->parent_id)
                : null,
            'currentCategory' => $category,
            'childCategories' => $category
                ? $category->childrenCategories()->with('category_translations')->get()
                : collect(),
            'rootCategories' => $rootCategories,
            'allBrands' => $allBrands,
            'attributes' => $attributes,
        ];
    }

    private function visibleShops()
    {
        return Shop::query()
            ->where('published', 1)
            ->where('approval', 1)
            ->where('verification_status', 1);
    }

    private function shopProductsQuery(int $shopId)
    {
        return Product::query()
            ->with(['product_translations', 'taxes', 'variations', 'product_categories', 'attribute_values'])
            ->where('shop_id', $shopId)
            ->frontendVisible();
    }

    private function validCouponsQuery(int $shopId)
    {
        $now = time();

        return Coupon::query()
            ->where('shop_id', $shopId)
            ->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now);
    }

    private function normalizeBanners(?string $payload): array
    {
        if (!$payload) {
            return [];
        }

        $decoded = json_decode($payload, true);
        if (!is_array($decoded)) {
            return [];
        }

        if (isset($decoded['images']) && is_array($decoded['images'])) {
            return collect($decoded['images'])
                ->map(function ($image, $index) use ($decoded) {
                    return [
                        'img' => api_asset($image),
                        'link' => $decoded['links'][$index] ?? null,
                    ];
                })
                ->filter(fn (array $banner) => !empty($banner['img']))
                ->values()
                ->all();
        }

        return collect($decoded)
            ->map(function ($banner) {
                if (is_string($banner)) {
                    return [
                        'img' => api_asset($banner),
                        'link' => null,
                    ];
                }

                if (is_array($banner)) {
                    return [
                        'img' => api_asset($banner['img'] ?? null),
                        'link' => $banner['link'] ?? null,
                    ];
                }

                return null;
            })
            ->filter(fn ($banner) => is_array($banner) && !empty($banner['img']))
            ->values()
            ->all();
    }
}
