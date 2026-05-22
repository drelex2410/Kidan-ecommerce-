<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminSettingsPersistenceTest extends TestCase
{
    protected User $admin;

    protected array $settingTypes = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::query()->findOrFail(1);
    }

    protected function tearDown(): void
    {
        if (!empty($this->settingTypes)) {
            Setting::query()->whereIn('type', $this->settingTypes)->delete();
        }

        parent::tearDown();
    }

    public function test_settings_update_replaces_duplicate_rows_and_refreshes_cached_value(): void
    {
        $type = 'test_banner_setting_' . Str::lower(Str::random(8));
        $this->settingTypes[] = $type;

        Setting::query()->create([
            'type' => $type,
            'value' => '101',
        ]);

        Setting::query()->create([
            'type' => $type,
            'value' => '202',
        ]);

        Cache::forget('settings');
        $this->assertSame('202', get_setting($type));

        $this->actingAs($this->admin)
            ->post(route('settings.update'), [
                'types' => [$type],
                $type => '999',
            ])
            ->assertRedirect();

        $values = Setting::query()
            ->where('type', $type)
            ->orderBy('id')
            ->pluck('value')
            ->all();

        $this->assertSame(['999', '999'], $values);
        $this->assertSame('999', get_setting($type));
    }

    public function test_get_setting_prefers_latest_non_empty_duplicate_value(): void
    {
        $type = 'test_banner_setting_' . Str::lower(Str::random(8));
        $this->settingTypes[] = $type;

        Setting::query()->create([
            'type' => $type,
            'value' => '101',
        ]);

        Setting::query()->create([
            'type' => $type,
            'value' => '',
        ]);

        Cache::forget('settings');

        $this->assertSame('101', get_setting($type));
    }

    public function test_settings_update_does_not_clear_existing_value_when_request_field_is_missing(): void
    {
        $type = 'test_banner_setting_' . Str::lower(Str::random(8));
        $this->settingTypes[] = $type;

        Setting::query()->create([
            'type' => $type,
            'value' => '515',
        ]);

        Cache::forget('settings');

        $this->actingAs($this->admin)
            ->post(route('settings.update'), [
                'types' => [$type],
            ])
            ->assertRedirect();

        Cache::forget('settings');

        $this->assertSame('515', get_setting($type));
        $this->assertSame(['515'], Setting::query()->where('type', $type)->pluck('value')->all());
    }

    public function test_fixed_homepage_banner_arrays_preserve_existing_values_for_blank_slots(): void
    {
        $type = 'home_banner_4_images';
        $this->settingTypes[] = $type;

        Setting::query()->create([
            'type' => $type,
            'value' => json_encode(['11', '22', '33', '44']),
        ]);

        Cache::forget('settings');

        $this->actingAs($this->admin)
            ->post(route('settings.update'), [
                'types' => [$type],
                $type => [null, null, '99', null],
            ])
            ->assertRedirect();

        Cache::forget('settings');

        $this->assertSame(
            json_encode(['11', '22', '99', '44']),
            Setting::query()->where('type', $type)->value('value')
        );
    }
}
