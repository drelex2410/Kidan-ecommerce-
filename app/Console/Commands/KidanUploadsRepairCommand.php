<?php

namespace App\Console\Commands;

use App\Services\Uploads\UploadRepairService;
use Illuminate\Console\Command;

class KidanUploadsRepairCommand extends Command
{
    protected $signature = 'kidan:uploads-repair
        {--confirm : Apply repair actions}
        {--dry-run : Preview repairs only}
        {--quarantine-orphans : Move orphan files into the backup folder when confirming}';

    protected $description = 'Report and optionally repair missing upload files, broken upload rows, and orphan files on disk.';

    public function handle(UploadRepairService $repairService): int
    {
        $result = $repairService->run(
            confirm: (bool) $this->option('confirm'),
            dryRun: (bool) $this->option('dry-run'),
            quarantineOrphans: (bool) $this->option('quarantine-orphans'),
        );

        $this->info('Uploads repair report complete');
        $this->line('Executed: ' . ($result['executed'] ? 'yes' : 'no'));
        $this->line('Dry run: ' . ($result['dry_run'] ? 'yes' : 'no'));
        $this->line('Missing upload row references: ' . ($result['missing_upload_rows'] ?? 0));
        $this->line('Missing files: ' . ($result['missing_files'] ?? 0));
        $this->line('Broken upload row IDs: ' . implode(', ', $result['broken_upload_row_ids'] ?? []));
        $this->line('Orphan files: ' . count($result['orphan_files'] ?? []));

        if (isset($result['backup']['backup_directory'])) {
            $this->line('Backup directory: ' . $result['backup']['backup_directory']);
        }

        return self::SUCCESS;
    }
}
