<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\AttributeCollection;
use App\Http\Resources\BrandCollection;
use App\Http\Resources\CategoryCollection;
use App\Http\Resources\CategorySingleCollection;
use App\Http\Resources\ProductCollection;
use App\Http\Resources\ProductSingleCollection;
use App\Http\Resources\ShopCollection;
use App\Models\Attribute;
use App\Models\AttributeCategory;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\ProductAttributeValue;
use App\Models\ProductVariationCombination;
use App\Models\Shop;
use App\Utility\CategoryUtility;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    public function index()
    {
        return new ProductCollection(filter_products(Product::latest())->paginate(10));
    }

    public function show($product_slug)
    {
        $product = filter_products(Product::query())
            ->where('slug', $product_slug)
            ->with(['brand', 'variations', 'variation_combinations', 'shop' => function ($query) {
                $query->withCount('reviews');
            }])
            ->withCount(['reviews', 'reviews_1', 'reviews_2', 'reviews_3', 'reviews_4', 'reviews_5'])
            ->first();
        if ($product) {
            return new ProductSingleCollection($product);
        } else {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => translate('Product not found')
            ]);
        }
    }

    public function get_by_ids(Request $request)
    {
        if ($request->has('product_ids') && is_array($request->product_ids)) {
            return new ProductCollection(filter_products(Product::whereIn('id', $request->product_ids))->get());
        } else {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => translate('Products not found')
            ], 200);
        }
    }

    public function related($product_id)
    {
        $products = filter_products(Product::query())->whereHas('product_categories', function ($query) use ($product_id) {
            $query->whereIn('category_id', Product::find($product_id)->product_categories->pluck('category_id')->toArray());
        })->where('id', '!=', $product_id)->limit(10)->get();
        return new ProductCollection($products);
    }

    public function bought_together($product_id)
    {
        $order_ids = OrderDetail::where('product_id', $product_id)->pluck('order_id')->toArray();
        $product_ids = OrderDetail::distinct()->whereIn('order_id', $order_ids)->where('product_id', '!=', $product_id)->pluck('product_id')->toArray();
        $products = filter_products(Product::whereIn('id', $product_ids))->limit(10)->get();
        return new ProductCollection($products);
    }

    public function random_products($limit, $product_id = null)
    {
        return new ProductCollection(filter_products(Product::where('id', '!=', $product_id))->inRandomOrder()->limit($limit)->get());
    }
    public function latest_products($limit)
    {
        $products = filter_products(Product::query())->latest()->limit($limit)->get();
        Log::info('Frontend latest products query snapshot', [
            'limit' => (int) $limit,
            'result_count' => $products->count(),
            'latest_product_visibility' => $this->latestProductVisibilitySnapshot(),
        ]);

        return new ProductCollection($products);
    }

    public function search(Request $request)
    {
        $brand = $request->brand_ids ? Brand::find(intval($request->brand_ids)) : null;
        $category                   = $request->category_slug ? Category::where('slug', $request->category_slug)->first() : null;
        $search_keyword             = $request->keyword;
        $sort_by                    = $request->sort_by;
        $category_id                = optional($category)->id;
        $brand_ids                  = $request->brand_ids ? explode(',', $request->brand_ids) : null;
        $min_price                  = $request->min_price;
        $max_price                  = $request->max_price;
        $attributes                 = Attribute::with('attribute_values')->get();
        $selected_attribute_values  = $request->attribute_values ? explode(',', $request->attribute_values) : null;

        $products = filter_products(Product::with(['variations']));

        //brand check
        if ($brand_ids != null) {
            $products->whereIn('brand_id', $brand_ids);
        }


        // search keyword check
        if ($search_keyword != null) {
            $products->where(function ($q) use ($search_keyword) {
                foreach (explode(' ', trim($search_keyword)) as $word) {
                    $q->where('name', 'like', '%' . $word . '%')->orWhere('tags', 'like', '%' . $word . '%');
                }
            });
        }

        // category + child category check
        if ($category_id != null) {

            $category_ids = CategoryUtility::children_ids($category_id);
            $category_ids[] = $category_id;

            $products->with('product_categories')->whereHas('product_categories', function ($query) use ($category_ids) {
                return $query->whereIn('category_id', $category_ids);
            });

            $attribute_ids = AttributeCategory::whereIn('category_id', $category_ids)->pluck('attribute_id')->toArray();
            $attributes = Attribute::with('attribute_values')->whereIn('id', $attribute_ids)->get();
        } else {
            $category_ids = [];
            if ($search_keyword != null) {
                foreach (explode(' ', trim($search_keyword)) as $word) {
                    $ids = Category::where('name', 'like', '%' . $word . '%')->pluck('id')->toArray();
                    if (count($ids) > 0) {
                        foreach ($ids as $id) {
                            $category_ids[] = $id;
                            array_merge($category_ids, CategoryUtility::children_ids($id));
                        }
                    }
                }

                $attribute_ids = AttributeCategory::whereIn('category_id', $category_ids)->pluck('attribute_id')->toArray();
                $attributes = Attribute::with('attribute_values')->whereIn('id', $attribute_ids)->get();
            }
        }

        //price range
        if ($min_price != null) {
            $products->where('lowest_price', '>=', $min_price);
        }
        if ($max_price != null) {
            $products->where('highest_price', '<=', $max_price);
        }

        //filter by attribute value
        if ($selected_attribute_values) {
            $products->with('attribute_values')->whereHas('attribute_values', function ($query) use ($selected_attribute_values) {
                return $query->whereIn('attribute_value_id', $selected_attribute_values);
            });
        }


        //sorting
        switch ($sort_by) {
            case 'latest':
                $products->orderBy('created_at', 'desc');
                break;
            case 'oldest':
                $products->orderBy('created_at', 'asc');
                break;
            case 'highest_price':
                $products->orderBy('highest_price', 'desc');
                break;
            case 'lowest_price':
                $products->orderBy('lowest_price', 'asc');
                break;
            default:
                $products->orderBy('num_of_sale', 'desc');
                break;
        }

        $paginator = $products->paginate(20);
        Log::info('Frontend product search query snapshot', [
            'keyword' => $search_keyword,
            'category_slug' => $request->category_slug,
            'brand_ids' => $brand_ids,
            'sort_by' => $sort_by,
            'min_price' => $min_price,
            'max_price' => $max_price,
            'total_results' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'latest_product_visibility' => $this->latestProductVisibilitySnapshot(),
        ]);
        $collection = new ProductCollection($paginator);

        return response()->json([
            'success' => true,
            'categoryMetaTitle' => $category ? $category->meta_title : get_setting('meta_title'),
            'categoryMetaDescription' => $category ? $category->meta_description : get_setting('meta_description'),
            'brandMetaTitle' => $brand ?? $brand?->meta_title,
            'brandMetaDescription' => $brand ?? $brand?->meta_description,
            'products' => $collection,
            'totalPage' => $collection->lastPage(),
            'currentPage' => $collection->currentPage(),
            'total' => $collection->total(),
            'parentCategory' => $category && $category->parent_id != 0 ? new CategorySingleCollection(Category::find($category->parent_id)) : null,
            'currentCategory' => $category ? new CategorySingleCollection($category) : null,
            'childCategories' => $category ? new CategoryCollection($category->childrenCategories) : null,
            'rootCategories' => new CategoryCollection(Category::where('level', 0)->orderBy('order_level', 'desc')->get()),
            'allBrands' => new BrandCollection(Brand::all()),
            'attributes' => new AttributeCollection($attributes)
        ]);
    }

    public function todays_deal(Request $request)
    {
        $selectedCategoryIds = $this->parseIdList($request->category_ids);
        $selectedAttributeValueIds = $this->parseIdList($request->attribute_values);

        $products = Product::with(['variations'])
            ->todayDeal()
            ->frontendVisible();

        $this->applyTodayDealFilters(
            $products,
            $selectedCategoryIds,
            $selectedAttributeValueIds,
            $request->min_price,
            $request->max_price
        );
        $this->applyProductSorting($products, $request->sort_by);

        $collection = new ProductCollection($products->paginate(16));
        $filters = $this->buildTodayDealFilters();

        return response()->json([
            'success' => true,
            'title' => "Today's Deal",
            'description' => 'Explore our best selling catalogue, Join hundreds of people with exquisite taste.',
            'products' => $collection,
            'totalPage' => $collection->lastPage(),
            'currentPage' => $collection->currentPage(),
            'total' => $collection->total(),
            'filters' => $filters,
        ]);
    }

    public function ajax_search($search_keyword)
    {

        $keywords = array();
        $products = filter_products(Product::query())
            ->where('tags', 'like', '%' . $search_keyword . '%')
            ->get();
        foreach ($products as $key => $product) {
            foreach (explode(',', $product->tags) as $key => $tag) {
                if (stripos($tag, $search_keyword) !== false) {
                    if (sizeof($keywords) > 5) {
                        break;
                    } else {
                        if (!in_array(strtolower($tag), $keywords)) {
                            array_push($keywords, strtolower($tag));
                        }
                    }
                }
            }
        }

        $products_query = filter_products(Product::query())
            ->where(function ($q) use ($search_keyword) {
                foreach (explode(' ', trim($search_keyword)) as $word) {
                    $q->where('name', 'like', '%' . $word . '%')
                        ->orWhere('tags', 'like', '%' . $word . '%')
                        ->orWhereHas('product_translations', function ($q) use ($word) {
                            $q->where('name', 'like', '%' . $word . '%');
                        })
                        ->orWhereHas('variations', function ($q) use ($word) {
                            $q->where('sku', 'like', '%' . $word . '%');
                        });
                }
            });


        $case1 = $search_keyword . '%';
        $case2 = '%' . $search_keyword . '%';

        $products_query->orderByRaw("CASE 
                WHEN name LIKE '$case1' THEN 1 
                WHEN name LIKE '$case2' THEN 2 
                ELSE 3 
                END");

        $products = new ProductCollection($products_query->limit(3)->get());

        $categories = Category::where('level', 0)->where('name', 'like', '%' . $search_keyword . '%')->get()->take(3);
        $brands = Brand::where('name', 'like', '%' . $search_keyword . '%')->get()->take(3);
        $shops = new ShopCollection(filter_shops(Shop::where('name', 'like', '%' . $search_keyword . '%')->get()->take(3)));

        if (sizeof($keywords) > 0 || sizeof($categories) > 0 || sizeof($products) > 0 || sizeof($shops) > 0 || sizeof($brands) > 0) {
            return response()->json([
                'success' => true,
                'keywords' => $keywords,
                'categories' => $categories,
                'brands' => $brands,
                'products' => $products,
                'shops' => $shops,
            ]);
        } else {
            return response()->json([
                'success' => false
            ]);
        }
    }

    public function productComparedList(Request $request)
    {
        $products = Product::whereIn('id', $request->data)->get();
        // return new ProductCollection($products);
        $products_array = array();
        foreach ($products as $product) {
            $products_array['name'][] = $product->name;
            $products_array['image'][] = api_asset($product->thumbnail_img);
            if ($product->lowest_price != $product->highest_price) {
                $products_array['price'][] = format_price($product->lowest_price) . "-" . format_price($product->highest_price);
            } else {
                $products_array['price'][] = format_price($product->lowest_price);
            }
            $products_array['brand'][] = $product->brand->name ?? "none";
            $products_array['shop'][] = $product->shop->name ?? "none";
            $products_array['slug'][] = $product->slug;
            $products_array['id'][] = $product->id;
        }
        return response()->json([
            'success' => true,
            'specifications' => $products_array,
        ]);
    }

    private function parseIdList($value): array
    {
        if (is_array($value)) {
            return collect($value)
                ->map(fn ($item) => (int) $item)
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        return collect(explode(',', $value))
            ->map(fn ($item) => (int) trim($item))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function applyTodayDealFilters($products, array $selectedCategoryIds, array $selectedAttributeValueIds, $minPrice, $maxPrice): void
    {
        if (!empty($selectedCategoryIds)) {
            $products->whereHas('product_categories', function ($query) use ($selectedCategoryIds) {
                $query->whereIn('category_id', $selectedCategoryIds);
            });
        }

        if ($minPrice !== null && $minPrice !== '') {
            $products->where('lowest_price', '>=', (float) $minPrice);
        }

        if ($maxPrice !== null && $maxPrice !== '') {
            $products->where('highest_price', '<=', (float) $maxPrice);
        }

        if (!empty($selectedAttributeValueIds)) {
            $attributeGroups = AttributeValue::query()
                ->whereIn('id', $selectedAttributeValueIds)
                ->get(['id', 'attribute_id'])
                ->groupBy('attribute_id');

            foreach ($attributeGroups as $attributeId => $attributeValues) {
                $valueIds = $attributeValues->pluck('id')->map(fn ($id) => (int) $id)->values()->all();

                $products->where(function ($attributeQuery) use ($attributeId, $valueIds) {
                    $attributeQuery
                        ->whereHas('attribute_values', function ($query) use ($attributeId, $valueIds) {
                            $query->where('attribute_id', $attributeId)->whereIn('attribute_value_id', $valueIds);
                        })
                        ->orWhereHas('variation_combinations', function ($query) use ($attributeId, $valueIds) {
                            $query->where('attribute_id', $attributeId)->whereIn('attribute_value_id', $valueIds);
                        });
                });
            }
        }
    }

    private function applyProductSorting($products, $sortBy): void
    {
        switch ($sortBy) {
            case 'latest':
                $products->orderBy('created_at', 'desc');
                break;
            case 'oldest':
                $products->orderBy('created_at', 'asc');
                break;
            case 'highest_price':
                $products->orderBy('highest_price', 'desc');
                break;
            case 'lowest_price':
                $products->orderBy('lowest_price', 'asc');
                break;
            default:
                $products->orderBy('num_of_sale', 'desc');
                break;
        }
    }

    private function buildTodayDealFilters(): array
    {
        $baseQuery = Product::query()->todayDeal()->frontendVisible();
        $productIds = $baseQuery->pluck('id');

        if ($productIds->isEmpty()) {
            return [
                'categories' => [],
                'colors' => [],
                'sizes' => [],
                'materials' => [],
                'attributes' => [],
                'priceRange' => [
                    'min' => 0,
                    'max' => 0,
                ],
            ];
        }

        $categories = Category::query()
            ->select('categories.*')
            ->join('product_categories', 'product_categories.category_id', '=', 'categories.id')
            ->whereIn('product_categories.product_id', $productIds)
            ->distinct()
            ->orderBy('categories.name')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => (int) $category->id,
                    'name' => $category->getTranslation('name'),
                    'slug' => $category->slug,
                ];
            })
            ->values()
            ->all();

        $attributeFilters = $this->collectTodayDealAttributeFilters($productIds);
        $groupedFilters = $this->groupTodayDealAttributeFilters($attributeFilters);

        $priceRange = Product::query()
            ->todayDeal()
            ->frontendVisible()
            ->selectRaw('MIN(lowest_price) AS min_price, MAX(highest_price) AS max_price')
            ->first();

        return [
            'categories' => $categories,
            'colors' => $groupedFilters['colors'],
            'sizes' => $groupedFilters['sizes'],
            'materials' => $groupedFilters['materials'],
            'attributes' => $groupedFilters['attributes'],
            'priceRange' => [
                'min' => (float) ($priceRange->min_price ?? 0),
                'max' => (float) ($priceRange->max_price ?? 0),
            ],
        ];
    }

    private function collectTodayDealAttributeFilters(Collection $productIds): Collection
    {
        $directAttributeValues = ProductAttributeValue::query()
            ->with(['attribute', 'value'])
            ->whereIn('product_id', $productIds)
            ->get()
            ->map(function ($item) {
                if (!$item->attribute || !$item->value) {
                    return null;
                }

                return [
                    'attribute_id' => (int) $item->attribute_id,
                    'attribute_name' => $item->attribute->getTranslation('name'),
                    'value_id' => (int) $item->attribute_value_id,
                    'value_name' => $item->value->getTranslation('name'),
                ];
            })
            ->filter();

        $variationAttributeValues = ProductVariationCombination::query()
            ->with(['attribute', 'attribute_value'])
            ->whereIn('product_id', $productIds)
            ->get()
            ->map(function ($item) {
                if (!$item->attribute || !$item->attribute_value) {
                    return null;
                }

                return [
                    'attribute_id' => (int) $item->attribute_id,
                    'attribute_name' => $item->attribute->getTranslation('name'),
                    'value_id' => (int) $item->attribute_value_id,
                    'value_name' => $item->attribute_value->getTranslation('name'),
                ];
            })
            ->filter();

        return collect($directAttributeValues->all())
            ->merge($variationAttributeValues->all())
            ->unique(fn ($item) => $item['attribute_id'] . ':' . $item['value_id'])
            ->values();
    }

    private function groupTodayDealAttributeFilters(Collection $attributeFilters): array
    {
        $grouped = [
            'colors' => [],
            'sizes' => [],
            'materials' => [],
            'attributes' => [],
        ];

        $attributeFilters
            ->groupBy('attribute_id')
            ->each(function (Collection $items) use (&$grouped) {
                $first = $items->first();
                $attributeName = $first['attribute_name'] ?? '';
                $normalizedName = str($attributeName)->lower()->replace(['_', '-'], ' ')->squish()->value();
                $values = $items
                    ->sortBy('value_name')
                    ->values()
                    ->map(function ($item) {
                        return [
                            'id' => (int) $item['value_id'],
                            'name' => $item['value_name'],
                        ];
                    })
                    ->all();

                if (in_array($normalizedName, ['color', 'colors'], true)) {
                    $grouped['colors'] = $values;
                    return;
                }

                if (str_contains($normalizedName, 'size')) {
                    $grouped['sizes'] = $values;
                    return;
                }

                if (str_contains($normalizedName, 'material') || str_contains($normalizedName, 'fabric')) {
                    $grouped['materials'] = $values;
                    return;
                }

                $grouped['attributes'][] = [
                    'id' => (int) $first['attribute_id'],
                    'name' => $attributeName,
                    'values' => $values,
                ];
            });

        return $grouped;
    }

    private function latestProductVisibilitySnapshot(): array
    {
        $latestProduct = Product::query()
            ->latest('id')
            ->first(['id', 'shop_id', 'published', 'approved', 'digital', 'slug', 'deleted_at']);

        if (!$latestProduct) {
            return ['latest_product' => null];
        }

        $publishedShopIds = collect(published_shops_ids())
            ->filter()
            ->map(function ($id) {
                return (int) $id;
            })
            ->values()
            ->all();
        $latestProductShopId = is_null($latestProduct->shop_id) ? null : (int) $latestProduct->shop_id;

        return [
            'id' => (int) $latestProduct->id,
            'slug' => $latestProduct->slug,
            'shop_id' => $latestProductShopId,
            'published' => (int) $latestProduct->published,
            'approved' => (int) $latestProduct->approved,
            'digital' => (int) $latestProduct->digital,
            'soft_deleted' => !is_null($latestProduct->deleted_at),
            'visible_on_frontend' => (int) $latestProduct->published === 1,
            'has_default_translation' => $latestProduct->product_translations()->where('lang', env('DEFAULT_LANGUAGE'))->exists(),
            'has_categories' => $latestProduct->product_categories()->exists(),
            'published_shop_ids' => $publishedShopIds,
        ];
    }
}
