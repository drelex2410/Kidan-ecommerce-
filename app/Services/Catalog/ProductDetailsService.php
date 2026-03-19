<?php

namespace App\Services\Catalog;

use App\Models\OrderDetail;
use App\Models\Product;
use Illuminate\Support\Collection;

class ProductDetailsService
{
    public function findBySlug(string $slug): ?Product
    {
        return Product::query()
            ->frontendVisible()
            ->where('slug', $slug)
            ->with([
                'brand',
                'categories',
                'variations.combinations.attribute',
                'variations.combinations.attribute_value',
                'variation_combinations.attribute',
                'variation_combinations.attribute_value',
                'shop',
            ])
            ->withCount(['reviews', 'reviews_1', 'reviews_2', 'reviews_3', 'reviews_4', 'reviews_5'])
            ->first();
    }

    public function related(int $productId, int $limit = 10): Collection
    {
        $product = Product::query()->with('product_categories')->find($productId);
        if (!$product) {
            return collect();
        }

        $categoryIds = $product->product_categories->pluck('category_id')->all();

        return Product::query()
            ->frontendVisible()
            ->with(['brand', 'variations'])
            ->where('id', '!=', $productId)
            ->whereHas('product_categories', fn ($query) => $query->whereIn('category_id', $categoryIds))
            ->limit($limit)
            ->get();
    }

    public function boughtTogether(int $productId, int $limit = 10): Collection
    {
        $orderIds = OrderDetail::query()->where('product_id', $productId)->pluck('order_id')->all();
        if ($orderIds === []) {
            return collect();
        }

        $productIds = OrderDetail::query()
            ->whereIn('order_id', $orderIds)
            ->where('product_id', '!=', $productId)
            ->distinct()
            ->pluck('product_id')
            ->all();

        if ($productIds === []) {
            return collect();
        }

        return Product::query()
            ->frontendVisible()
            ->with(['brand', 'variations'])
            ->whereIn('id', $productIds)
            ->limit($limit)
            ->get();
    }

    public function random(int $limit, ?int $excludeProductId = null): Collection
    {
        return Product::query()
            ->frontendVisible()
            ->with(['brand', 'variations'])
            ->when($excludeProductId, fn ($query) => $query->where('id', '!=', $excludeProductId))
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    public function latest(int $limit): Collection
    {
        return Product::query()
            ->frontendVisible()
            ->with(['brand', 'variations'])
            ->latest()
            ->limit($limit)
            ->get();
    }
}
