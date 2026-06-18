<?php

namespace App\Http\Controllers;

use App\Contracts\ApplicationBootstrap;
use App\Http\Services\AdminShopService;
use App\Models\Setting;
use App\Models\Upload;
use App\Services\Payments\AlatPay\AlatPayConfig;
use App\Models\User;
use Artisan;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SettingController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:show_general_setting'])->only('general_setting');
        $this->middleware(['permission:smtp_setting'])->only('smtp_settings');
        $this->middleware(['permission:payment_method'])->only('payment_method');
        $this->middleware(['permission:file_system'])->only('file_system');
        $this->middleware(['permission:social_media_login'])->only('social_login');
        $this->middleware(['permission:third_party_setting'])->only('third_party_settings');
    }

    public function general_setting(Request $request, AdminShopService $adminShopService)
    {
        app(ApplicationBootstrap::class)->initialize();
        $shop = $adminShopService->ensureShopForUser($request->user());

        return view('backend.settings.general_settings', compact('shop'));
    }

    public function otp_settings(Request $request)
    {
        app(ApplicationBootstrap::class)->initialize();
        return view('backend.settings.otp');
    }

    public function social_login(Request $request)
    {
        app(ApplicationBootstrap::class)->initialize();
        return view('backend.settings.social_login');
    }

    public function smtp_settings(Request $request)
    {
        app(ApplicationBootstrap::class)->initialize();
        return view('backend.settings.smtp_settings');
    }

    public function third_party_settings(Request $request)
    {
        app(ApplicationBootstrap::class)->initialize();
        return view('backend.settings.third_party_settings');
    }

    public function payment_method(Request $request, AlatPayConfig $alatPayConfig)
    {
        app(ApplicationBootstrap::class)->initialize();
        $secretState = $alatPayConfig->secretsState();

        return view('backend.settings.payment_method', [
            'alatpaySummary' => $alatPayConfig->summary(),
            'alatpaySettings' => [
                'env' => $alatPayConfig->environment(),
                'base_url' => $alatPayConfig->baseUrl(),
                'merchant_id' => $alatPayConfig->merchantId(),
                'public_key' => $alatPayConfig->publicKey(),
                'plugin_script_url' => $alatPayConfig->pluginScriptUrl(),
                'client_id' => $alatPayConfig->clientId(),
                'client_secret' => '',
                'subscription_key' => '',
                'callback_url' => $alatPayConfig->callbackUrl(),
                'webhook_secret' => '',
                'supported_currencies' => implode(', ', $alatPayConfig->supportedCurrencies()),
                'charge_type' => $alatPayConfig->chargeType(),
                'charge_flat' => $alatPayConfig->chargeFlat(),
                'charge_percent' => $alatPayConfig->chargePercent(),
                'checkout_flow' => $alatPayConfig->checkoutFlow(),
                'web_plugin_ready' => $alatPayConfig->webPluginReady(),
                'has_client_secret' => $secretState['client_secret'],
                'has_subscription_key' => $secretState['subscription_key'],
                'has_webhook_secret' => $secretState['webhook_secret'],
            ],
        ]);
    }

    public function file_system(Request $request)
    {
        app(ApplicationBootstrap::class)->initialize();
        return view('backend.settings.file_system');
    }

    /**
     * Update the API key's for payment methods.
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function payment_method_update(Request $request)
    {
        foreach ($request->types as $key => $type) {
            if (! $this->storeEnvironmentSetting($type, $request[$type])) {
                return $this->settingsWriteErrorResponse();
            }
        }

        $business_settings = Setting::where('type', $request->payment_method . '_sandbox')->first();
        if ($business_settings != null) {
            if ($request->has($request->payment_method . '_sandbox')) {
                $business_settings->value = 1;
                $business_settings->save();
            } else {
                $business_settings->value = 0;
                $business_settings->save();
            }
        }

        cache_clear();

        flash(translate("Settings updated successfully"))->success();
        return back();
    }

    /**
     * Update the API key's for GOOGLE analytics.
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function google_analytics_update(Request $request)
    {
        foreach ($request->types as $key => $type) {
            if (! $this->storeEnvironmentSetting($type, $request[$type])) {
                return $this->settingsWriteErrorResponse();
            }
        }

        $business_settings = Setting::where('type', 'google_analytics')->first();

        if ($request->has('google_analytics')) {
            $business_settings->value = 1;
            $business_settings->save();
        } else {
            $business_settings->value = 0;
            $business_settings->save();
        }

        cache_clear();

        flash(translate("Settings updated successfully"))->success();
        return back();
    }

    public function google_recaptcha_update(Request $request)
    {
        foreach ($request->types as $key => $type) {
            if (! $this->storeEnvironmentSetting($type, $request[$type])) {
                return $this->settingsWriteErrorResponse();
            }
        }

        $business_settings = Setting::where('type', 'google_recaptcha')->first();

        if ($request->has('google_recaptcha')) {
            $business_settings->value = 1;
            $business_settings->save();
        } else {
            $business_settings->value = 0;
            $business_settings->save();
        }

        cache_clear();

        flash(translate("Settings updated successfully"))->success();
        return back();
    }


    /**
     * Update the API key's for GOOGLE analytics.
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function facebook_chat_update(Request $request)
    {
        foreach ($request->types as $key => $type) {
            if (! $this->storeEnvironmentSetting($type, $request[$type])) {
                return $this->settingsWriteErrorResponse();
            }
        }

        $business_settings = Setting::where('type', 'facebook_chat')->first();

        if ($request->has('facebook_chat')) {
            $business_settings->value = 1;
            $business_settings->save();
        } else {
            $business_settings->value = 0;
            $business_settings->save();
        }

        cache_clear();

        flash(translate("Settings updated successfully"))->success();
        return back();
    }

    public function facebook_pixel_update(Request $request)
    {
        foreach ($request->types as $key => $type) {
            if (! $this->storeEnvironmentSetting($type, $request[$type])) {
                return $this->settingsWriteErrorResponse();
            }
        }

        $business_settings = Setting::where('type', 'facebook_pixel')->first();

        if ($request->has('facebook_pixel')) {
            $business_settings->value = 1;
            $business_settings->save();
        } else {
            $business_settings->value = 0;
            $business_settings->save();
        }

        cache_clear();

        flash(translate("Settings updated successfully"))->success();
        return back();
    }

    /**
     * Update the API key's for other methods.
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function env_key_update(Request $request)
    {
        $environmentUpdates = $this->normalizeEnvironmentUpdates($request);

        foreach ($environmentUpdates as $type => $value) {
            if (! $this->storeEnvironmentSetting($type, $value)) {
                return $this->settingsWriteErrorResponse();
            }
        }

        cache_clear();

        flash(translate("Settings updated successfully"))->success();
        return back();
    }

    /**
     * overWrite the Env File values.
     * @param  String type
     * @param  String value
     * @return \Illuminate\Http\Response
     */
    public function overWriteEnvFile($type, $val)
    {
        return $this->storeEnvironmentSetting($type, $val);
    }

    public function initSetting()
    {
        app(ApplicationBootstrap::class)->initialize();
    }


    /**
     * Update sell verification form.
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request)
    {
        $this->validateHomepageBannerSettings($request);
        $updatedTypes = [];

        foreach ($request->types as $key => $type) {
            if ($type == 'timezone') {
                $this->overWriteEnvFile('APP_TIMEZONE', $request[$type]);
            } else {
                if (! $request->exists($type)) {
                    Log::warning('Skipping settings update because request field is missing', [
                        'type' => $type,
                        'route' => optional($request->route())->getName(),
                    ]);
                    continue;
                }

                $storedValue = $this->persistSettingValue(
                    $type,
                    $request->input($type),
                    $request->input($type . '_delete', [])
                );
                $updatedTypes[] = $type;
                $this->logHomepageBannerWrite(
                    settingsGroup: (string) $request->input('settings_group', ''),
                    type: $type,
                    storedValue: $storedValue
                );
            }
        }

        $this->forgetFrontendContentCache($updatedTypes);

        cache_clear();

        flash(translate("Settings updated successfully"))->success();
        return back();
    }

    public function shop_update(Request $request, AdminShopService $adminShopService)
    {
        $validated = $request->validate([
            'min_order' => ['required', 'numeric', 'min:0'],
        ]);

        $shop = $adminShopService->ensureShopForUser($request->user());

        if (!$shop) {
            abort(500, 'Unable to resolve the inhouse shop for this account.');
        }

        $shop->min_order = $validated['min_order'];
        $shop->save();

        flash(translate("Settings updated successfully"))->success();
        return back();
    }

    protected function persistSettingValue(string $type, $value, $deleteMarkers = [])
    {
        $settings = Setting::query()
            ->where('type', $type)
            ->orderBy('id')
            ->get();

        $existingValue = $settings->last()?->value;
        $storedValue = $this->normalizeSettingValue($type, $value, $existingValue, $deleteMarkers);

        if ($settings->isEmpty()) {
            $setting = new Setting;
            $setting->type = $type;
            $setting->value = $storedValue;
            $setting->save();
            return $storedValue;
        }

        if ($settings->count() > 1) {
            Log::warning('Duplicate settings rows detected while persisting setting', [
                'type' => $type,
                'duplicate_count' => $settings->count(),
                'setting_ids' => $settings->pluck('id')->all(),
            ]);
        }

        foreach ($settings as $setting) {
            $setting->value = $storedValue;
            $setting->save();
        }

        return $storedValue;
    }

    protected function normalizeSettingValue(string $type, $value, $existingValue, $deleteMarkers = [])
    {
        if (!is_array($value)) {
            return $value;
        }

        if (!$this->shouldPreserveSparseArrayEntries($type)) {
            $value = $this->applyDeleteMarkersToArray($value, $deleteMarkers);

            return json_encode($value);
        }

        $existing = [];
        if (is_string($existingValue) && $existingValue !== '') {
            $decoded = json_decode($existingValue, true);
            if (is_array($decoded)) {
                $existing = $decoded;
            }
        }

        $merged = $this->mergeSparseArrayEntries($value, $existing, $deleteMarkers);

        return json_encode($merged);
    }

    protected function shouldPreserveSparseArrayEntries(string $type): bool
    {
        return in_array($type, [
            'home_banner_2_images',
            'home_banner_2_links',
            'home_banner_4_images',
            'home_banner_4_links',
        ], true);
    }

    protected function validateHomepageBannerSettings(Request $request): void
    {
        $group = (string) $request->input('settings_group', '');

        if ($group === '') {
            return;
        }

        $messages = match ($group) {
            'hero_slider' => $this->validateDynamicBannerGroup(
                imagesType: 'home_slider_1_images',
                linksType: 'home_slider_1_links',
                imageLabel: 'Hero slider',
                request: $request,
                requireAtLeastOneImage: false,
            ),
            'home_banner_1' => $this->validateDynamicBannerGroup(
                imagesType: 'home_banner_1_images',
                linksType: 'home_banner_1_links',
                imageLabel: 'Banner 1',
                request: $request,
                requireAtLeastOneImage: false,
            ),
            'home_banner_2' => $this->validateFixedBannerGroup(
                imagesType: 'home_banner_2_images',
                linksType: 'home_banner_2_links',
                request: $request,
                requiredSlots: [],
                requiredLinks: [],
                ignoredLinkSlots: [0],
            ),
            'home_banner_4' => $this->validateFixedBannerGroup(
                imagesType: 'home_banner_4_images',
                linksType: 'home_banner_4_links',
                request: $request,
                requiredSlots: [],
                requiredLinks: [],
                ignoredLinkSlots: [3],
            ),
            default => [],
        };

        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }
    }

    protected function validateDynamicBannerGroup(
        string $imagesType,
        string $linksType,
        string $imageLabel,
        Request $request,
        bool $requireAtLeastOneImage = false,
    ): array {
        $messages = [];
        $imageIds = $this->normalizeSubmittedArray($request->input($imagesType, []));
        $links = $this->normalizeSubmittedArray($request->input($linksType, []));

        $nonEmptyImageIds = array_values(array_filter($imageIds, fn ($value) => $value !== null && $value !== ''));

        if ($requireAtLeastOneImage && $nonEmptyImageIds === []) {
            $messages[$imagesType] = "{$imageLabel} requires at least one image.";
            return $messages;
        }

        $this->validateUploadIds($imagesType, $nonEmptyImageIds, $messages, $imageLabel);

        foreach ($links as $index => $link) {
            $this->validateBannerLink("{$linksType}.{$index}", $link, $messages, "{$imageLabel} link");
        }

        return $messages;
    }

    protected function validateFixedBannerGroup(
        string $imagesType,
        string $linksType,
        Request $request,
        array $requiredSlots,
        array $requiredLinks = [],
        array $ignoredLinkSlots = [],
    ): array {
        $messages = [];
        $mergedImages = $this->mergedRequestArray(
            $imagesType,
            $request->input($imagesType, []),
            $request->input($imagesType . '_delete', [])
        );
        $mergedLinks = $this->mergedRequestArray(
            $linksType,
            $request->input($linksType, []),
            $request->input($linksType . '_delete', [])
        );

        foreach ($requiredSlots as $index => $label) {
            $value = $mergedImages[$index] ?? null;

            if ($value === null || $value === '') {
                $messages["{$imagesType}.{$index}"] = "{$label} is required.";
                continue;
            }

            $this->validateUploadIds("{$imagesType}.{$index}", [$value], $messages, $label);
        }

        foreach ($mergedImages as $index => $value) {
            if ($value === null || $value === '' || array_key_exists("{$imagesType}.{$index}", $messages)) {
                continue;
            }

            $this->validateUploadIds("{$imagesType}.{$index}", [$value], $messages, "Banner image");
        }

        foreach ($mergedLinks as $index => $link) {
            if (in_array($index, $ignoredLinkSlots, true)) {
                continue;
            }

            $requiredLabel = $requiredLinks[$index] ?? null;
            $this->validateBannerLink(
                "{$linksType}.{$index}",
                $link,
                $messages,
                $requiredLabel ?: "Banner link",
                $requiredLabel !== null
            );
        }

        foreach ($requiredLinks as $index => $label) {
            if (!array_key_exists("{$linksType}.{$index}", $messages)) {
                $link = $mergedLinks[$index] ?? null;

                if (!is_string($link) || trim($link) === '') {
                    $messages["{$linksType}.{$index}"] = "{$label} is required.";
                }
            }
        }

        return $messages;
    }

    protected function mergedRequestArray(string $type, $submitted, $deleteMarkers = []): array
    {
        $submittedArray = $this->normalizeSubmittedArray($submitted);

        if (!$this->shouldPreserveSparseArrayEntries($type)) {
            return $this->applyDeleteMarkersToArray($submittedArray, $deleteMarkers);
        }

        return $this->mergeSparseArrayEntries($submittedArray, $this->existingSettingArray($type), $deleteMarkers);
    }

    protected function existingSettingArray(string $type): array
    {
        $existingValue = get_setting($type);

        if (!is_string($existingValue) || trim($existingValue) === '') {
            return [];
        }

        $decoded = json_decode($existingValue, true);

        return is_array($decoded) ? array_values($decoded) : [];
    }

    protected function normalizeSubmittedArray($value): array
    {
        if (!is_array($value)) {
            return [];
        }

        return array_map(function ($item) {
            if ($item === null) {
                return null;
            }

            if (!is_scalar($item)) {
                return null;
            }

            $normalized = trim((string) $item);

            return $normalized === '' ? null : $normalized;
        }, $value);
    }

    protected function mergeSparseArrayEntries(array $submitted, array $existing, $deleteMarkers = []): array
    {
        $deleteMarkers = $this->normalizeDeleteMarkers($deleteMarkers);
        $merged = $submitted;
        $maxIndex = max(array_keys($submitted + $existing + $deleteMarkers) ?: [-1]);

        for ($index = 0; $index <= $maxIndex; $index++) {
            if (($deleteMarkers[$index] ?? false) === true) {
                $merged[$index] = null;
                continue;
            }

            $submittedValue = $merged[$index] ?? null;
            if (($submittedValue === null || $submittedValue === '') && array_key_exists($index, $existing)) {
                $merged[$index] = $existing[$index];
            }
        }

        return $merged;
    }

    protected function applyDeleteMarkersToArray(array $submitted, $deleteMarkers = []): array
    {
        $deleteMarkers = $this->normalizeDeleteMarkers($deleteMarkers);

        if ($deleteMarkers === []) {
            return $submitted;
        }

        foreach ($deleteMarkers as $index => $shouldDelete) {
            if ($shouldDelete) {
                unset($submitted[$index]);
            }
        }

        return array_values($submitted);
    }

    protected function normalizeDeleteMarkers($deleteMarkers): array
    {
        if (!is_array($deleteMarkers)) {
            return [];
        }

        $normalized = [];

        foreach ($deleteMarkers as $index => $value) {
            $normalized[(int) $index] = in_array($value, [true, 1, '1', 'on', 'true'], true);
        }

        return $normalized;
    }

    protected function validateUploadIds(string $field, array $uploadIds, array &$messages, string $label): void
    {
        if ($uploadIds === []) {
            return;
        }

        $uploads = Upload::query()
            ->whereIn('id', $uploadIds)
            ->get()
            ->keyBy(fn (Upload $upload) => (string) $upload->id);

        foreach ($uploadIds as $uploadId) {
            $upload = $uploads->get((string) $uploadId);

            if (!$upload || $upload->type !== 'image') {
                $messages[$field] = "{$label} is invalid. Please reselect the image from Uploaded Files.";
                return;
            }

            if (!$upload->fileExists()) {
                $messages[$field] = "{$label} is missing from storage. Please upload the image again.";
                return;
            }
        }
    }

    protected function validateBannerLink(
        string $field,
        mixed $value,
        array &$messages,
        string $label,
        bool $required = false,
    ): void {
        $normalized = is_string($value) ? trim($value) : '';

        if ($normalized === '') {
            if ($required) {
                $messages[$field] = "{$label} is required.";
            }

            return;
        }

        if (preg_match('#^/link-\d+$#i', $normalized)) {
            $messages[$field] = "{$label} is using a placeholder path. Please enter a real route.";
            return;
        }

        if (!Str::startsWith($normalized, ['/', 'http://', 'https://', '#'])) {
            $messages[$field] = "{$label} must start with /, http://, https://, or #.";
        }
    }

    protected function forgetFrontendContentCache(array $types): void
    {
        $cacheKeys = [];

        if (collect($types)->contains(fn (string $type) => $this->isHomepageBannerSetting($type))) {
            $cacheKeys[] = 'v1.home.sliders';
        }

        if ($cacheKeys === []) {
            return;
        }

        foreach (array_unique($cacheKeys) as $cacheKey) {
            try {
                Cache::forget($cacheKey);
            } catch (\Throwable $exception) {
            }
        }

        Log::debug('Homepage content cache invalidated after settings update', [
            'updated_types' => array_values(array_unique($types)),
            'cache_keys' => array_values(array_unique($cacheKeys)),
        ]);
    }

    protected function isHomepageBannerSetting(string $type): bool
    {
        return Str::startsWith($type, [
            'home_slider_',
            'home_banner_',
        ]);
    }

    protected function logHomepageBannerWrite(string $settingsGroup, string $type, mixed $storedValue): void
    {
        if (!$this->isHomepageBannerSetting($type)) {
            return;
        }

        $slotCount = 0;
        $nonEmptySlots = 0;

        if (is_string($storedValue)) {
            $decoded = json_decode($storedValue, true);
            if (is_array($decoded)) {
                $slotCount = count($decoded);
                $nonEmptySlots = count(array_filter($decoded, fn ($value) => $value !== null && $value !== ''));
            } elseif ($storedValue !== '') {
                $slotCount = 1;
                $nonEmptySlots = 1;
            }
        } elseif (is_array($storedValue)) {
            $slotCount = count($storedValue);
            $nonEmptySlots = count(array_filter($storedValue, fn ($value) => $value !== null && $value !== ''));
        }

        Log::debug('Homepage banner setting persisted', [
            'settings_group' => $settingsGroup,
            'type' => $type,
            'slot_count' => $slotCount,
            'non_empty_slots' => $nonEmptySlots,
        ]);
    }

    public function updateActivationSettings(Request $request)
    {
        $env_changes = ['FORCE_HTTPS', 'FILESYSTEM_DRIVER'];
        if (in_array($request->type, $env_changes)) {

            return $this->updateActivationSettingsInEnv($request);
        }


        $business_settings = Setting::where('type', $request->type)->first();
        if ($business_settings != null) {
            if ($request->type == 'maintenance_mode' && $request->value == '1') {
                if (env('DEMO_MODE') != 'On') {
                    Artisan::call('down');
                }
            } elseif ($request->type == 'maintenance_mode' && $request->value == '0') {
                if (env('DEMO_MODE') != 'On') {
                    Artisan::call('up');
                }
            }

            if ($request->type == 'wallet_system' && $request->value == '0') {
                $club_point = Setting::where('type', 'club_point')->first();
                if (!is_null($club_point)) {
                    $club_point->value = 0;
                    $club_point->save();
                }
            }

            if ($request->type == 'club_point' && $request->value == '1') {
                $wallet_system = Setting::where('type', 'wallet_system')->first();
                if (!is_null($wallet_system) && $wallet_system->value == 0) {
                    return 'wallet_system_off';
                }
            }
            $business_settings->value = $request->value;
            $business_settings->save();
        } else {
            $business_settings = new Setting;
            $business_settings->type = $request->type;
            $business_settings->value = $request->value;
            $business_settings->save();
        }
        cache_clear();
        return '1';
    }

    public function updateActivationSettingsInEnv($request)
    {
        if ($request->type == 'FORCE_HTTPS' && $request->value == '1') {
            if (! $this->storeEnvironmentSetting($request->type, 'On')) {
                return 'env_not_writable';
            }

            if (strpos(env('APP_URL'), 'http:') !== FALSE) {
                if (! $this->storeEnvironmentSetting('APP_URL', str_replace("http:", "https:", env('APP_URL')))) {
                    return 'env_not_writable';
                }
            }
        } elseif ($request->type == 'FORCE_HTTPS' && $request->value == '0') {
            if (! $this->storeEnvironmentSetting($request->type, 'Off')) {
                return 'env_not_writable';
            }
            if (strpos(env('APP_URL'), 'https:') !== FALSE) {
                if (! $this->storeEnvironmentSetting('APP_URL', str_replace("https:", "http:", env('APP_URL')))) {
                    return 'env_not_writable';
                }
            }
        } elseif ($request->type == 'FILESYSTEM_DRIVER' && $request->value == '1') {
            if (! $this->storeEnvironmentSetting($request->type, 's3')) {
                return 'env_not_writable';
            }
        } elseif ($request->type == 'FILESYSTEM_DRIVER' && $request->value == '0') {
            if (! $this->storeEnvironmentSetting($request->type, 'local')) {
                return 'env_not_writable';
            }
        }

        return '1';
    }

    protected function storeEnvironmentSetting(string $type, mixed $value): bool
    {
        if (env('DEMO_MODE') === 'On') {
            return true;
        }

        $path = base_path('.env');
        if (! file_exists($path) || ! is_writable($path)) {
            report(new \RuntimeException(".env is not writable for setting [{$type}]"));

            return false;
        }

        try {
            $contents = file_get_contents($path);
            if ($contents === false) {
                throw new FileNotFoundException("Unable to read [.env] for setting [{$type}].");
            }

            $serializedValue = $this->serializeEnvironmentValue($value);
            $pattern = "/^" . preg_quote($type, '/') . "=.*$/m";
            $replacement = $type . '=' . $serializedValue;

            if (preg_match($pattern, $contents) === 1) {
                $updatedContents = preg_replace($pattern, $replacement, $contents, 1);
            } else {
                $updatedContents = rtrim($contents) . PHP_EOL . $replacement . PHP_EOL;
            }

            if ($updatedContents === null || file_put_contents($path, $updatedContents) === false) {
                throw new \RuntimeException("Unable to persist [.env] change for setting [{$type}].");
            }

            return true;
        } catch (\Throwable $exception) {
            report($exception);

            return false;
        }
    }

    protected function serializeEnvironmentValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value)) {
            $value = json_encode($value);
        }

        $value = trim((string) $value);

        if ($value === '') {
            return '""';
        }

        if (preg_match('/\s/', $value) === 1 || Str::contains($value, ['#', '"'])) {
            return '"' . addcslashes($value, "\\\"") . '"';
        }

        return $value;
    }

    protected function normalizeEnvironmentUpdates(Request $request): array
    {
        $updates = [];

        foreach ((array) $request->input('types', []) as $type) {
            $updates[$type] = $request->input($type);
        }

        if (array_key_exists('MAIL_MAILER', $updates) || array_key_exists('MAIL_DRIVER', $updates)) {
            $mailer = $updates['MAIL_MAILER'] ?? $updates['MAIL_DRIVER'];

            $updates['MAIL_MAILER'] = $mailer;
            $updates['MAIL_DRIVER'] = $mailer;
        }

        return $updates;
    }

    protected function settingsWriteErrorResponse()
    {
        flash(translate('This setting cannot be changed from the web UI until the server grants controlled write access to the environment configuration.'))->error();

        return back()->withErrors([
            'settings' => translate('Environment configuration is not writable. Update the server configuration and retry.'),
        ]);
    }

    public function shipping_configuration(Request $request)
    {
        return view('backend.settings.shipping_configuration.index');
    }

    public function shipping_configuration_update(Request $request)
    {
        $business_settings = Setting::where('type', $request->type)->first();
        $business_settings->value = $request[$request->type];
        $business_settings->save();


        cache_clear();
        return back();
    }
}
