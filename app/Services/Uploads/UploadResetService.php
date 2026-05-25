<?php

namespace App\Services\Uploads;

use App\Models\Upload;
use App\Support\Uploads\UploadStorage;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class UploadResetService
{
    public function __construct(
        private readonly UploadReferenceRegistry $registry,
        private readonly UploadAuditService $auditService,
        private readonly UploadBackupService $backupService,
        private readonly DatabaseManager $db,
    ) {
    }

    public function planBannerReset(): array
    {
        $bannerKeys = $this->registry->bannerSettingKeys();
        $settings = $this->db->table('settings')
            ->select(['id', 'type', 'value'])
            ->whereIn('type', $bannerKeys)
            ->orderBy('type')
            ->orderBy('id')
            ->get();

        $bannerUploadIds = $settings
            ->filter(fn ($row) => str_ends_with((string) $row->type, '_images'))
            ->flatMap(function ($row) {
                $decoded = json_decode((string) $row->value, true);

                return is_array($decoded)
                    ? collect($decoded)->filter(fn ($value) => is_numeric($value))->map(fn ($value) => (int) $value)
                    : [];
            })
            ->unique()
            ->values();

        $report = $this->auditService->generateReport();
        $references = collect($report['references'] ?? []);

        $sharedUploadIds = $bannerUploadIds->filter(function (int $uploadId) use ($references, $bannerKeys) {
            return $references
                ->where('upload_id', $uploadId)
                ->contains(function (array $reference) use ($bannerKeys) {
                    if ($reference['table'] !== 'settings') {
                        return true;
                    }

                    return !in_array((string) ($reference['setting_type'] ?? ''), $bannerKeys, true);
                });
        })->values();

        $exclusiveUploadIds = $bannerUploadIds->diff($sharedUploadIds)->values();

        return [
            'scope' => 'banners',
            'banner_setting_keys' => $bannerKeys,
            'settings_row_count' => $settings->count(),
            'banner_upload_ids' => $bannerUploadIds->all(),
            'exclusive_upload_ids' => $exclusiveUploadIds->all(),
            'shared_upload_ids' => $sharedUploadIds->all(),
        ];
    }

    public function runBannerReset(bool $confirm, bool $dryRun = false, ?string $backupDirectory = null): array
    {
        $plan = $this->planBannerReset();

        if ($dryRun) {
            return $plan + ['executed' => false, 'dry_run' => true];
        }

        if (!$confirm) {
            throw new RuntimeException('Banner reset requires the --confirm flag.');
        }

        $backup = $this->backupService->createBackups($backupDirectory);

        $this->db->transaction(function () use ($plan) {
            $this->db->table('settings')
                ->whereIn('type', $plan['banner_setting_keys'])
                ->update([
                    'value' => null,
                    'updated_at' => now(),
                ]);

            $this->deleteUploadsByIds($plan['exclusive_upload_ids']);
        });

        $this->clearFrontendCaches();

        return $plan + [
            'executed' => true,
            'dry_run' => false,
            'backup' => $backup,
        ];
    }

    public function planFullReset(): array
    {
        $report = $this->auditService->generateReport();

        return [
            'scope' => 'all',
            'upload_row_count' => $report['upload_row_count'],
            'referenced_upload_id_count' => $report['referenced_upload_id_count'],
            'orphan_upload_id_count' => $report['orphan_upload_id_count'],
            'broken_upload_row_count' => $report['broken_upload_row_count'],
            'table_summaries' => $report['table_summaries'],
            'configured_directories' => array_values((array) config('uploads.filesystem_directories', [])),
        ];
    }

    public function runFullReset(bool $confirm, bool $dryRun = false, ?string $backupDirectory = null): array
    {
        $plan = $this->planFullReset();

        if ($dryRun) {
            return $plan + ['executed' => false, 'dry_run' => true];
        }

        if (!$confirm) {
            throw new RuntimeException('Full upload reset requires the --confirm flag.');
        }

        $backup = $this->backupService->createBackups($backupDirectory);

        $this->db->transaction(function () {
            $this->clearImageReferences();
            $this->clearImageSettings();
            $this->deleteUploadsByIds(Upload::query()->pluck('id')->all());
        });

        $this->purgeUploadDirectories();
        $this->clearFrontendCaches();

        return $plan + [
            'executed' => true,
            'dry_run' => false,
            'backup' => $backup,
        ];
    }

    protected function clearImageReferences(): void
    {
        foreach ($this->registry->definitions() as $definition) {
            $table = (string) $definition['table'];
            $column = (string) $definition['column'];
            $kind = (string) $definition['kind'];

            if ($table === 'settings' || !Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
                continue;
            }

            if ($kind === 'scalar') {
                $this->db->table($table)
                    ->whereNotNull($column)
                    ->update([$column => null]);
                continue;
            }

            if ($kind === 'csv') {
                $this->db->table($table)
                    ->whereNotNull($column)
                    ->update([$column => '']);
                continue;
            }

            if ($kind !== 'json_media' || !Schema::hasColumn($table, 'id')) {
                continue;
            }

            $rows = $this->db->table($table)->select(['id', $column])->get();

            foreach ($rows as $row) {
                $cleaned = $this->clearJsonMediaReferences(data_get($row, $column));

                if ($cleaned !== data_get($row, $column)) {
                    $this->db->table($table)
                        ->where('id', $row->id)
                        ->update([$column => $cleaned]);
                }
            }
        }
    }

    protected function clearImageSettings(): void
    {
        $this->db->table('settings')
            ->whereIn('type', $this->registry->imageSettingKeys())
            ->update([
                'value' => null,
                'updated_at' => now(),
            ]);
    }

    protected function clearJsonMediaReferences(mixed $value): mixed
    {
        $decoded = is_array($value) ? $value : json_decode((string) $value, true);

        if (!is_array($decoded)) {
            return $value;
        }

        $cleared = $this->clearMediaArrayRecursively($decoded);

        return json_encode($cleared);
    }

    /**
     * @param  array<mixed>  $payload
     * @return array<mixed>
     */
    protected function clearMediaArrayRecursively(array $payload): array
    {
        $mediaKeys = [
            'image',
            'image_2',
            'badge_image',
            'gallery_images',
            'img',
            'banner',
            'logo',
            'avatar',
            'photo',
            'icon',
            'thumbnail_img',
            'meta_image',
            'meta_img',
        ];

        foreach ($payload as $key => $item) {
            $normalizedKey = is_string($key) ? strtolower($key) : null;

            if (is_array($item)) {
                $payload[$key] = $this->clearMediaArrayRecursively($item);

                if ($normalizedKey && in_array($normalizedKey, ['gallery_images', 'images', 'photos'], true)) {
                    $payload[$key] = [];
                }

                continue;
            }

            if ($normalizedKey && in_array($normalizedKey, $mediaKeys, true)) {
                $payload[$key] = in_array($normalizedKey, ['gallery_images', 'images', 'photos'], true) ? [] : null;
            }
        }

        return $payload;
    }

    /**
     * @param  array<int, int|string>  $uploadIds
     */
    protected function deleteUploadsByIds(array $uploadIds): void
    {
        $uploads = Upload::query()->whereIn('id', $uploadIds)->get();

        foreach ($uploads as $upload) {
            $upload->deleteStoredFile();
            $upload->delete();
        }
    }

    protected function purgeUploadDirectories(): void
    {
        foreach ((array) config('uploads.filesystem_directories', []) as $directory) {
            $absolutePath = base_path($directory);

            if (!is_dir($absolutePath)) {
                continue;
            }

            foreach (File::allFiles($absolutePath) as $file) {
                @unlink($file->getPathname());
            }
        }
    }

    protected function clearFrontendCaches(): void
    {
        Cache::forget('settings');
        Cache::forget('v1.home.sliders');
        Cache::forget('v1.header_setting');
        Cache::forget('header_setting');

        if (function_exists('cache_clear')) {
            cache_clear();
        }
    }
}
