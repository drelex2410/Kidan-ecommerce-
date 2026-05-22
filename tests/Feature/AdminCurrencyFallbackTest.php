<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AdminCurrencyFallbackTest extends TestCase
{
    public function test_system_default_currency_falls_back_to_first_active_currency_when_setting_is_missing(): void
    {
        $setting = Setting::query()->where('type', 'system_default_currency')->first();
        $originalValue = $setting?->value;

        if (!$setting) {
            $setting = new Setting;
            $setting->type = 'system_default_currency';
        }

        $setting->value = null;
        $setting->save();

        Cache::forget('settings');
        Cache::forget('system_default_currency');

        $fallbackCurrency = Currency::query()->where('status', 1)->orderBy('id')->first();

        $this->assertNotNull($fallbackCurrency);
        $this->assertSame($fallbackCurrency->id, optional(get_system_default_currency())->id);

        $setting->value = $originalValue;
        $setting->save();

        Cache::forget('settings');
        Cache::forget('system_default_currency');
    }
}
