<?php

namespace App\Services\Uploads;

class UploadReferenceRegistry
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function definitions(): array
    {
        return [
            ['table' => 'users', 'column' => 'avatar', 'kind' => 'scalar', 'label' => 'User avatar'],
            ['table' => 'delivery_boys', 'column' => 'avatar', 'kind' => 'scalar', 'label' => 'Delivery boy avatar'],
            ['table' => 'brands', 'column' => 'logo', 'kind' => 'scalar', 'label' => 'Brand logo'],
            ['table' => 'categories', 'column' => 'banner', 'kind' => 'scalar', 'label' => 'Category banner'],
            ['table' => 'categories', 'column' => 'icon', 'kind' => 'scalar', 'label' => 'Category icon'],
            ['table' => 'categories', 'column' => 'meta_image', 'kind' => 'scalar', 'label' => 'Category meta image'],
            ['table' => 'categories', 'column' => 'meta_img', 'kind' => 'scalar', 'label' => 'Category meta image'],
            ['table' => 'products', 'column' => 'thumbnail_img', 'kind' => 'scalar', 'label' => 'Product thumbnail'],
            ['table' => 'products', 'column' => 'meta_image', 'kind' => 'scalar', 'label' => 'Product meta image'],
            ['table' => 'products', 'column' => 'meta_img', 'kind' => 'scalar', 'label' => 'Product meta image'],
            ['table' => 'products', 'column' => 'photos', 'kind' => 'csv', 'label' => 'Product gallery'],
            ['table' => 'product_variations', 'column' => 'img', 'kind' => 'scalar', 'label' => 'Product variation image'],
            ['table' => 'shops', 'column' => 'logo', 'kind' => 'scalar', 'label' => 'Shop logo'],
            ['table' => 'shops', 'column' => 'banners', 'kind' => 'csv', 'label' => 'Shop banners'],
            ['table' => 'shops', 'column' => 'products_banners', 'kind' => 'json_media', 'label' => 'Shop product banners'],
            ['table' => 'blogs', 'column' => 'banner', 'kind' => 'scalar', 'label' => 'Blog banner'],
            ['table' => 'blogs', 'column' => 'editorial_image', 'kind' => 'scalar', 'label' => 'Blog editorial image'],
            ['table' => 'blogs', 'column' => 'meta_img', 'kind' => 'scalar', 'label' => 'Blog meta image'],
            ['table' => 'blogs', 'column' => 'meta_image', 'kind' => 'scalar', 'label' => 'Blog meta image'],
            ['table' => 'blogs', 'column' => 'photos', 'kind' => 'csv', 'label' => 'Blog gallery'],
            ['table' => 'offers', 'column' => 'banner', 'kind' => 'scalar', 'label' => 'Offer banner'],
            ['table' => 'coupons', 'column' => 'banner', 'kind' => 'scalar', 'label' => 'Coupon banner'],
            ['table' => 'manual_payment_methods', 'column' => 'photo', 'kind' => 'scalar', 'label' => 'Manual payment photo'],
            ['table' => 'reviews', 'column' => 'image', 'kind' => 'scalar', 'label' => 'Review image'],
            ['table' => 'pages', 'column' => 'meta_image', 'kind' => 'scalar', 'label' => 'Page meta image'],
            ['table' => 'page_sections', 'column' => 'image', 'kind' => 'scalar', 'label' => 'Page section image'],
            ['table' => 'page_sections', 'column' => 'image_2', 'kind' => 'scalar', 'label' => 'Page section secondary image'],
            ['table' => 'page_sections', 'column' => 'settings_json', 'kind' => 'json_media', 'label' => 'Page section media JSON'],
            ['table' => 'settings', 'column' => 'value', 'kind' => 'settings_media', 'label' => 'Image-related settings'],
        ];
    }

    /**
     * @return array<int, string>
     */
    public function bannerSettingKeys(): array
    {
        return array_values((array) config('uploads.banner_setting_keys', []));
    }

    /**
     * @return array<int, string>
     */
    public function imageSettingKeys(): array
    {
        return [
            'header_logo',
            'footer_logo',
            'customer_chat_logo',
            'topbar_banner',
            'meta_image',
            'login_page_banner',
            'delivery_boy_login_page_banner',
            'registration_page_banner',
            'forgot_page_banner',
            'listing_page_banner',
            'product_page_banner',
            'checkout_page_banner',
            'dashboard_page_top_banner',
            'dashboard_page_bottom_banner',
            'all_shops_page_banner',
            'shop_registration_page_banner',
            'home_product_section_3_banner_img',
            'home_product_section_6_banner_img',
            ...$this->bannerSettingKeys(),
            'home_shop_banner_1_images',
            'home_shop_banner_2_images',
            'home_shop_banner_3_images',
        ];
    }

    public function isBannerSettingKey(string $key): bool
    {
        return in_array($key, $this->bannerSettingKeys(), true);
    }

    public function isImageSettingKey(string $key): bool
    {
        return in_array($key, $this->imageSettingKeys(), true);
    }
}
