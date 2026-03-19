<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class SuperAdminPermissionArchitectureTest extends TestCase
{
    private string $projectRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectRoot = dirname(__DIR__, 2);
    }

    public function test_super_admin_permissions_are_synced_via_migration(): void
    {
        $migration = file_get_contents($this->projectRoot . '/database/migrations/2026_03_16_120000_sync_super_admin_role_permissions.php');

        $this->assertIsString($migration);
        $this->assertStringContainsString("Role::firstOrCreate([", $migration);
        $this->assertStringContainsString("'name' => 'Super Admin'", $migration);
        $this->assertStringContainsString("->where('guard_name', \$guardName)", $migration);
        $this->assertStringContainsString("->pluck('id')", $migration);
        $this->assertStringContainsString("->syncPermissions(\$permissionIds)", $migration);
    }

    public function test_upload_controller_requires_authenticated_sessions_for_mutating_endpoints(): void
    {
        $controller = file_get_contents($this->projectRoot . '/app/Http/Controllers/AizUploadController.php');

        $this->assertIsString($controller);
        $this->assertStringContainsString("\$this->middleware('auth')->except(['attachment_download', 'serve']);", $controller);
    }
}
