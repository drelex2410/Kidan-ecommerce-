<?php

namespace App\Services\Uploads;

use App\Models\Upload;
use App\Support\Uploads\UploadStorage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class UploadRepairService
{
    public function __construct(
        private readonly UploadAuditService $auditService,
        private readonly UploadBackupService $backupService,
    ) {
    }

    public function run(bool $confirm = false, bool $dryRun = false, bool $quarantineOrphans = false): array
    {
        $report = $this->auditService->generateReport();
        $uploads = Upload::query()->get()->keyBy(fn (Upload $upload) => $upload->normalized_file_name);
        $orphanFiles = $this->findOrphanFiles($uploads->keys()->all());

        if ($dryRun || !$confirm) {
            return [
                'executed' => false,
                'dry_run' => true,
                'missing_upload_rows' => $report['missing_upload_row_reference_count'],
                'missing_files' => $report['missing_file_reference_count'],
                'broken_upload_row_ids' => $report['broken_upload_row_ids'],
                'orphan_files' => $orphanFiles,
            ];
        }

        $backup = $this->backupService->createBackups();

        foreach ($report['references'] as $reference) {
            if (!$reference['missing_file']) {
                continue;
            }

            $upload = Upload::query()->find($reference['upload_id']);
            if (!$upload) {
                continue;
            }

            if (Schema::hasColumn('uploads', 'processing_status')) {
                $upload->processing_status = 'failed';
            }

            if (Schema::hasColumn('uploads', 'processing_error')) {
                $upload->processing_error = 'missing_file';
            }

            $upload->save();
        }

        if ($quarantineOrphans && $orphanFiles !== []) {
            $quarantineDirectory = $backup['backup_directory'] . '/orphan-files';
            File::ensureDirectoryExists($quarantineDirectory);

            foreach ($orphanFiles as $orphanFile) {
                $target = $quarantineDirectory . '/' . basename($orphanFile);
                @rename($orphanFile, $target);
            }
        }

        return [
            'executed' => true,
            'dry_run' => false,
            'backup' => $backup,
            'orphan_files' => $orphanFiles,
        ];
    }

    /**
     * @param  array<int, string>  $knownPaths
     * @return array<int, string>
     */
    protected function findOrphanFiles(array $knownPaths): array
    {
        $normalizedKnownPaths = collect($knownPaths)->filter()->values()->all();
        $orphans = [];

        foreach ((array) config('uploads.filesystem_directories', []) as $directory) {
            $absolutePath = base_path($directory);

            if (!is_dir($absolutePath)) {
                continue;
            }

            foreach (File::allFiles($absolutePath) as $file) {
                $relativePath = ltrim(str_replace(base_path() . '/', '', $file->getPathname()), '/');
                $normalized = UploadStorage::normalizePath($relativePath);

                if (!in_array($normalized, $normalizedKnownPaths, true)) {
                    $orphans[] = $file->getPathname();
                }
            }
        }

        return array_values(array_unique($orphans));
    }
}
