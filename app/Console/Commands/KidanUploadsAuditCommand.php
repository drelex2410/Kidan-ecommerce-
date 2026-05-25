<?php

namespace App\Console\Commands;

use App\Services\Uploads\UploadAuditService;
use Illuminate\Console\Command;

class KidanUploadsAuditCommand extends Command
{
    protected $signature = 'kidan:uploads-audit {--json : Output the report as JSON}';

    protected $description = 'Audit upload references, missing files, orphan rows, and homepage banner image integrity.';

    public function handle(UploadAuditService $auditService): int
    {
        $report = $auditService->generateReport();

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->info('KIDAN upload audit complete');
        $this->line('Generated at: ' . $report['generated_at']);
        $this->line('Uploads rows: ' . $report['upload_row_count']);
        $this->line('Referenced upload IDs: ' . $report['referenced_upload_id_count']);
        $this->line('Orphan upload IDs: ' . $report['orphan_upload_id_count']);
        $this->line('Broken upload rows: ' . $report['broken_upload_row_count']);
        $this->line('Broken references: ' . $report['broken_reference_count']);
        $this->newLine();

        $this->table(
            ['Table', 'Column', 'Rows', 'Refs', 'Missing rows', 'Missing files'],
            collect($report['table_summaries'])->map(fn ($item) => [
                $item['table'],
                $item['column'],
                $item['row_count'],
                $item['reference_count'],
                $item['missing_upload_row_count'],
                $item['missing_file_count'],
            ])->all()
        );

        return self::SUCCESS;
    }
}
