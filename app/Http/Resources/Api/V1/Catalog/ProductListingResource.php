<?php

namespace App\Http\Resources\Api\V1\Catalog;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductListingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $paginator = $this->resource['paginator'];

        return [
            'success' => true,
            'metaTitle' => $this->resource['meta_title'],
            'metaDescription' => $this->resource['meta_description'],
            'categoryMetaTitle' => $this->resource['meta_title'],
            'categoryMetaDescription' => $this->resource['meta_description'],
            'products' => new ProductCardCollection($paginator->getCollection()),
            'totalPage' => $paginator->lastPage(),
            'currentPage' => $paginator->currentPage(),
            'total' => $paginator->total(),
            'parentCategory' => $this->resource['parent_category'] ? new CategoryResource($this->resource['parent_category']) : null,
            'parentCategories' => $this->resource['parent_category'] ? [new CategoryResource($this->resource['parent_category'])] : [],
            'currentCategory' => $this->resource['current_category'] ? new CategoryResource($this->resource['current_category']) : null,
            'childCategories' => new CategoryCollection($this->resource['child_categories']),
            'rootCategories' => new CategoryCollection($this->resource['root_categories']),
            'allBrands' => new BrandCollection($this->resource['brands']),
            'attributes' => new FilterAttributeCollection($this->resource['attributes']),
            'seo' => $this->resource['seo'],
        ];
    }
}
