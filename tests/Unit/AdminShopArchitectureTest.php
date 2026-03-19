<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class AdminShopArchitectureTest extends TestCase
{
    private string $projectRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectRoot = dirname(__DIR__, 2);
    }

    public function test_admin_requests_now_enforce_the_inhouse_shop_invariant(): void
    {
        $service = file_get_contents($this->projectRoot . '/app/Http/Services/AdminShopService.php');
        $middleware = file_get_contents($this->projectRoot . '/app/Http/Middleware/IsAdmin.php');
        $migration = file_get_contents($this->projectRoot . '/database/migrations/2026_03_13_170000_ensure_admin_inhouse_shop_exists.php');

        $this->assertIsString($service);
        $this->assertIsString($middleware);
        $this->assertIsString($migration);

        $this->assertStringContainsString('public function ensureShopForUser(?User $user): ?Shop', $service);
        $this->assertStringContainsString("Shop::where('user_id', \$admin->id)->orderBy('id')->first()", $service);
        $this->assertStringContainsString("\$shop->min_order = 0;", $service);
        $this->assertStringContainsString("if (!\$this->adminShopService->ensureShopForUser(Auth::user())) {", $middleware);
        $this->assertStringContainsString("->whereIn('user_type', ['admin', 'staff'])", $migration);
        $this->assertStringContainsString("\$this->backfillShopAssignments('products', \$shopId);", $migration);
    }

    public function test_shop_settings_screen_uses_an_injected_shop_instead_of_deep_auth_access(): void
    {
        $controller = file_get_contents($this->projectRoot . '/app/Http/Controllers/SettingController.php');
        $view = file_get_contents($this->projectRoot . '/resources/views/backend/settings/general_settings.blade.php');
        $sellerMiddleware = file_get_contents($this->projectRoot . '/app/Http/Middleware/IsSeller.php');
        $userModel = file_get_contents($this->projectRoot . '/app/Models/User.php');

        $this->assertIsString($controller);
        $this->assertIsString($view);
        $this->assertIsString($sellerMiddleware);
        $this->assertIsString($userModel);

        $this->assertStringContainsString("public function general_setting(Request \$request, AdminShopService \$adminShopService)", $controller);
        $this->assertStringContainsString("return view('backend.settings.general_settings', compact('shop'));", $controller);
        $this->assertStringContainsString("'min_order' => ['required', 'numeric', 'min:0']", $controller);
        $this->assertStringContainsString("value=\"{{ old('min_order', optional(\$shop)->min_order ?? 0) }}\"", $view);
        $this->assertStringNotContainsString('auth()->user()->shop->min_order', $view);
        $this->assertStringContainsString("\$shop = Auth::check() ? Auth::user()->shop : null;", $sellerMiddleware);
        $this->assertStringContainsString("return \$this->belongsTo(Shop::class, 'shop_id');", $userModel);
    }
}
