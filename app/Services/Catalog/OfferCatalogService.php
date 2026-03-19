<?php

namespace App\Services\Catalog;

use App\Models\Offer;
use Illuminate\Support\Collection;

class OfferCatalogService
{
    public function allActive(): Collection
    {
        return Offer::query()
            ->where('status', 1)
            ->where('start_date', '<=', now()->timestamp)
            ->where('end_date', '>=', now()->timestamp)
            ->orderByDesc('end_date')
            ->get();
    }

    public function findActiveBySlug(string $slug): ?Offer
    {
        return Offer::query()
            ->where('status', 1)
            ->where('start_date', '<=', now()->timestamp)
            ->where('end_date', '>=', now()->timestamp)
            ->where('slug', $slug)
            ->with(['products.brand', 'products.variations', 'products.product_translations', 'products.taxes'])
            ->first();
    }
}
