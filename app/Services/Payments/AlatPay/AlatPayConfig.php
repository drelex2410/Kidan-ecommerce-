<?php

namespace App\Services\Payments\AlatPay;

use App\Models\AlatPayTransaction;
use App\Models\AlatPayWebhookLog;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class AlatPayConfig
{
    private const SETTING_KEYS = [
        'enabled' => 'alatpay_payment',
        'environment' => 'alatpay_env',
        'base_url' => 'alatpay_base_url',
        'merchant_id' => 'alatpay_merchant_id',
        'public_key' => 'alatpay_public_key',
        'plugin_script_url' => 'alatpay_plugin_script_url',
        'client_id' => 'alatpay_client_id',
        'client_secret' => 'alatpay_client_secret',
        'subscription_key' => 'alatpay_subscription_key',
        'callback_url' => 'alatpay_callback_url',
        'webhook_secret' => 'alatpay_webhook_secret',
        'supported_currencies' => 'alatpay_supported_currencies',
        'charge_type' => 'alatpay_charge_type',
        'charge_flat' => 'alatpay_charge_flat',
        'charge_percent' => 'alatpay_charge_percent',
    ];

    private const ENCRYPTED_KEYS = [
        'client_secret',
        'subscription_key',
        'webhook_secret',
    ];

    public function __construct(private readonly AlatPayRoutes $routes)
    {
    }

    public function enabled(): bool
    {
        return (int) $this->setting('enabled', env('ALATPAY_ENABLED', 0)) === 1;
    }

    public function environment(): string
    {
        if (app()->environment('production')) {
            return 'production';
        }

        $value = strtolower((string) ($this->setting('environment', env('ALATPAY_ENV', 'sandbox')) ?: 'sandbox'));

        return in_array($value, ['sandbox', 'production'], true) ? $value : 'sandbox';
    }

    public function baseUrl(): string
    {
        if ($override = $this->productionOverride('base_url')) {
            return rtrim($override, '/');
        }

        $configured = trim((string) ($this->setting('base_url', env('ALATPAY_BASE_URL')) ?? ''));
        if ($configured !== '') {
            return rtrim($configured, '/');
        }

        return $this->defaultBaseUrlForEnvironment($this->environment());
    }

    public function defaultBaseUrlForEnvironment(?string $environment = null): string
    {
        $env = strtolower((string) ($environment ?: $this->environment()));

        return rtrim($env === 'production'
            ? config('alatpay.production_base_url')
            : config('alatpay.sandbox_base_url'), '/');
    }

    public function merchantId(): ?string
    {
        if ($override = $this->productionOverride('merchant_id')) {
            return $override;
        }

        return $this->nullableString($this->setting('merchant_id', env('ALATPAY_MERCHANT_ID')));
    }

    public function clientId(): ?string
    {
        if ($override = $this->productionOverride('client_id')) {
            return $override;
        }

        return $this->nullableString($this->setting('client_id', env('ALATPAY_CLIENT_ID')));
    }

    public function publicKey(): ?string
    {
        if ($override = $this->productionOverride('public_key')) {
            return $override;
        }

        return $this->nullableString($this->setting('public_key', env('ALATPAY_PUBLIC_KEY')));
    }

    public function pluginScriptUrl(): ?string
    {
        if ($override = $this->productionOverride('plugin_script_url')) {
            return $override;
        }

        return $this->nullableString(
            $this->setting('plugin_script_url', env('ALATPAY_PLUGIN_SCRIPT_URL', config('alatpay.plugin_script_url')))
        );
    }

    public function clientSecret(): ?string
    {
        if ($override = $this->productionOverride('client_secret')) {
            return $override;
        }

        return $this->nullableString($this->setting('client_secret', env('ALATPAY_CLIENT_SECRET')));
    }

    public function secretKey(): ?string
    {
        return $this->clientSecret();
    }

    public function subscriptionKey(): ?string
    {
        if ($override = $this->productionOverride('subscription_key')) {
            return $override;
        }

        return $this->nullableString($this->setting('subscription_key', env('ALATPAY_SUBSCRIPTION_KEY')));
    }

    public function callbackUrl(): string
    {
        if ($override = $this->productionOverride('callback_url')) {
            return $override;
        }

        $fallback = env('ALATPAY_CALLBACK_URL', $this->routes->webhook());

        return (string) ($this->setting('callback_url', $fallback) ?: $fallback);
    }

    public function webhookSecret(): ?string
    {
        if ($override = $this->productionOverride('webhook_secret')) {
            return $override;
        }

        return $this->nullableString($this->setting('webhook_secret', env('ALATPAY_WEBHOOK_SECRET')));
    }

    public function supportedCurrencies(): array
    {
        $raw = $this->setting('supported_currencies', '["NGN"]');
        if (is_array($raw)) {
            $values = $raw;
        } else {
            $decoded = json_decode((string) $raw, true);
            $values = is_array($decoded) ? $decoded : explode(',', (string) $raw);
        }

        return array_values(array_unique(array_filter(array_map(
            static fn ($value) => strtoupper(trim((string) $value)),
            $values
        ))));
    }

    public function chargeType(): string
    {
        $value = strtolower((string) ($this->setting('charge_type', 'percentage') ?: 'percentage'));

        return in_array($value, ['flat', 'percentage'], true) ? $value : 'percentage';
    }

    public function chargeFlat(): float
    {
        return (float) ($this->setting('charge_flat', '0') ?: 0);
    }

    public function chargePercent(): float
    {
        return (float) ($this->setting('charge_percent', '0') ?: 0);
    }

    public function path(string $key): string|array
    {
        return config("alatpay.paths.{$key}");
    }

    public function isConfigured(): bool
    {
        return filled($this->merchantId())
            && filled($this->secretKey())
            && filled($this->baseUrl());
    }

    public function webPluginReady(): bool
    {
        return $this->isConfigured()
            && filled($this->publicKey())
            && filled($this->pluginScriptUrl());
    }

    public function checkoutFlow(): string
    {
        return $this->webPluginReady() ? 'web_plugin' : 'virtual_account';
    }

    public function authHeaders(): array
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        if ($subscriptionKey = $this->subscriptionKey()) {
            $headers['Ocp-Apim-Subscription-Key'] = $subscriptionKey;
        }

        if ($clientId = $this->clientId()) {
            $headers['X-Client-Id'] = $clientId;
        }

        if ($clientSecret = $this->clientSecret()) {
            $headers['X-Client-Secret'] = $clientSecret;
            $headers['Authorization'] = 'Bearer ' . $clientSecret;
        }

        if ($merchantId = $this->merchantId()) {
            $headers['X-Merchant-Id'] = $merchantId;
        }

        return $headers;
    }

    public function save(array $validated): void
    {
        $pairs = [
            'enabled' => array_key_exists('alatpay_payment', $validated)
                ? (string) ((int) $validated['alatpay_payment'])
                : (string) ((int) $this->enabled()),
            'environment' => $validated['alatpay_env'] ?? $this->environment(),
            'base_url' => $validated['alatpay_base_url'] ?? $this->baseUrl(),
            'merchant_id' => $validated['alatpay_merchant_id'] ?? $this->merchantId(),
            'public_key' => $validated['alatpay_public_key'] ?? $this->publicKey(),
            'plugin_script_url' => $validated['alatpay_plugin_script_url'] ?? $this->pluginScriptUrl(),
            'client_id' => $validated['alatpay_client_id'] ?? $this->clientId(),
            'callback_url' => $validated['alatpay_callback_url'] ?? $this->callbackUrl(),
            'supported_currencies' => json_encode($validated['alatpay_supported_currencies'] ?? $this->supportedCurrencies()),
            'charge_type' => $validated['alatpay_charge_type'] ?? $this->chargeType(),
            'charge_flat' => (string) ($validated['alatpay_charge_flat'] ?? $this->chargeFlat()),
            'charge_percent' => (string) ($validated['alatpay_charge_percent'] ?? $this->chargePercent()),
        ];

        foreach ($pairs as $key => $value) {
            $this->storeSetting($key, $value);
        }

        foreach (['client_secret', 'subscription_key', 'webhook_secret'] as $key) {
            if (array_key_exists("alatpay_{$key}", $validated)) {
                $secretValue = (string) ($validated["alatpay_{$key}"] ?? '');
                if ($secretValue !== '') {
                    $this->storeSetting($key, Crypt::encryptString($secretValue));
                }
            }
        }

        Cache::forget('settings');
        Cache::forget('alatpay.config.summary');
    }

    public function secretsState(): array
    {
        return [
            'client_secret' => filled($this->clientSecret()),
            'subscription_key' => filled($this->subscriptionKey()),
            'webhook_secret' => filled($this->webhookSecret()),
        ];
    }

    public function summary(): array
    {
        return Cache::remember('alatpay.config.summary', 300, function (): array {
            $lastWebhook = AlatPayWebhookLog::query()->latest('received_at')->first();
            $lastSuccess = AlatPayTransaction::query()->where('status', 'successful')->latest('completed_at')->first();
            $secretState = $this->secretsState();

            return [
                'enabled' => $this->enabled(),
                'configured' => $this->isConfigured(),
                'environment' => $this->environment(),
                'base_url' => $this->baseUrl(),
                'callback_url' => $this->callbackUrl(),
                'supported_currencies' => $this->supportedCurrencies(),
                'checkout_flow' => $this->checkoutFlow(),
                'web_plugin_ready' => $this->webPluginReady(),
                'last_webhook_received_at' => $lastWebhook?->received_at,
                'last_successful_transaction_at' => $lastSuccess?->completed_at,
                'last_successful_reference' => $lastSuccess?->reference,
                'gateway_status' => $this->enabled() && $this->isConfigured() ? 'ready' : 'incomplete',
                'public_key_configured' => filled($this->publicKey()),
                'plugin_script_url' => $this->pluginScriptUrl(),
                'has_client_secret' => $secretState['client_secret'],
                'has_subscription_key' => $secretState['subscription_key'],
                'has_webhook_secret' => $secretState['webhook_secret'],
            ];
        });
    }

    private function setting(string $alias, mixed $fallback = null): mixed
    {
        $type = self::SETTING_KEYS[$alias] ?? null;
        if (!$type) {
            return $fallback;
        }

        $setting = Setting::query()->where('type', $type)->first();
        if (!$setting || $setting->value === null || $setting->value === '') {
            return $fallback;
        }

        if (in_array($alias, self::ENCRYPTED_KEYS, true)) {
            try {
                return Crypt::decryptString((string) $setting->value);
            } catch (\Throwable) {
                return $fallback;
            }
        }

        return $setting->value;
    }

    private function storeSetting(string $alias, mixed $value): void
    {
        $type = self::SETTING_KEYS[$alias] ?? null;
        if (!$type) {
            return;
        }

        Setting::query()->updateOrCreate(
            ['type' => $type],
            ['value' => $value]
        );
    }

    private function nullableString(mixed $value): ?string
    {
        if (!is_string($value) && !is_numeric($value)) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }

    private function productionOverride(string $alias): ?string
    {
        if (!app()->environment('production')) {
            return null;
        }

        return match ($alias) {
            'base_url' => $this->nullableString(env('ALATPAY_BASE_URL', config('alatpay.production_base_url'))),
            'merchant_id' => $this->nullableString(env('ALATPAY_MERCHANT_ID')),
            'public_key' => $this->nullableString(env('ALATPAY_PUBLIC_KEY')),
            'plugin_script_url' => $this->nullableString(env('ALATPAY_PLUGIN_SCRIPT_URL', config('alatpay.plugin_script_url'))),
            'client_id' => $this->nullableString(env('ALATPAY_CLIENT_ID')),
            'client_secret' => $this->nullableString(env('ALATPAY_CLIENT_SECRET')),
            'subscription_key' => $this->nullableString(env('ALATPAY_SUBSCRIPTION_KEY')),
            'callback_url' => $this->nullableString(env('ALATPAY_CALLBACK_URL')),
            'webhook_secret' => $this->nullableString(env('ALATPAY_WEBHOOK_SECRET')),
            default => null,
        };
    }
}
