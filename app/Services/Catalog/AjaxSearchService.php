<?php

namespace App\Services\Catalog;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;

class AjaxSearchService
{
    public function search(string $keyword): array
    {
        $normalizedKeyword = trim($keyword);

        $keywords = $this->resolveKeywords($normalizedKeyword);
        $products = Product::query()
            ->frontendVisible()
            ->with(['brand', 'variations'])
            ->where(function ($query) use ($normalizedKeyword) {
                foreach (preg_split('/\s+/', $normalizedKeyword) ?: [] as $word) {
                    $query->orWhere('name', 'like', '%' . $word . '%')
                        ->orWhere('tags', 'like', '%' . $word . '%')
                        ->orWhereHas('product_translations', function ($translationQuery) use ($word) {
                            $translationQuery->where('name', 'like', '%' . $word . '%');
                        })
                        ->orWhereHas('variations', function ($variationQuery) use ($word) {
                            $variationQuery->where('sku', 'like', '%' . $word . '%');
                        });
                }
            })
            ->limit(3)
            ->get();

        $categories = Category::query()
            ->where(function ($query) use ($normalizedKeyword) {
                $query->where('name', 'like', '%' . $normalizedKeyword . '%')
                    ->orWhereHas('category_translations', function ($translationQuery) use ($normalizedKeyword) {
                        $translationQuery->where('name', 'like', '%' . $normalizedKeyword . '%');
                    });
            })
            ->limit(3)
            ->get();

        $brands = Brand::query()
            ->where(function ($query) use ($normalizedKeyword) {
                $query->where('name', 'like', '%' . $normalizedKeyword . '%')
                    ->orWhereHas('brand_translations', function ($translationQuery) use ($normalizedKeyword) {
                        $translationQuery->where('name', 'like', '%' . $normalizedKeyword . '%');
                    });
            })
            ->limit(3)
            ->get();

        if ($brands->isEmpty() && $products->isNotEmpty()) {
            $brands = Brand::query()
                ->whereIn('id', $products->pluck('brand_id')->filter()->unique()->values()->all())
                ->limit(3)
                ->get();
        }

        $shops = Shop::query()
            ->when(\Schema::hasColumn('shops', 'published'), fn ($query) => $query->where('published', 1))
            ->when(\Schema::hasColumn('shops', 'approval'), fn ($query) => $query->where('approval', 1))
            ->when(\Schema::hasColumn('shops', 'verification_status'), fn ($query) => $query->where('verification_status', 1))
            ->where('name', 'like', '%' . $normalizedKeyword . '%')
            ->limit(3)
            ->get();

        return [
            'success' => !empty($keywords) || $categories->isNotEmpty() || $brands->isNotEmpty() || $products->isNotEmpty() || $shops->isNotEmpty(),
            'keywords' => $keywords,
            'categories' => $categories,
            'brands' => $brands,
            'products' => $products,
            'shops' => $shops,
        ];
    }

    private function resolveKeywords(string $keyword): array
    {
        $products = Product::query()
            ->frontendVisible()
            ->where('tags', 'like', '%' . $keyword . '%')
            ->get();

        $keywords = [];
        foreach ($products as $product) {
            foreach (explode(',', (string) $product->tags) as $tag) {
                $tag = strtolower(trim($tag));
                if ($tag !== '' && str_contains($tag, strtolower($keyword)) && !in_array($tag, $keywords, true)) {
                    $keywords[] = $tag;
                }
                if (count($keywords) >= 6) {
                    break 2;
                }
            }
        }

        return $keywords;
    }
}
