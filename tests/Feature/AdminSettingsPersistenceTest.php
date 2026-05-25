<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\Upload;
use App\Models\User;
use App\Support\Uploads\UploadStorage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminSettingsPersistenceTest extends TestCase
{
    protected User $admin;

    protected array $settingTypes = [];
    protected array $snapshottedSettings = [];
    protected array $temporaryUploadIds = [];
    protected array $temporaryUploadPaths = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::query()->findOrFail(1);
    }

    protected function tearDown(): void
    {
        $this->restoreSnapshottedSettings();

        if (!empty($this->temporaryUploadIds)) {
            Upload::query()->whereIn('id', $this->temporaryUploadIds)->get()->each->deleteStoredFile();
            Upload::query()->whereIn('id', $this->temporaryUploadIds)->delete();
        }

        foreach ($this->temporaryUploadPaths as $path) {
            if (is_string($path) && file_exists($path)) {
                @unlink($path);
            }
        }

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

    public function test_settings_update_clears_frontend_content_cache_keys(): void
    {
        $type = 'test_banner_setting_' . Str::lower(Str::random(8));
        $this->settingTypes[] = $type;

        Setting::query()->create([
            'type' => $type,
            'value' => '101',
        ]);

        Cache::put('v1.home.sliders', ['stale' => true], 86400);
        Cache::put('v1.header_setting', ['stale' => true], 86400);
        Cache::put('header_setting', ['stale' => true], 86400);

        $this->assertTrue(Cache::has('v1.home.sliders'));
        $this->assertTrue(Cache::has('v1.header_setting'));
        $this->assertTrue(Cache::has('header_setting'));

        $this->actingAs($this->admin)
            ->post(route('settings.update'), [
                'types' => [$type],
                $type => '303',
            ])
            ->assertRedirect();

        $this->assertFalse(Cache::has('v1.home.sliders'));
        $this->assertFalse(Cache::has('v1.header_setting'));
        $this->assertFalse(Cache::has('header_setting'));
    }

    public function test_admin_saved_hero_slider_values_are_returned_by_homepage_api(): void
    {
        $this->snapshotSettings([
            'home_slider_1_images',
            'home_slider_1_links',
        ]);

        $upload = $this->createTemporaryImageUpload();

        Cache::put('v1.home.sliders', [
            'one' => [
                [
                    'img' => '/uploads/all/stale-slider.png',
                    'link' => '/stale-slider',
                ],
            ],
        ], 86400);

        $this->actingAs($this->admin)
            ->post(route('settings.update'), [
                'settings_group' => 'hero_slider',
                'types' => [
                    'home_slider_1_images',
                    'home_slider_1_links',
                ],
                'home_slider_1_images' => [(string) $upload->id],
                'home_slider_1_links' => ['/fresh-hero-route'],
            ])
            ->assertRedirect();

        $response = $this->getJson('/api/v1/setting/home/sliders');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.one.0.img', api_asset($upload->id))
            ->assertJsonPath('data.one.0.link', '/fresh-hero-route');
    }

    protected function snapshotSettings(array $types): void
    {
        foreach ($types as $type) {
            if (array_key_exists($type, $this->snapshottedSettings)) {
                continue;
            }

            $this->snapshottedSettings[$type] = DB::table('settings')
                ->where('type', $type)
                ->orderBy('id')
                ->get()
                ->map(fn ($row) => (array) $row)
                ->all();
        }
    }

    protected function restoreSnapshottedSettings(): void
    {
        foreach ($this->snapshottedSettings as $type => $rows) {
            DB::table('settings')->where('type', $type)->delete();

            if ($rows !== []) {
                DB::table('settings')->insert($rows);
            }
        }
    }

    protected function createTemporaryImageUpload(): Upload
    {
        $directory = public_path(UploadStorage::DIRECTORY);

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $fileName = 'test-banner-' . Str::lower(Str::random(12)) . '.png';
        $relativePath = UploadStorage::DIRECTORY . '/' . $fileName;
        $absolutePath = public_path($relativePath);

        file_put_contents(
            $absolutePath,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO3sF4sAAAAASUVORK5CYII=')
        );

        $this->temporaryUploadPaths[] = $absolutePath;

        $upload = Upload::query()->create([
            'file_original_name' => 'test-banner.png',
            'file_name' => $relativePath,
            'user_id' => $this->admin->id,
            'extension' => 'png',
            'type' => 'image',
            'file_size' => filesize($absolutePath),
        ]);

        $this->temporaryUploadIds[] = $upload->id;

        return $upload;
    }
}
