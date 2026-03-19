<?php

namespace App\Services\Catalog;

use App\Models\Brand;
use Illuminate\Support\Collection;

class BrandCatalogService
{
    public function all(): Collection
    {
        return Brand::query()->orderBy('name')->get();
    }
}
