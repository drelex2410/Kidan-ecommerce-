<?php

namespace App\Services\Content;

use App\Http\Resources\CategoryCollection;
use App\Http\Resources\ProductCollection;
use App\Http\Resources\ShopCollection;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Support\Facades\Cache;
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
            'banner_section_one' => $this->bannerSection('home_banner_1_images', 'home_banner_1_links'),
            'banner_section_two' => $this->bannerSection('home_banner_2_images', 'home_banner_2_links'),
            'banner_section_three' => $this->bannerSection('home_banner_3_images', 'home_banner_3_links'),
            'banner_section_four' => $this->bannerSection('home_banner_4_images', 'home_banner_4_links'),
            'home_about_text' => $this->homeAboutText(),
            'shop_section_one' => $this->shopSection(1),
            'shop_section_two' => $this->shopSection(2),
            'shop_section_three' => $this->shopSection(3),
            'shop_section_four' => $this->shopSection(4),
            'shop_section_five' => $this->shopSection(5),
            'shop_banner_section_one' => $this->bannerSection('home_shop_banner_1_images', 'home_shop_banner_1_links'),
            'shop_banner_section_two' => $this->bannerSection('home_shop_banner_2_images', 'home_shop_banner_2_links'),
            'shop_banner_section_three' => $this->bannerSection('home_shop_banner_3_images', 'home_shop_banner_3_links'),
            default => throw new NotFoundHttpException('Home section not found.'),
        };
    }

    private function sliders(): array
    {
        return Cache::remember('v1.home.sliders', 86400, function (): array {
            return [
                'one' => $this->bannerSection('home_slider_1_images', 'home_slider_1_links'),
                'two' => $this->bannerSection('home_slider_2_images', 'home_slider_2_links'),
                'three' => $this->bannerSection('home_slider_3_images', 'home_slider_3_links'),
                'four' => $this->bannerSection('home_slider_4_images', 'home_slider_4_links'),
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

    private function bannerSection(string $imagesSettingKey, string $linksSettingKey): array
    {
        $imageIds = $this->decodeSettingArray(get_setting($imagesSettingKey));
        $links = $this->decodeSettingArray(get_setting($linksSettingKey));

        return collect($imageIds)->map(function ($imageId, $index) use ($links) {
            return [
                'img' => $this->contentMedia->asset($imageId),
                'link' => $links[$index] ?? null,
            ];
        })->filter(fn ($banner) => $banner['img'] !== null)->values()->all();
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
}
