<?php

namespace App\Services\Catalog;

use App\Models\Attribute;
use App\Models\AttributeCategory;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Utility\CategoryUtility;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class CatalogSearchService
{
    public function search(array $filters): array
    {
        $category = !empty($filters['category_slug'])
            ? Category::query()->where('slug', $filters['category_slug'])->first()
            : null;

        $brandIds = $filters['brand_ids'] ?? [];
        $attributeValueIds = $filters['attribute_values'] ?? [];
        $searchKeyword = $filters['keyword'] ?? null;

        $query = Product::query()
            ->frontendVisible()
            ->with([
                'brand',
                'variations',
                'categories',
                'attribute_values.value.attribute',
            ]);

        if ($brandIds !== []) {
            $query->whereIn('brand_id', $brandIds);
        }

        if ($searchKeyword) {
            $this->applyKeywordFilter($query, $searchKeyword);
        }

        $categoryIds = [];
        if ($category) {
            $categoryIds = array_values(array_unique(array_merge(
                [$category->id],
                CategoryUtility::children_ids($category->id)
            )));

            $query->whereHas('product_categories', function (Builder $builder) use ($categoryIds): void {
                $builder->whereIn('category_id', $categoryIds);
            });
        }

        if (!empty($filters['min_price'])) {
            $query->where('lowest_price', '>=', $filters['min_price']);
        }

        if (!empty($filters['max_price'])) {
            $query->where('highest_price', '<=', $filters['max_price']);
        }

        if ($attributeValueIds !== []) {
            $query->whereHas('attribute_values', function (Builder $builder) use ($attributeValueIds): void {
                $builder->whereIn('attribute_value_id', $attributeValueIds);
            });
        }

        $this->applySorting($query, $filters['sort_by'] ?? 'popular');

        $paginator = $query->paginate(20)->withQueryString();

        $attributeIds = $category
            ? AttributeCategory::query()->whereIn('category_id', $categoryIds)->pluck('attribute_id')->all()
            : $this->resolveAttributeIdsFromPaginator($paginator);

        $attributes = Attribute::query()
            ->with('attribute_values')
            ->when($attributeIds !== [], fn (Builder $builder) => $builder->whereIn('id', $attributeIds))
            ->get();

        $currentCategory = $category;
        $parentCategory = $category && (int) $category->parent_id !== 0
            ? Category::query()->find($category->parent_id)
            : null;
        $childCategories = $category
            ? $category->childrenCategories()->orderByDesc('order_level')->get()
            : collect();
        $rootCategories = Category::query()->where('level', 0)->orderByDesc('order_level')->get();
        $brands = Brand::query()->get();
        $activeBrand = $brandIds !== [] ? Brand::query()->find($brandIds[0]) : null;

        return [
            'paginator' => $paginator,
            'attributes' => $attributes,
            'brands' => $brands,
            'root_categories' => $rootCategories,
            'parent_category' => $parentCategory,
            'current_category' => $currentCategory,
            'child_categories' => $childCategories,
            'meta_title' => $currentCategory?->meta_title ?: $activeBrand?->meta_title ?: get_setting('meta_title'),
            'meta_description' => $currentCategory?->meta_description ?: $activeBrand?->meta_description ?: get_setting('meta_description'),
            'seo' => [
                'title' => $currentCategory?->meta_title ?: $activeBrand?->meta_title ?: get_setting('meta_title'),
                'description' => $currentCategory?->meta_description ?: $activeBrand?->meta_description ?: get_setting('meta_description'),
            ],
        ];
    }

    private function applyKeywordFilter(Builder $query, string $searchKeyword): void
    {
        $query->where(function (Builder $builder) use ($searchKeyword): void {
            foreach (preg_split('/\s+/', trim($searchKeyword)) ?: [] as $word) {
                $builder->orWhere('name', 'like', '%' . $word . '%')
                    ->orWhere('tags', 'like', '%' . $word . '%')
                    ->orWhereHas('product_translations', function (Builder $translationQuery) use ($word): void {
                        $translationQuery->where('name', 'like', '%' . $word . '%');
                    })
                    ->orWhereHas('variations', function (Builder $variationQuery) use ($word): void {
                        $variationQuery->where('sku', 'like', '%' . $word . '%');
                    });
            }
        });
    }

    private function applySorting(Builder $query, string $sortBy): void
    {
        match ($sortBy) {
            'latest' => $query->orderByDesc('created_at'),
            'oldest' => $query->orderBy('created_at'),
            'highest_price' => $query->orderByDesc('highest_price'),
            'lowest_price' => $query->orderBy('lowest_price'),
            default => $query->orderByDesc('num_of_sale'),
        };
    }

    private function resolveAttributeIdsFromPaginator(LengthAwarePaginator $paginator): array
    {
        return $paginator->getCollection()
            ->flatMap(static fn (Product $product) => $product->attribute_values->pluck('attribute_id'))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
