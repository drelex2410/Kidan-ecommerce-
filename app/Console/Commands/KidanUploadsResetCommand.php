<?php

namespace App\Console\Commands;

use App\Services\Uploads\UploadResetService;
use Illuminate\Console\Command;
use RuntimeException;

class KidanUploadsResetCommand extends Command
{
    protected $signature = 'kidan:uploads-reset
        {--scope=banners : Reset scope: banners or all}
        {--confirm : Execute the reset}
        {--dry-run : Preview the reset without changing anything}
        {--backup-dir= : Override the backup destination directory}';

    protected $description = 'Create backups and safely reset banner-only uploads or all upload references.';

    public function handle(UploadResetService $resetService): int
    {
        $scope = (string) $this->option('scope');
        $confirm = (bool) $this->option('confirm');
        $dryRun = (bool) $this->option('dry-run');
        $backupDir = $this->option('backup-dir') ?: null;

        try {
            $result = match ($scope) {
                'banners' => $resetService->runBannerReset($confirm, $dryRun, $backupDir),
                'all' => $resetService->runFullReset($confirm, $dryRun, $backupDir),
                default => throw new RuntimeException('Unsupported scope. Use --scope=banners or --scope=all.'),
            };
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Uploads reset plan complete');
        $this->line('Scope: ' . $result['scope']);
        $this->line('Dry run: ' . ($result['dry_run'] ? 'yes' : 'no'));
        $this->line('Executed: ' . ($result['executed'] ? 'yes' : 'no'));

        if (isset($result['backup']['backup_directory'])) {
            $this->line('Backup directory: ' . $result['backup']['backup_directory']);
        }

        if ($scope === 'banners') {
            $this->line('Banner setting rows: ' . $result['settings_row_count']);
            $this->line('Banner upload IDs: ' . implode(', ', $result['banner_upload_ids']));
            $this->line('Exclusive upload IDs to delete: ' . implode(', ', $result['exclusive_upload_ids']));
            $this->line('Shared upload IDs kept: ' . implode(', ', $result['shared_upload_ids']));
        } else {
            $this->line('Uploads rows: ' . $result['upload_row_count']);
            $this->line('Referenced upload IDs: ' . $result['referenced_upload_id_count']);
            $this->line('Orphan upload IDs: ' . $result['orphan_upload_id_count']);
            $this->line('Broken upload rows: ' . $result['broken_upload_row_count']);
        }

        return self::SUCCESS;
    }
}
