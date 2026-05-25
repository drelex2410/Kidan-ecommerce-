<?php

namespace App\Services\Uploads;

use App\Models\Upload;
use App\Support\Uploads\UploadInspector;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class UploadAuditService
{
    public function __construct(
        private readonly UploadReferenceRegistry $registry,
        private readonly UploadInspector $inspector,
        private readonly DatabaseManager $db,
    ) {
    }

    public function generateReport(): array
    {
        $uploads = Upload::query()->get()->keyBy(fn (Upload $upload) => (int) $upload->id);
        $references = [];
        $tableSummaries = [];

        foreach ($this->registry->definitions() as $definition) {
            if (!Schema::hasTable($definition['table']) || !Schema::hasColumn($definition['table'], $definition['column'])) {
                continue;
            }

            $result = $this->scanDefinition($definition, $uploads);
            $tableSummaries[] = Arr::except($result, ['references']);
            $references = array_merge($references, $result['references']);
        }

        $referencedIds = collect($references)
            ->pluck('upload_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $missingRows = collect($references)
            ->where('missing_upload_row', true)
            ->count();

        $missingFiles = collect($references)
            ->where('missing_file', true)
            ->count();

        $brokenUploadRows = $uploads->filter(function (Upload $upload) {
            $inspection = $this->inspector->inspectStoredPath(
                (string) $upload->file_name,
                (string) $upload->type,
                (string) $upload->extension
            );

            return !($inspection['exists'] ?? false);
        })->keys()->map(fn ($id) => (int) $id)->values();

        $orphanUploadIds = $uploads->keys()
            ->map(fn ($id) => (int) $id)
            ->diff($referencedIds)
            ->values();

        return [
            'generated_at' => now()->toDateTimeString(),
            'upload_row_count' => $uploads->count(),
            'referenced_upload_id_count' => $referencedIds->count(),
            'orphan_upload_id_count' => $orphanUploadIds->count(),
            'broken_upload_row_count' => $brokenUploadRows->count(),
            'broken_reference_count' => $missingRows + $missingFiles,
            'missing_upload_row_reference_count' => $missingRows,
            'missing_file_reference_count' => $missingFiles,
            'table_summaries' => $tableSummaries,
            'referenced_upload_ids' => $referencedIds->all(),
            'orphan_upload_ids' => $orphanUploadIds->all(),
            'broken_upload_row_ids' => $brokenUploadRows->all(),
            'references' => $references,
        ];
    }

    /**
     * @param  array<string, mixed>  $definition
     * @param  Collection<int, Upload>  $uploads
     * @return array<string, mixed>
     */
    protected function scanDefinition(array $definition, Collection $uploads): array
    {
        $table = (string) $definition['table'];
        $column = (string) $definition['column'];
        $label = (string) ($definition['label'] ?? "{$table}.{$column}");
        $kind = (string) $definition['kind'];
        $primaryKey = Schema::hasColumn($table, 'id') ? 'id' : null;
        $selectColumns = array_values(array_filter([$primaryKey, $column]));
        $rows = $this->db->table($table)->select($selectColumns)->get();
        $references = [];

        if ($kind === 'settings_media') {
            foreach ($this->db->table('settings')
                ->select(['id', 'type', 'value'])
                ->whereIn('type', $this->registry->imageSettingKeys())
                ->get() as $row) {
                foreach ($this->extractSettingValueIds($row->type, $row->value) as $uploadId) {
                    $upload = $uploads->get((int) $uploadId);
                    $references[] = [
                        'table' => $table,
                        'column' => $column,
                        'record_id' => (string) $row->id,
                        'label' => $label . " ({$row->type})",
                        'setting_type' => (string) $row->type,
                        'upload_id' => (int) $uploadId,
                        'missing_upload_row' => !$upload,
                        'missing_file' => $upload ? !$upload->fileExists() : false,
                    ];
                }
            }

            return [
                'table' => $table,
                'column' => $column,
                'label' => $label,
                'kind' => $kind,
                'row_count' => $rows->count(),
                'reference_count' => count($references),
                'missing_upload_row_count' => collect($references)->where('missing_upload_row', true)->count(),
                'missing_file_count' => collect($references)->where('missing_file', true)->count(),
                'references' => $references,
            ];
        }

        foreach ($rows as $row) {
            $recordId = $primaryKey ? (string) data_get($row, $primaryKey) : null;
            $value = data_get($row, $column);

            foreach ($this->extractReferences($kind, $table, $column, $value) as $uploadId) {
                $upload = $uploads->get((int) $uploadId);
                $references[] = [
                    'table' => $table,
                    'column' => $column,
                    'record_id' => $recordId,
                    'label' => $label,
                    'setting_type' => null,
                    'upload_id' => (int) $uploadId,
                    'missing_upload_row' => !$upload,
                    'missing_file' => $upload ? !$upload->fileExists() : false,
                ];
            }
        }

        return [
            'table' => $table,
            'column' => $column,
            'label' => $label,
            'kind' => $kind,
            'row_count' => $rows->count(),
            'reference_count' => count($references),
            'missing_upload_row_count' => collect($references)->where('missing_upload_row', true)->count(),
            'missing_file_count' => collect($references)->where('missing_file', true)->count(),
            'references' => $references,
        ];
    }

    /**
     * @return array<int, int>
     */
    public function extractReferences(string $kind, string $table, string $column, mixed $value): array
    {
        return match ($kind) {
            'scalar' => $this->extractScalarIds($value),
            'csv' => $this->extractCsvIds($value),
            'json_media' => $this->extractJsonMediaIds($value),
            'settings_media' => $this->extractSettingValueIds($table, $column, $value),
            default => [],
        };
    }

    /**
     * @return array<int, int>
     */
    protected function extractScalarIds(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        return is_numeric($value) ? [(int) $value] : [];
    }

    /**
     * @return array<int, int>
     */
    protected function extractCsvIds(mixed $value): array
    {
        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        return collect(explode(',', $value))
            ->map(fn ($item) => trim((string) $item))
            ->filter(fn ($item) => $item !== '' && is_numeric($item))
            ->map(fn ($item) => (int) $item)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, int>
     */
    protected function extractJsonMediaIds(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $decoded = is_array($value) ? $value : json_decode((string) $value, true);

        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_unique($this->extractMediaIdsRecursively($decoded)));
    }

    /**
     * @return array<int, int>
     */
    protected function extractSettingValueIds(string $type, mixed $settingValue): array
    {
        if ($settingValue === null || $settingValue === '') {
            return [];
        }

        if (str_ends_with($type, '_links')) {
            return [];
        }

        if (str_contains($type, '_images')) {
            $decoded = json_decode((string) $settingValue, true);

            return is_array($decoded)
                ? collect($decoded)->filter(fn ($item) => is_numeric($item))->map(fn ($item) => (int) $item)->unique()->values()->all()
                : [];
        }

        return is_numeric($settingValue) ? [(int) $settingValue] : [];
    }

    /**
     * @param  array<mixed>  $payload
     * @return array<int, int>
     */
    protected function extractMediaIdsRecursively(array $payload, ?string $parentKey = null): array
    {
        $ids = [];
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
                if ($normalizedKey && in_array($normalizedKey, ['gallery_images', 'images', 'photos'], true)) {
                    foreach ($item as $listItem) {
                        if (is_numeric($listItem)) {
                            $ids[] = (int) $listItem;
                        }
                    }
                }

                $ids = array_merge($ids, $this->extractMediaIdsRecursively($item, $normalizedKey));
                continue;
            }

            if ($normalizedKey && in_array($normalizedKey, $mediaKeys, true)) {
                if (is_numeric($item)) {
                    $ids[] = (int) $item;
                } elseif (is_string($item)) {
                    $ids = array_merge($ids, $this->extractCsvIds($item));
                }
            } elseif ($parentKey && in_array($parentKey, ['gallery_images', 'images', 'photos'], true) && is_numeric($item)) {
                $ids[] = (int) $item;
            }
        }

        return array_values(array_unique($ids));
    }
}
