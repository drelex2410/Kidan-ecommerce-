<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class HomepageArchitectureTest extends TestCase
{
    private string $projectRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectRoot = dirname(__DIR__, 2);
    }

    public function test_render_path_points_to_the_active_storefront_shell(): void
    {
        $routes = file_get_contents($this->projectRoot . '/routes/web.php');
        $controller = file_get_contents($this->projectRoot . '/app/Http/Controllers/HomeController.php');
        $frontendBlade = file_get_contents($this->projectRoot . '/resources/views/frontend/app.blade.php');
        $appEntry = file_get_contents($this->projectRoot . '/resources/js/app.js');

        $this->assertIsString($routes);
        $this->assertIsString($controller);
        $this->assertIsString($frontendBlade);
        $this->assertIsString($appEntry);

        $this->assertStringContainsString("Route::get('/', [HomeController::class, 'index'])->name('home');", $routes);
        $this->assertStringContainsString("return view('frontend.app', compact('settings', 'meta'));", $controller);
        $this->assertStringContainsString("@vite(['resources/js/app.js'])", $frontendBlade);
        $this->assertStringContainsString('<div id="app"></div>', $frontendBlade);
        $this->assertStringContainsString("import App from './components/App.vue';", $appEntry);
        $this->assertStringContainsString('createApp(App);', $appEntry);
        $this->assertStringContainsString('app.mount("#app");', $appEntry);
    }

    public function test_active_homepage_is_composed_only_through_app_vue(): void
    {
        $appShell = file_get_contents($this->projectRoot . '/resources/js/components/App.vue');
        $legacyHome = file_get_contents($this->projectRoot . '/resources/js/pages/Home.vue');
        $legacyShell = file_get_contents($this->projectRoot . '/resources/js/components/TheShop.vue');

        $this->assertIsString($appShell);
        $this->assertIsString($legacyHome);
        $this->assertIsString($legacyShell);

        $this->assertStringContainsString('v-if="isHomePage && !$route.meta.hideLayout"', $appShell);
        $this->assertStringContainsString('<HomeBannerSectionFour />', $appShell);
        $this->assertStringContainsString('<BlogSlider />', $appShell);
        $this->assertStringContainsString('<HomeAboutText', $appShell);
        $this->assertTrue(
            strpos($appShell, '<HomeBannerSectionFour />') < strpos($appShell, '<BlogSlider />')
            && strpos($appShell, '<BlogSlider />') < strpos($appShell, '<HomeAboutText'),
            'Homepage stories slot should stay between testimonials and the featured editorial section.'
        );
        $this->assertStringContainsString('Legacy routed homepage definition.', $legacyHome);
        $this->assertStringContainsString('Legacy shell retained for reference.', $legacyShell);
    }

    public function test_homepage_blog_contract_stays_on_published_latest_six_posts(): void
    {
        $blogSection = file_get_contents($this->projectRoot . '/resources/js/components/new-design2/BlogSlider.vue');
        $blogController = file_get_contents($this->projectRoot . '/app/Http/Controllers/Api/BlogController.php');

        $this->assertIsString($blogSection);
        $this->assertIsString($blogController);

        $this->assertStringContainsString('const HOME_STORIES_LIMIT = 6;', $blogSection);
        $this->assertStringContainsString('recent-blogs?limit=${HOME_STORIES_LIMIT}', $blogSection);
        $this->assertStringContainsString('all-blogs/search?page=1&per_page=${HOME_STORIES_LIMIT}', $blogSection);
        $this->assertStringContainsString('all-blog-categories', $blogSection);
        $this->assertStringContainsString(".slice(0, HOME_STORIES_LIMIT)", $blogSection);
        $this->assertStringContainsString("return Blog::latest()->where('status', 1);", $blogController);
        $this->assertStringContainsString("public function recent(Request \$request)", $blogController);
        $this->assertStringContainsString("\$blogs->paginate(\$perPage)", $blogController);
    }
}
