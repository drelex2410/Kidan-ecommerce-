<?php

namespace Tests\Feature;

use App\Services\Uploads\UploadAuditService;
use App\Services\Uploads\UploadResetService;
use Mockery;
use Tests\TestCase;

class UploadCommandsTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_upload_audit_command_reports_missing_files(): void
    {
        $service = Mockery::mock(UploadAuditService::class);
        $service->shouldReceive('generateReport')->once()->andReturn([
            'generated_at' => now()->toDateTimeString(),
            'upload_row_count' => 10,
            'referenced_upload_id_count' => 8,
            'orphan_upload_id_count' => 2,
            'broken_upload_row_count' => 1,
            'broken_reference_count' => 3,
            'table_summaries' => [
                [
                    'table' => 'settings',
                    'column' => 'value',
                    'row_count' => 5,
                    'reference_count' => 4,
                    'missing_upload_row_count' => 1,
                    'missing_file_count' => 2,
                ],
            ],
        ]);

        $this->app->instance(UploadAuditService::class, $service);

        $this->artisan('kidan:uploads-audit')
            ->expectsOutput('KIDAN upload audit complete')
            ->expectsOutput('Uploads rows: 10')
            ->expectsOutput('Broken references: 3')
            ->assertSuccessful();
    }

    public function test_banner_reset_command_supports_dry_run(): void
    {
        $service = Mockery::mock(UploadResetService::class);
        $service->shouldReceive('runBannerReset')
            ->once()
            ->with(false, true, null)
            ->andReturn([
                'scope' => 'banners',
                'dry_run' => true,
                'executed' => false,
                'settings_row_count' => 4,
                'banner_upload_ids' => [1, 2],
                'exclusive_upload_ids' => [1],
                'shared_upload_ids' => [2],
            ]);

        $this->app->instance(UploadResetService::class, $service);

        $this->artisan('kidan:uploads-reset', ['--scope' => 'banners', '--dry-run' => true])
            ->expectsOutput('Uploads reset plan complete')
            ->expectsOutput('Scope: banners')
            ->expectsOutput('Dry run: yes')
            ->assertSuccessful();
    }

    public function test_full_reset_command_requires_confirm_without_dry_run(): void
    {
        $service = Mockery::mock(UploadResetService::class);
        $service->shouldReceive('runFullReset')
            ->once()
            ->with(false, false, null)
            ->andThrow(new \RuntimeException('Full upload reset requires the --confirm flag.'));

        $this->app->instance(UploadResetService::class, $service);

        $this->artisan('kidan:uploads-reset', ['--scope' => 'all'])
            ->expectsOutput('Full upload reset requires the --confirm flag.')
            ->assertFailed();
    }
}
