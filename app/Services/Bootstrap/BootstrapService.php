<?php

namespace App\Services\Bootstrap;

use App\Contracts\AddonStatusManager;
use App\Http\Resources\ManualPaymentResource;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Language;
use App\Models\ManualPaymentMethod;
use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class BootstrapService
{
    public function __construct(
        private readonly AddonStatusManager $addonStatusManager
    ) {
    }

    public function build(): array
    {
        $settings = $this->settings();
        $metaTitle = $this->setting($settings, 'meta_title', (string) config('app.name'));
        $metaDescription = $this->setting($settings, 'meta_description');

        $payload = [
            'appName' => $this->setting($settings, 'site_name', (string) config('app.name')),
            'meta' => [
                'title' => $metaTitle,
                'description' => $metaDescription,
                'keywords' => $this->setting($settings, 'meta_keywords'),
                'image' => $this->assetUrl($this->setting($settings, 'meta_image')),
            ],
            'appMetaTitle' => $metaTitle,
            'appMetaDescription' => $metaDescription,
            'appLogo' => $this->assetUrl($this->setting($settings, 'header_logo'), static_asset('assets/img/logo.svg')),
            'appUrl' => rtrim((string) config('app.url'), '/') . '/',
            'cacheVersion' => $this->setting($settings, 'force_cache_clear_version', '1'),
            'demoMode' => (string) env('DEMO_MODE') === 'On',
            'appLanguage' => (string) env('DEFAULT_LANGUAGE', 'en'),
            'allLanguages' => $this->activeLanguages(),
            'allCurrencies' => $this->currencies(),
            'availableCountries' => $this->availableCountries(),
            'paymentMethods' => $this->paymentMethods($settings),
            'offlinePaymentMethods' => $this->offlinePaymentMethods($settings),
            'general_settings' => $this->generalSettings($settings),
            'addons' => $this->addonStatusManager->frontendPayload()->values()->all(),
            'banners' => $this->banners($settings),
            'refundSettings' => $this->refundSettings($settings),
            'shop_registration_message' => [
                'shop_registration_message_title' => $this->cleanText($this->setting($settings, 'shop_registration_message_title')),
                'shop_registration_message_content' => $this->cleanText($this->setting($settings, 'shop_registration_message_content')),
            ],
            'cookie_message' => [
                'cookie_title' => $this->cleanText($this->setting($settings, 'cookie_title')),
                'cookie_description' => $this->cleanText($this->setting($settings, 'cookie_description')),
            ],
            'authSettings' => [
                'customer_login_with' => $this->setting($settings, 'customer_login_with', 'email'),
                'customer_otp_with' => $this->setting($settings, 'customer_otp_with', 'disabled'),
            ],
        ];

        return $payload;
    }

    private function settings(): Collection
    {
        return Cache::remember('bootstrap.settings', 300, static fn () => Setting::query()->pluck('value', 'type'));
    }

    private function activeLanguages(): array
    {
        return Cache::remember('bootstrap.languages', 300, static function () {
            return Language::query()
                ->where('status', 1)
                ->get(['name', 'code', 'flag', 'rtl'])
                ->map(static function (Language $language) {
                    return [
                        'name' => $language->name,
                        'code' => $language->code,
                        'flag' => $language->flag,
                        'rtl' => (int) ($language->rtl ?? 0),
                    ];
                })
                ->values()
                ->all();
        });
    }

    private function currencies(): array
    {
        return Cache::remember('bootstrap.currencies', 300, static function () {
            return Currency::query()
                ->get(['id', 'name', 'symbol', 'code', 'exchange_rate'])
                ->map(static function (Currency $currency) {
                    return [
                        'id' => $currency->id,
                        'name' => $currency->name,
                        'symbol' => $currency->symbol,
                        'code' => $currency->code,
                        'exchange_rate' => $currency->exchange_rate,
                    ];
                })
                ->values()
                ->all();
        });
    }

    private function availableCountries(): array
    {
        return Cache::remember('bootstrap.countries', 300, static function () {
            return Country::query()
                ->where('status', 1)
                ->pluck('code')
                ->filter()
                ->values()
                ->all();
        });
    }

    private function generalSettings(Collection $settings): array
    {
        $defaultCurrency = Currency::find($this->setting($settings, 'system_default_currency'));

        return [
            'product_comparison' => $this->intSetting($settings, 'product_comparison'),
            'wallet_system' => $this->intSetting($settings, 'wallet_system'),
            'club_point' => $this->intSetting($settings, 'club_point'),
            'club_point_convert_rate' => $this->setting($settings, 'club_point_convert_rate'),
            'conversation_system' => $this->intSetting($settings, 'conversation_system'),
            'sticky_header' => $this->intSetting($settings, 'sticky_header'),
            'affiliate_system' => $this->intSetting($settings, 'affiliate_system'),
            'delivery_boy' => $this->intSetting($settings, 'delivery_boy'),
            'support_chat' => (bool) $this->intSetting($settings, 'support_chat'),
            'pickup_point' => (bool) $this->intSetting($settings, 'pickup_point'),
            'chat' => [
                'customer_chat_logo' => $this->assetUrl($this->setting($settings, 'customer_chat_logo')),
                'customer_chat_name' => $this->setting($settings, 'customer_chat_name'),
            ],
            'social_login' => [
                'google' => $this->intSetting($settings, 'google_login'),
                'facebook' => $this->intSetting($settings, 'facebook_login'),
                'twitter' => $this->intSetting($settings, 'twitter_login'),
            ],
            'currency' => [
                'code' => $defaultCurrency?->symbol,
                'decimal_separator' => $this->setting($settings, 'decimal_separator'),
                'symbol_format' => $this->setting($settings, 'symbol_format'),
                'no_of_decimals' => $this->setting($settings, 'no_of_decimals'),
                'truncate_price' => $this->setting($settings, 'truncate_price'),
            ],
        ];
    }

    private function paymentMethods(Collection $settings): array
    {
        $definitions = [
            ['setting' => 'paypal_payment', 'code' => 'paypal', 'name' => 'Paypal', 'img' => 'assets/img/cards/paypal.png'],
            ['setting' => 'stripe_payment', 'code' => 'stripe', 'name' => 'Stripe', 'img' => 'assets/img/cards/stripe.png'],
            ['setting' => 'sslcommerz_payment', 'code' => 'sslcommerz', 'name' => 'SSLCommerz', 'img' => 'assets/img/cards/sslcommerz.png'],
            ['setting' => 'paystack_payment', 'code' => 'paystack', 'name' => 'Paystack', 'img' => 'assets/img/cards/paystack.png'],
            ['setting' => 'flutterwave_payment', 'code' => 'flutterwave', 'name' => 'Flutterwave', 'img' => 'assets/img/cards/flutterwave.png'],
            ['setting' => 'razorpay_payment', 'code' => 'razorpay', 'name' => 'Razorpay', 'img' => 'assets/img/cards/razorpay.png'],
            ['setting' => 'paytm_payment', 'code' => 'paytm', 'name' => 'Paytm', 'img' => 'assets/img/cards/paytm.png'],
            ['setting' => 'payfast_payment', 'code' => 'payfast', 'name' => 'Payfast', 'img' => 'assets/img/cards/payfast.png'],
            ['setting' => 'authorizenet_payment', 'code' => 'authorizenet', 'name' => 'Authorize Net', 'img' => 'assets/img/cards/authorizenet.png'],
            ['setting' => 'mercadopago_payment', 'code' => 'mercadopago', 'name' => 'Mercadopago', 'img' => 'assets/img/cards/mercadopago.png'],
            ['setting' => 'iyzico_payment', 'code' => 'iyzico', 'name' => 'Iyzico', 'img' => 'assets/img/cards/iyzico.png'],
            ['setting' => 'myfatoorah_payment', 'code' => 'myfatoorah', 'name' => 'Myfatoorah', 'img' => 'assets/img/cards/myfatoorah.png'],
            ['setting' => 'phonepe_payment', 'code' => 'phonepe', 'name' => 'Phonepe', 'img' => 'assets/img/cards/phonepe.png'],
            ['setting' => 'payhere_payment', 'code' => 'payhere', 'name' => 'Payhere', 'img' => 'assets/img/cards/payhere.png'],
            ['setting' => 'cash_payment', 'code' => 'cash_on_delivery', 'name' => 'Cash on Delivery', 'img' => 'assets/img/cards/cod.png'],
        ];

        return collect($definitions)->map(function (array $definition) use ($settings) {
            return [
                'status' => $this->intSetting($settings, $definition['setting']),
                'code' => $definition['code'],
                'name' => $definition['name'],
                'img' => static_asset($definition['img']),
            ];
        })->values()->all();
    }

    private function offlinePaymentMethods(Collection $settings): array
    {
        if ($this->intSetting($settings, 'offline_payment') !== 1) {
            return [];
        }

        return json_decode(ManualPaymentResource::collection(ManualPaymentMethod::query()->get())->toJson(), true) ?? [];
    }

    private function banners(Collection $settings): array
    {
        $keys = [
            'login_page',
            'delivery_boy_login_page',
            'registration_page',
            'forgot_page',
            'listing_page',
            'product_page',
            'checkout_page',
            'dashboard_page_top',
            'dashboard_page_bottom',
            'all_shops_page',
            'shop_registration_page',
        ];

        $payload = [];

        foreach ($keys as $key) {
            $payload[$key] = [
                'img' => $this->assetUrl($this->setting($settings, "{$key}_banner")),
                'link' => $this->setting($settings, "{$key}_banner_link"),
            ];
        }

        return $payload;
    }

    private function refundSettings(Collection $settings): array
    {
        return [
            'refund_request_time_period' => $this->intSetting($settings, 'refund_request_time_period') * 86400,
            'refund_request_order_status' => $this->jsonSetting($settings, 'refund_request_order_status', []),
            'refund_reason_types' => $this->jsonSetting($settings, 'refund_reason_types', []),
        ];
    }

    private function assetUrl(mixed $value, ?string $fallback = null): ?string
    {
        $value = is_string($value) ? trim($value) : null;

        if ($value === null || $value === '') {
            return $fallback;
        }

        return api_asset($value);
    }

    private function cleanText(mixed $value): string
    {
        return str_replace(
            ['&amp;', '&nbsp;'],
            ['&', ' '],
            strip_tags((string) $value)
        );
    }

    private function setting(Collection $settings, string $key, mixed $default = null): mixed
    {
        return $settings->get($key, $default);
    }

    private function intSetting(Collection $settings, string $key, int $default = 0): int
    {
        return (int) $this->setting($settings, $key, $default);
    }

    private function jsonSetting(Collection $settings, string $key, array $default): array
    {
        $value = $this->setting($settings, $key);

        if (!is_string($value) || trim($value) === '') {
            return $default;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : $default;
    }
}
