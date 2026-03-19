<?php

namespace App\Http\Resources\Api\V1\Catalog;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductDetailResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => (int) $this->id,
            'name' => $this->getTranslation('name'),
            'slug' => $this->slug,
            'metaTitle' => $this->meta_title,
            'brand' => [
                'id' => optional($this->brand)->id,
                'name' => optional($this->brand)?->getTranslation('name'),
                'slug' => optional($this->brand)->slug,
                'logo' => optional($this->brand)->logo ? api_asset(optional($this->brand)->logo) : null,
            ],
            'photos' => collect(explode(',', (string) $this->photos))
                ->filter()
                ->map(fn ($photo) => api_asset(trim($photo)))
                ->values()
                ->all(),
            'thumbnail_image' => $this->thumbnail_img ? api_asset($this->thumbnail_img) : null,
            'tags' => collect(explode(',', (string) $this->tags))->filter()->values()->all(),
            'featured' => (int) $this->featured,
            'stock' => (int) $this->stock,
            'current_stock' => (int) $this->stock,
            'min_qty' => (int) $this->min_qty,
            'max_qty' => (int) $this->max_qty,
            'unit' => $this->getTranslation('unit'),
            'discount' => $this->discount,
            'discount_type' => $this->discount_type,
            'base_price' => (double) product_base_price($this->resource),
            'highest_price' => (double) product_highest_price($this->resource),
            'base_discounted_price' => (double) product_discounted_base_price($this->resource),
            'highest_discounted_price' => (double) product_discounted_highest_price($this->resource),
            'standard_delivery_time' => (int) $this->standard_delivery_time,
            'express_delivery_time' => (int) $this->express_delivery_time,
            'is_variant' => (int) $this->is_variant,
            'has_warranty' => (int) $this->has_warranty,
            'for_pickup' => (int) $this->for_pickup,
            'review_summary' => [
                'average' => (double) $this->rating,
                'total_count' => (int) $this->reviews_count,
                'count_5' => (int) $this->reviews_5_count,
                'count_4' => (int) $this->reviews_4_count,
                'count_3' => (int) $this->reviews_3_count,
                'count_2' => (int) $this->reviews_2_count,
                'count_1' => (int) $this->reviews_1_count,
            ],
            'description' => $this->getTranslation('description'),
            'variations' => $this->variationPayload(),
            'variation_options' => $this->variationOptionsPayload(),
            'shop' => [
                'name' => optional($this->shop)->name,
                'logo' => optional($this->shop)->logo ? api_asset(optional($this->shop)->logo) : null,
                'rating' => (double) (optional($this->shop)->rating ?? 0),
                'review_count' => optional($this->shop)->reviews_count,
                'slug' => optional($this->shop)->slug,
                'isVarified' => (bool) (optional($this->shop)->verification_status == 1),
            ],
            'earn_point' => (float) $this->earn_point,
            'is_digital' => (bool) ($this->digital == 1),
        ];
    }

    private function variationPayload(): array
    {
        return $this->variations->map(function ($variation) {
            return [
                'id' => (int) $variation->id,
                'code' => $variation->code === null ? null : array_values(array_filter(explode('/', (string) $variation->code))),
                'img' => $variation->img,
                'image' => $variation->img ? api_asset($variation->img) : null,
                'price' => variation_discounted_price($this->resource, $variation),
                'stock' => (int) $variation->stock,
                'current_stock' => (int) ($variation->current_stock ?? $variation->stock),
            ];
        })->values()->all();
    }

    private function variationOptionsPayload(): array
    {
        return $this->variation_combinations
            ->groupBy('attribute_id')
            ->map(function ($group) {
                $first = $group->first();

                return [
                    'id' => (int) $first->attribute_id,
                    'name' => optional($first->attribute)->getTranslation('name'),
                    'values' => $group
                        ->unique('attribute_value_id')
                        ->map(function ($combination) {
                            return [
                                'id' => (int) $combination->attribute_value_id,
                                'name' => optional($combination->attribute_value)->getTranslation('name'),
                            ];
                        })
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();
    }
}
