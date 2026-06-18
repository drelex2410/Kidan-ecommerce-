<?php

namespace App\Services\Content;

use App\Http\Resources\CategoryCollection;
use App\Http\Resources\ProductCollection;
use App\Http\Resources\ShopCollection;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class HomeSectionService
{
    public function __construct(
        private readonly ContentMedia $contentMedia
    ) {
    }

    public function get(string $section): mixed
    {
        return match ($section) {
            'sliders' => $this->sliders(),
            'popular_categories' => $this->popularCategories(),
            'product_section_one' => $this->productSectionOne(),
            'product_section_two' => $this->productSectionTwo(),
            'product_section_three' => $this->productSectionThree(),
            'product_section_four' => $this->productSectionFour(),
            'product_section_five' => $this->productSectionFive(),
            'product_section_six' => $this->productSectionSix(),
            'banner_section_one' => $this->bannerSection('home_banner_1_images', 'home_banner_1_links', 'banner_section_one'),
            'banner_section_two' => $this->fixedBannerSection('home_banner_2_images', 'home_banner_2_links', [
                ['slot' => 'background', 'default_link' => null, 'clickable' => false],
                ['slot' => 'product', 'default_link' => '/', 'clickable' => true],
            ], 'banner_section_two'),
            'banner_section_three' => $this->bannerSectionThree(),
            'banner_section_four' => $this->bannerSectionFour(),
            'home_about_text' => $this->homeAboutText(),
            'shop_section_one' => $this->shopSection(1),
            'shop_section_two' => $this->shopSection(2),
            'shop_section_three' => $this->shopSection(3),
            'shop_section_four' => $this->shopSection(4),
            'shop_section_five' => $this->shopSection(5),
            'shop_banner_section_one' => $this->bannerSection('home_shop_banner_1_images', 'home_shop_banner_1_links', 'shop_banner_section_one'),
            'shop_banner_section_two' => $this->bannerSection('home_shop_banner_2_images', 'home_shop_banner_2_links', 'shop_banner_section_two'),
            'shop_banner_section_three' => $this->bannerSection('home_shop_banner_3_images', 'home_shop_banner_3_links', 'shop_banner_section_three'),
            default => throw new NotFoundHttpException('Home section not found.'),
        };
    }

    private function bannerSectionFour(): array
    {
        $slots = $this->bannerSectionFourSlots('home_banner_4_images');

        $current = $this->fixedBannerSection(
            'home_banner_4_images',
            'home_banner_4_links',
            $slots,
            'banner_section_four'
        );

        if ($this->payloadHasImage($current)) {
            return $current;
        }

        return $this->fixedBannerSection(
            'home_banner_3_images',
            'home_banner_3_links',
            $this->bannerSectionFourSlots('home_banner_3_images'),
            'banner_section_four',
            'home_banner_3_compat'
        );
    }

    private function bannerSectionFourSlots(string $imagesSettingKey): array
    {
        $imageIds = $this->decodeSettingArray(get_setting($imagesSettingKey));
        $slotCount = max(count($imageIds), 4);
        $slots = [];

        for ($index = 0; $index < $slotCount; $index++) {
            if ($index === 3) {
                $slots[] = ['slot' => 'newsletter', 'default_link' => null, 'clickable' => false];
                continue;
            }

            $slidesBeforeNewsletter = $index < 3 ? $index + 1 : $index;
            $slots[] = ['slot' => 'slide_' . $slidesBeforeNewsletter, 'default_link' => null, 'clickable' => true];
        }

        return $slots;
    }

    private function bannerSectionThree(): array
    {
        $currentBannerThree = $this->bannerSection(
            'home_banner_4_images',
            'home_banner_4_links',
            'banner_section_three',
            'home_banner_4_alias',
            3
        );

        if ($currentBannerThree !== []) {
            return $currentBannerThree;
        }

        return $this->bannerSection(
            'home_banner_3_images',
            'home_banner_3_links',
            'banner_section_three',
            'home_banner_3_legacy'
        );
    }

    private function sliders(): array
    {
        return Cache::remember('v1.home.sliders', 86400, function (): array {
            return [
                'one' => $this->bannerSection('home_slider_1_images', 'home_slider_1_links', 'sliders.one'),
                'two' => $this->bannerSection('home_slider_2_images', 'home_slider_2_links', 'sliders.two'),
                'three' => $this->bannerSection('home_slider_3_images', 'home_slider_3_links', 'sliders.three'),
                'four' => $this->bannerSection('home_slider_4_images', 'home_slider_4_links', 'sliders.four'),
            ];
        });
    }

    private function popularCategories(): CategoryCollection
    {
        return Cache::remember('v1.home.popular_categories', 86400, function () {
            return new CategoryCollection(
                Category::query()
                    ->where('featured', 1)
                    ->orderBy('order_level', 'desc')
                    ->orderBy('id', 'desc')
                    ->get()
            );
        });
    }

    private function productSectionOne(): array
    {
        return Cache::remember('v1.home.product_section_one', 86400, function (): array {
            return [
                'title' => "Today's Deal",
                'products' => new ProductCollection(
                    Product::query()
                        ->with('variations')
                        ->todayDeal()
                        ->frontendVisible()
                        ->orderByDesc('updated_at')
                        ->get()
                ),
            ];
        });
    }

    private function productSectionTwo(): array
    {
        return Cache::remember('v1.home.product_section_two', 86400, function (): array {
            return [
                'title' => get_setting('home_product_section_2_title'),
                'products' => new ProductCollection($this->manualProducts('home_product_section_2_products')),
            ];
        });
    }

    private function productSectionThree(): array
    {
        return Cache::remember('v1.home.product_section_three', 86400, function (): array {
            return [
                'title' => get_setting('home_product_section_3_title'),
                'banner' => [
                    'img' => $this->contentMedia->asset(get_setting('home_product_section_3_banner_img')),
                    'link' => get_setting('home_product_section_3_banner_link'),
                ],
                'products' => new ProductCollection($this->manualProducts('home_product_section_3_products')),
            ];
        });
    }

    private function productSectionFour(): array
    {
        $products = Product::query()
            ->with(['variations'])
            ->withCount('carts')
            ->frontendVisible()
            ->where(function ($query) {
                $query->where('num_of_sale', '>', 0)->orWhereHas('carts');
            })
            ->orderByDesc('num_of_sale')
            ->orderByDesc('carts_count')
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();

        return [
            'title' => get_setting('home_product_section_4_title') ?: 'Best Selling',
            'products' => new ProductCollection($products),
        ];
    }

    private function productSectionFive(): array
    {
        return Cache::remember('v1.home.product_section_five', 86400, function (): array {
            return [
                'title' => get_setting('home_product_section_5_title'),
                'products' => new ProductCollection($this->manualProducts('home_product_section_5_products')),
            ];
        });
    }

    private function productSectionSix(): array
    {
        return Cache::remember('v1.home.product_section_six', 86400, function (): array {
            return [
                'title' => get_setting('home_product_section_6_title'),
                'banner' => [
                    'img' => $this->contentMedia->asset(get_setting('home_product_section_6_banner_img')),
                    'link' => get_setting('home_product_section_6_banner_link'),
                ],
                'products' => new ProductCollection($this->manualProducts('home_product_section_6_products')),
            ];
        });
    }

    private function homeAboutText(): array
    {
        return [
            'content' => get_setting('home_about_us'),
            'youtube_url' => get_setting('home_about_youtube_url'),
        ];
    }

    private function shopSection(int $number): array
    {
        return Cache::remember("v1.home.shop_section_{$number}", 86400, function () use ($number): array {
            $shops = $this->manualShops("home_shop_section_{$number}_shops");

            return [
                'title' => get_setting("home_shop_section_{$number}_title"),
                'shops' => new ShopCollection($shops, true),
            ];
        });
    }

    private function bannerSection(
        string $imagesSettingKey,
        string $linksSettingKey,
        ?string $sectionName = null,
        string $source = 'primary',
        ?int $limit = null
    ): array
    {
        $imageIds = $this->decodeSettingArray(get_setting($imagesSettingKey));
        $links = $this->decodeSettingArray(get_setting($linksSettingKey));

        if ($limit !== null) {
            $imageIds = array_slice($imageIds, 0, $limit);
            $links = array_slice($links, 0, $limit);
        }

        $payload = collect($imageIds)->map(function ($imageId, $index) use ($links) {
            $img = $this->contentMedia->asset($imageId);
            $link = $this->normalizeBannerLink($links[$index] ?? null, null);

            return [
                'img' => $img,
                'link' => $link,
                'is_clickable' => $img !== null && $link !== null,
                'missing_image' => $img === null,
            ];
        })->filter(fn ($banner) => $banner['img'] !== null)->values()->all();

        $this->logBannerSectionRead(
            sectionName: $sectionName ?? $imagesSettingKey,
            imagesSettingKey: $imagesSettingKey,
            linksSettingKey: $linksSettingKey,
            payload: $payload,
            source: $source
        );

        return $payload;
    }

    private function fixedBannerSection(
        string $imagesSettingKey,
        string $linksSettingKey,
        array $slots,
        ?string $sectionName = null,
        string $source = 'primary'
    ): array
    {
        $imageIds = $this->decodeSettingArray(get_setting($imagesSettingKey));
        $links = $this->decodeSettingArray(get_setting($linksSettingKey));

        $payload = collect($slots)->map(function (array $slot, int $index) use ($imageIds, $links) {
            $imageId = $imageIds[$index] ?? null;
            $link = $links[$index] ?? null;
            $isClickable = (bool) ($slot['clickable'] ?? true);
            $resolvedImage = $this->contentMedia->asset($imageId);
            $resolvedLink = $isClickable ? $this->normalizeBannerLink($link, null) : null;

            return [
                'slot' => $slot['slot'],
                'img' => $resolvedImage,
                'link' => $resolvedLink,
                'is_clickable' => $isClickable && !empty($resolvedImage) && $resolvedLink !== null,
                'missing_image' => empty($resolvedImage),
            ];
        })->values()->all();

        $this->logBannerSectionRead(
            sectionName: $sectionName ?? $imagesSettingKey,
            imagesSettingKey: $imagesSettingKey,
            linksSettingKey: $linksSettingKey,
            payload: $payload,
            source: $source
        );

        return $payload;
    }

    private function manualProducts(string $settingKey)
    {
        $ids = $this->decodeSettingArray(get_setting($settingKey));

        if ($ids === []) {
            return collect();
        }

        return filter_products(Product::query()->whereIn('id', $ids))->get();
    }

    private function manualShops(string $settingKey)
    {
        $ids = $this->decodeSettingArray(get_setting($settingKey));

        if ($ids === []) {
            return collect();
        }

        return filter_shops(
            Shop::query()->withCount(['products', 'reviews'])->whereIn('id', $ids)
        )->get();
    }

    private function decodeSettingArray(?string $value): array
    {
        $decoded = $value ? json_decode($value, true) : null;

        return is_array($decoded) ? array_values($decoded) : [];
    }

    private function normalizeBannerLink(mixed $value, ?string $fallback = null): ?string
    {
        if (!is_string($value)) {
            return $fallback;
        }

        $normalized = trim($value);

        if ($normalized === '') {
            return $fallback;
        }

        return $normalized;
    }

    private function payloadHasImage(array $payload): bool
    {
        return collect($payload)->contains(fn (array $item) => !empty($item['img']));
    }

    private function logBannerSectionRead(
        string $sectionName,
        string $imagesSettingKey,
        string $linksSettingKey,
        array $payload,
        string $source = 'primary'
    ): void {
        $resolvedImages = collect($payload)->filter(fn (array $item) => !empty($item['img']))->count();
        $missingSlots = collect($payload)
            ->filter(function (array $item) {
                if (array_key_exists('missing_image', $item)) {
                    return (bool) $item['missing_image'];
                }

                return empty($item['img']);
            })
            ->map(fn (array $item, int $index) => $item['slot'] ?? $index)
            ->values()
            ->all();

        Log::debug('Homepage banner section resolved', [
            'section' => $sectionName,
            'images_setting_key' => $imagesSettingKey,
            'links_setting_key' => $linksSettingKey,
            'source' => $source,
            'item_count' => count($payload),
            'resolved_image_count' => $resolvedImages,
            'missing_slots' => $missingSlots,
        ]);
    }
}
