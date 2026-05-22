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
}
