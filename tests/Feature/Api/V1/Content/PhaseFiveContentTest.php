<?php

namespace Tests\Feature\Api\V1\Content;

use App\Models\Blog;
use App\Models\Page;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PhaseFiveContentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        DB::reconnect('sqlite');

        $this->createContentSchema();
        $this->seedUploads();
        $this->seedSettings();
    }

    public function test_page_by_slug_returns_page_builder_payload(): void
    {
        $page = $this->seedPage();

        $response = $this->getJson('/api/v1/page/' . $page->slug);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.slug', 'about-us')
            ->assertJsonPath('data.sections.0.type', 'hero')
            ->assertJsonPath('data.sections.0.data.image', route('uploads.file', ['upload' => 1]));
    }

    public function test_page_by_slug_returns_explicit_404(): void
    {
        $this->getJson('/api/v1/page/missing-page')
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_header_settings_endpoint_returns_frontend_ready_payload(): void
    {
        $response = $this->getJson('/api/v1/setting/header');

        $response->assertOk()
            ->assertJsonPath('top_banner.img', route('uploads.file', ['upload' => 1]))
            ->assertJsonPath('mobile_app_links.show_play_store', 'on')
            ->assertJsonPath('header_menu.Shop', '/shops');
    }

    public function test_footer_settings_endpoint_returns_footer_contract(): void
    {
        $response = $this->getJson('/api/v1/setting/footer');

        $response->assertOk()
            ->assertJsonPath('footer_logo', route('uploads.file', ['upload' => 2]))
            ->assertJsonPath('footer_link_one.title', 'Company')
            ->assertJsonPath('footer_menu.Contact', '/contact')
            ->assertJsonPath('mobile_app_links.play_store', 'https://play.example.com');
    }

    public function test_home_sliders_endpoint_returns_keyed_slider_groups(): void
    {
        $response = $this->getJson('/api/v1/setting/home/sliders');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.one.0.img', route('uploads.file', ['upload' => 1]))
            ->assertJsonPath('data.four.0.link', '/promo-4');
    }

    public function test_home_product_section_endpoint_returns_title_and_products(): void
    {
        [$product] = $this->seedCatalogContent();

        $response = $this->getJson('/api/v1/setting/home/product_section_two');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Featured Picks')
            ->assertJsonPath('data.products.data.0.slug', $product->slug);
    }

    public function test_home_popular_categories_endpoint_returns_featured_categories(): void
    {
        $this->seedCatalogContent();

        $response = $this->getJson('/api/v1/setting/home/popular_categories');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.data.0.slug', 'style');
    }

    public function test_home_about_text_endpoint_returns_content_and_youtube_url(): void
    {
        $response = $this->getJson('/api/v1/setting/home/home_about_text');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.youtube_url', 'https://www.youtube.com/watch?v=abc123xyz89');
    }

    public function test_all_blog_categories_endpoint_returns_categories_and_recent_blogs(): void
    {
        [$category, $blog] = $this->seedJournal();

        $response = $this->getJson('/api/v1/all-blog-categories');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.slug', $category->slug)
            ->assertJsonPath('recentBlogs.data.0.slug', $blog->slug);
    }

    public function test_blog_search_endpoint_returns_feed_and_journal_payload(): void
    {
        [, $blog] = $this->seedJournal();

        $response = $this->getJson('/api/v1/all-blogs/search?page=1&per_page=18');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('blogs.data.0.slug', $blog->slug)
            ->assertJsonPath('journal.hero_posts.0.slug', $blog->slug)
            ->assertJsonPath('currentPage', 1);
    }

    public function test_blog_search_supports_category_filter_and_keyword_search(): void
    {
        [$category, $blog] = $this->seedJournal();

        $response = $this->getJson('/api/v1/all-blogs/search?page=1&per_page=12&category_slug=' . $category->slug . '&searchKeyword=journal');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('blogs.data.0.slug', $blog->slug)
            ->assertJsonPath('currentCategory.slug', $category->slug)
            ->assertJsonPath('journal', null);
    }

    public function test_recent_blogs_endpoint_returns_blog_collection(): void
    {
        [, $blog] = $this->seedJournal();

        $response = $this->getJson('/api/v1/recent-blogs?limit=1');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('blogs.data.0.slug', $blog->slug);
    }

    public function test_blog_detail_returns_full_payload(): void
    {
        [, $blog] = $this->seedJournal();

        $response = $this->getJson('/api/v1/blog/details/' . $blog->slug);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.slug', $blog->slug)
            ->assertJsonPath('data.videos.0.video_id', 'abc123xyz89')
            ->assertJsonPath('recentBlogs.data.0.slug', 'journal-story-two');
    }

    public function test_blog_detail_returns_explicit_404(): void
    {
        $this->getJson('/api/v1/blog/details/missing-blog')
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    private function createContentSchema(): void
    {
        foreach ([
            'uploads',
            'settings',
            'page_translations',
            'page_sections',
            'pages',
            'blog_translations',
            'blogs',
            'blog_category_translations',
            'blog_categories',
            'product_categories',
            'category_translations',
            'categories',
            'brand_translations',
            'brands',
            'product_translations',
            'product_taxes',
            'product_variations',
            'products',
            'shop_categories',
            'shops',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('uploads', function (Blueprint $table): void {
            $table->id();
            $table->string('file_original_name')->nullable();
            $table->string('file_name')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('extension')->nullable();
            $table->string('type')->default('image');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('type')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('first_name')->nullable();
            $table->timestamps();
        });

        Schema::create('pages', function (Blueprint $table): void {
            $table->id();
            $table->string('type')->nullable();
            $table->string('title')->nullable();
            $table->string('slug')->unique();
            $table->text('content')->nullable();
            $table->boolean('is_published')->default(true);
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('keywords')->nullable();
            $table->unsignedBigInteger('meta_image')->nullable();
            $table->timestamps();
        });

        Schema::create('page_translations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('page_id');
            $table->string('lang', 10);
            $table->string('title')->nullable();
            $table->text('content')->nullable();
            $table->timestamps();
        });

        Schema::create('page_sections', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('page_id');
            $table->string('section_key')->nullable();
            $table->string('type');
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->text('content')->nullable();
            $table->string('button_text')->nullable();
            $table->string('button_link')->nullable();
            $table->unsignedBigInteger('image')->nullable();
            $table->unsignedBigInteger('image_2')->nullable();
            $table->text('settings_json')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });

        Schema::create('blog_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('slug')->unique();
            $table->string('meta_title')->nullable();
            $table->timestamps();
        });

        Schema::create('blog_category_translations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('blog_category_id');
            $table->string('lang', 10);
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('brands', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
            $table->timestamps();
        });

        Schema::create('brand_translations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('brand_id');
            $table->string('lang', 10);
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('slug')->unique();
            $table->unsignedBigInteger('parent_id')->default(0);
            $table->unsignedInteger('level')->default(0);
            $table->integer('order_level')->default(0);
            $table->unsignedBigInteger('banner')->nullable();
            $table->unsignedBigInteger('icon')->nullable();
            $table->boolean('featured')->default(false);
            $table->timestamps();
        });

        Schema::create('category_translations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('category_id');
            $table->string('lang', 10);
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('product_categories', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('category_id');
            $table->timestamps();
        });

        Schema::create('shops', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedBigInteger('logo')->nullable();
            $table->string('banners')->nullable();
            $table->decimal('rating', 8, 2)->default(0);
            $table->decimal('min_order', 12, 2)->default(0);
            $table->boolean('published')->default(true);
            $table->boolean('approval')->default(true);
            $table->boolean('verification_status')->default(true);
            $table->timestamps();
        });

        Schema::create('shop_categories', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('shop_id');
            $table->unsignedBigInteger('category_id');
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('shop_id')->nullable();
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->string('name')->nullable();
            $table->string('slug')->unique();
            $table->unsignedBigInteger('thumbnail_img')->nullable();
            $table->text('photos')->nullable();
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('lowest_price', 12, 2)->default(0);
            $table->decimal('highest_price', 12, 2)->default(0);
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('min_qty')->default(1);
            $table->unsignedInteger('max_qty')->default(10);
            $table->decimal('rating', 8, 2)->default(0);
            $table->decimal('earn_point', 8, 2)->default(0);
            $table->boolean('is_variant')->default(false);
            $table->boolean('digital')->default(false);
            $table->boolean('published')->default(true);
            $table->boolean('approved')->default(true);
            $table->boolean('today_deal')->default(false);
            $table->unsignedInteger('num_of_sale')->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->string('discount_type')->default('flat');
            $table->unsignedBigInteger('discount_start_date')->nullable();
            $table->unsignedBigInteger('discount_end_date')->nullable();
            $table->timestamps();
        });

        Schema::create('product_translations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->string('lang', 10);
            $table->string('name');
            $table->string('unit')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('product_taxes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->string('tax_type')->default('flat');
            $table->decimal('tax', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('product_variations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->decimal('price', 12, 2)->default(0);
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('current_stock')->default(0);
            $table->timestamps();
        });

        Schema::create('blogs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('author_user_id')->nullable();
            $table->string('title')->nullable();
            $table->string('slug')->unique();
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->unsignedBigInteger('banner')->nullable();
            $table->unsignedBigInteger('editorial_image')->nullable();
            $table->string('meta_title')->nullable();
            $table->unsignedBigInteger('meta_img')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();
            $table->string('hero_button_label')->nullable();
            $table->string('editorial_title')->nullable();
            $table->text('editorial_content')->nullable();
            $table->text('modal_summary')->nullable();
            $table->string('type')->nullable();
            $table->string('product_source_type')->nullable();
            $table->unsignedBigInteger('product_category_id')->nullable();
            $table->unsignedBigInteger('product_brand_id')->nullable();
            $table->unsignedInteger('related_products_limit')->nullable();
            $table->text('related_product_ids')->nullable();
            $table->text('youtube_urls')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('blog_translations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('blog_id');
            $table->string('lang', 10);
            $table->string('title')->nullable();
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->string('hero_button_label')->nullable();
            $table->string('editorial_title')->nullable();
            $table->text('editorial_content')->nullable();
            $table->text('modal_summary')->nullable();
            $table->timestamps();
        });
    }

    private function seedUploads(): void
    {
        foreach ([1, 2, 3, 4, 5, 6] as $id) {
            DB::table('uploads')->insert([
                'id' => $id,
                'file_original_name' => "image-{$id}.jpg",
                'file_name' => "uploads/image-{$id}.jpg",
                'extension' => 'jpg',
                'type' => 'image',
                'file_size' => 100,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedSettings(): void
    {
        $settings = [
            'topbar_banner' => '1',
            'topbar_banner_link' => '/promo',
            'show_topbar_play_store_link' => 'on',
            'topbar_play_store_link' => 'https://play.example.com',
            'show_topbar_app_store_link' => 'off',
            'topbar_app_store_link' => 'https://app.example.com',
            'show_language_switcher' => 'on',
            'topbar_helpline_number' => '+2348000000000',
            'header_menu_labels' => json_encode(['Shop', 'Journal']),
            'header_menu_links' => json_encode(['/shops', '/journal']),
            'current_version' => '1.0.0',
            'footer_logo' => '2',
            'footer_link_one_title' => 'Company',
            'footer_link_one_labels' => json_encode(['About', 'Careers']),
            'footer_link_one_links' => json_encode(['/about', '/careers']),
            'footer_link_two_title' => 'Support',
            'footer_link_two_labels' => json_encode(['FAQ']),
            'footer_link_two_links' => json_encode(['/faq']),
            'contact_address' => '12 Broad Street',
            'contact_email' => 'hello@example.com',
            'contact_phone' => '+2348000000000',
            'play_store_link' => 'https://play.example.com',
            'app_store_link' => 'https://app.example.com',
            'footer_menu_labels' => json_encode(['Contact']),
            'footer_menu_links' => json_encode(['/contact']),
            'frontend_copyright_text' => 'Kidan',
            'footer_social_link' => json_encode(['instagram' => 'https://instagram.com/kidan']),
            'home_slider_1_images' => json_encode([1]),
            'home_slider_1_links' => json_encode(['/promo-1']),
            'home_slider_2_images' => json_encode([2]),
            'home_slider_2_links' => json_encode(['/promo-2']),
            'home_slider_3_images' => json_encode([3]),
            'home_slider_3_links' => json_encode(['/promo-3']),
            'home_slider_4_images' => json_encode([4]),
            'home_slider_4_links' => json_encode(['/promo-4']),
            'home_product_section_2_title' => 'Featured Picks',
            'home_about_us' => '<p>Editorial home intro</p>',
            'home_about_youtube_url' => 'https://www.youtube.com/watch?v=abc123xyz89',
            'meta_title' => 'Kidan Journal',
        ];

        foreach ($settings as $type => $value) {
            DB::table('settings')->insert([
                'type' => $type,
                'value' => $value,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Cache::flush();
    }

    private function seedPage(): Page
    {
        $pageId = DB::table('pages')->insertGetId([
            'type' => 'content_page',
            'title' => 'About Us',
            'slug' => 'about-us',
            'content' => '<p>About page</p>',
            'is_published' => 1,
            'meta_title' => 'About Meta',
            'meta_description' => 'About description',
            'keywords' => 'about',
            'meta_image' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $page = Page::query()->findOrFail($pageId);

        DB::table('page_translations')->insert([
            'page_id' => $page->id,
            'lang' => 'en',
            'title' => 'About Us',
            'content' => '<p>About page</p>',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('page_sections')->insert([
            'page_id' => $page->id,
            'section_key' => 'hero-main',
            'type' => 'hero',
            'title' => 'About Hero',
            'content' => '<p>Hero content</p>',
            'image' => 1,
            'settings_json' => json_encode([
                'heading' => 'About Heading',
                'gallery_images' => '2,3',
                'items' => [['title' => 'Mission', 'description' => 'Mission copy', 'image' => 2]],
            ]),
            'sort_order' => 1,
            'is_visible' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $page->fresh('visibleSections');
    }

    private function seedCatalogContent(): array
    {
        $shopId = DB::table('shops')->insertGetId([
            'name' => 'Content Shop',
            'slug' => 'content-shop',
            'logo' => 1,
            'banners' => '2',
            'rating' => 4.6,
            'min_order' => 0,
            'published' => 1,
            'approval' => 1,
            'verification_status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Style',
            'slug' => 'style',
            'featured' => 1,
            'banner' => 3,
            'icon' => 4,
            'order_level' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('category_translations')->insert([
            'category_id' => $categoryId,
            'lang' => 'en',
            'name' => 'Style',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('shop_categories')->insert([
            'shop_id' => $shopId,
            'category_id' => $categoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $product = DB::table('products')->insertGetId([
            'shop_id' => $shopId,
            'name' => 'Story Shirt',
            'slug' => 'story-shirt',
            'thumbnail_img' => 5,
            'photos' => '5,6',
            'unit_price' => 120,
            'lowest_price' => 120,
            'highest_price' => 120,
            'stock' => 8,
            'min_qty' => 1,
            'max_qty' => 5,
            'rating' => 4.5,
            'earn_point' => 10,
            'is_variant' => 1,
            'published' => 1,
            'approved' => 1,
            'today_deal' => 1,
            'num_of_sale' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_translations')->insert([
            'product_id' => $product,
            'lang' => 'en',
            'name' => 'Story Shirt',
            'unit' => 'pc',
            'description' => '<p>Shirt description</p>',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_taxes')->insert([
            'product_id' => $product,
            'tax_type' => 'flat',
            'tax' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_variations')->insert([
            'product_id' => $product,
            'price' => 120,
            'stock' => 1,
            'current_stock' => 8,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_categories')->insert([
            'product_id' => $product,
            'category_id' => $categoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('settings')->insert([
            'type' => 'home_product_section_2_products',
            'value' => json_encode([$product]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Cache::flush();

        return [DB::table('products')->where('id', $product)->first()];
    }

    private function seedJournal(): array
    {
        $userId = DB::table('users')->insertGetId([
            'name' => 'Editor',
            'first_name' => 'Alex',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $categoryId = DB::table('blog_categories')->insertGetId([
            'name' => 'Journal',
            'slug' => 'journal',
            'meta_title' => 'Journal Meta',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('blog_category_translations')->insert([
            'blog_category_id' => $categoryId,
            'lang' => 'en',
            'name' => 'Journal',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->seedPage();
        DB::table('page_sections')->insert([
            'page_id' => 1,
            'section_key' => 'journal-editorial',
            'type' => 'journal_editorial',
            'title' => 'Editorial Mix',
            'content' => 'Section content',
            'image' => 6,
            'settings_json' => json_encode([
                'youtube_urls' => ['https://www.youtube.com/watch?v=abc123xyz89'],
                'related_products_limit' => 2,
            ]),
            'sort_order' => 2,
            'is_visible' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        [$product] = $this->seedCatalogContent();

        $blogId = DB::table('blogs')->insertGetId([
            'category_id' => $categoryId,
            'author_user_id' => $userId,
            'title' => 'Journal Story',
            'slug' => 'journal-story',
            'short_description' => 'Short journal',
            'description' => '<p>Long journal story</p>',
            'banner' => 1,
            'editorial_image' => 2,
            'meta_title' => 'Journal Story Meta',
            'meta_img' => 3,
            'meta_description' => 'Meta description',
            'meta_keywords' => 'journal',
            'hero_button_label' => 'Read',
            'editorial_title' => 'Hero title',
            'editorial_content' => 'Hero content',
            'modal_summary' => 'Modal summary',
            'type' => 'feature',
            'related_product_ids' => json_encode([$product->id]),
            'youtube_urls' => json_encode(['https://www.youtube.com/watch?v=abc123xyz89']),
            'status' => 1,
            'published_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $blog = Blog::query()->findOrFail($blogId);

        DB::table('blog_translations')->insert([
            'blog_id' => $blog->id,
            'lang' => 'en',
            'title' => 'Journal Story',
            'short_description' => 'Short journal',
            'description' => '<p>Long journal story</p>',
            'hero_button_label' => 'Read',
            'editorial_title' => 'Hero title',
            'editorial_content' => 'Hero content',
            'modal_summary' => 'Modal summary',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $secondaryBlogId = DB::table('blogs')->insertGetId([
            'category_id' => $categoryId,
            'author_user_id' => $userId,
            'title' => 'Journal Story Two',
            'slug' => 'journal-story-two',
            'short_description' => 'Short journal two',
            'description' => '<p>Long journal story two</p>',
            'banner' => 2,
            'status' => 1,
            'published_at' => now()->subDay(),
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        DB::table('blog_translations')->insert([
            'blog_id' => $secondaryBlogId,
            'lang' => 'en',
            'title' => 'Journal Story Two',
            'short_description' => 'Short journal two',
            'description' => '<p>Long journal story two</p>',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [DB::table('blog_categories')->where('id', $categoryId)->first(), $blog];
    }
}
