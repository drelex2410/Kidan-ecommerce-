<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Catalog\OfferCollection;
use App\Http\Resources\Api\V1\Catalog\OfferDetailResource;
use App\Services\Catalog\OfferCatalogService;
use Illuminate\Http\JsonResponse;

class OfferController extends Controller
{
    public function index(OfferCatalogService $offerCatalogService): JsonResponse
    {
        $resource = new OfferCollection($offerCatalogService->allActive());

        return response()->json([
            'success' => true,
            ...$resource->toArray(request()),
            'status' => 200,
        ]);
    }

    public function show(string $slug, OfferCatalogService $offerCatalogService): JsonResponse
    {
        $offer = $offerCatalogService->findActiveBySlug($slug);

        if (!$offer) {
            return response()->json([
                'success' => false,
                'message' => 'Offer not found!',
                'status' => 404,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => (new OfferDetailResource($offer))->toArray(request()),
            'status' => 200,
        ]);
    }
}
