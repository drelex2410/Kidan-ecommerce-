<?php

namespace App\Services\Catalog;

use App\Models\Category;
use Illuminate\Support\Collection;

class CategoryTreeService
{
    public function allCategories(): Collection
    {
        $categories = Category::query()->get();

        return $this->buildTree($categories);
    }

    public function firstLevelCategories(): Collection
    {
        return $this->sortCategories(
            Category::query()
                ->where('level', 0)
                ->get()
        );
    }

    private function buildTree(Collection $categories): Collection
    {
        $groupedByParent = $categories->groupBy(function (Category $category) {
            return (string) ((int) ($category->parent_id ?? 0));
        });

        return $this->buildBranch($groupedByParent, 0);
    }

    private function buildBranch(Collection $groupedByParent, int $parentId): Collection
    {
        $branch = $this->sortCategories(
            collect($groupedByParent->get((string) $parentId, collect()))
        );

        return $branch
            ->map(function (Category $category) use ($groupedByParent) {
                $children = $this->buildBranch($groupedByParent, (int) $category->id);

                $category->setRelation('categories', $children);

                return $category;
            })
            ->values();
    }

    private function sortCategories(Collection $categories): Collection
    {
        return $categories
            ->sort(function (Category $first, Category $second) {
                $firstOrder = $first->order_level;
                $secondOrder = $second->order_level;

                if (is_null($firstOrder) xor is_null($secondOrder)) {
                    return is_null($firstOrder) ? 1 : -1;
                }

                if (! is_null($firstOrder) && ! is_null($secondOrder) && (int) $firstOrder !== (int) $secondOrder) {
                    return (int) $firstOrder <=> (int) $secondOrder;
                }

                $nameComparison = strcasecmp(
                    (string) $first->getTranslation('name'),
                    (string) $second->getTranslation('name')
                );

                if ($nameComparison !== 0) {
                    return $nameComparison;
                }

                return (int) $first->id <=> (int) $second->id;
            })
            ->values();
    }
}
