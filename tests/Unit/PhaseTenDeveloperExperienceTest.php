<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PhaseTenDeveloperExperienceTest extends TestCase
{
    private string $projectRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectRoot = dirname(__DIR__, 2);
    }

    public function test_readme_points_to_rebuild_docs_and_local_setup(): void
    {
        $readme = file_get_contents($this->projectRoot . '/README.md');

        $this->assertIsString($readme);
        $this->assertStringContainsString('Kidan Ecommerce Rebuild', $readme);
        $this->assertStringContainsString('docs/developer-local-runbook.md', $readme);
        $this->assertStringContainsString('php artisan db:seed', $readme);
        $this->assertStringContainsString('secret123', $readme);
    }

    public function test_runbook_and_api_overview_cover_bootstrap_auth_and_payment_contracts(): void
    {
        $runbook = file_get_contents($this->projectRoot . '/docs/developer-local-runbook.md');
        $overview = file_get_contents($this->projectRoot . '/docs/rebuilt-api-overview.md');

        $this->assertIsString($runbook);
        $this->assertIsString($overview);

        $this->assertStringContainsString('/api/v1/bootstrap', $runbook);
        $this->assertStringContainsString('shopAccessToken', $runbook);
        $this->assertStringContainsString('/payment/{gateway}/pay', $runbook);

        $this->assertStringContainsString('GET /api/v1/user/info', $overview);
        $this->assertStringContainsString('POST /api/v1/checkout/order/store', $overview);
        $this->assertStringContainsString('POST /api/v1/payment/{gateway}/pay', $overview);
    }
}
