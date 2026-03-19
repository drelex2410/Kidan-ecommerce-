<?php

namespace App\Services\Catalog;

use App\Models\Category;
use App\Utility\CategoryUtility;
use Illuminate\Support\Collection;

class CategoryTreeService
{
    public function allCategories(): Collection
    {
        return Category::query()
            ->where('level', 0)
            ->orderByDesc('order_level')
            ->get()
            ->map(function (Category $category) {
                $children = Category::query()
                    ->whereIn('id', CategoryUtility::children_ids($category->id))
                    ->get();

                $category->setRelation('all_children', $children);

                return $category;
            });
    }

    public function firstLevelCategories(): Collection
    {
        return Category::query()
            ->where('level', 0)
            ->orderByDesc('order_level')
            ->get();
    }
}
