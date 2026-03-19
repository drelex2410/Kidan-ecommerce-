<?php

namespace Tests\Feature\Api\V1\Shops;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PhaseEightShopDeliveryTest extends TestCase
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

        $this->createSchema();
        $this->seedSettings();
    }

    public function test_all_shops_returns_only_visible_shops(): void
    {
        $visible = $this->seedShopGraph('visible-shop');
        $this->seedShopGraph('hidden-shop', ['verification_status' => 0]);

        $response = $this->getJson('/api/v1/all-shops?page=1');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.slug', 'visible-shop')
            ->assertJsonPath('data.0.id', $visible['shop_id'])
            ->assertJsonCount(1, 'data');
    }

    public function test_shop_by_slug_returns_storefront_payload(): void
    {
        $this->seedShopGraph('shop-detail');

        $this->getJson('/api/v1/shop/shop-detail')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.slug', 'shop-detail')
            ->assertJsonPath('data.categories.data.0.name', 'Fashion');
    }

    public function test_shop_by_slug_returns_404_for_hidden_shop(): void
    {
        $this->seedShopGraph('hidden-shop', ['verification_status' => 0]);

        $this->getJson('/api/v1/shop/hidden-shop')
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_shop_home_returns_featured_and_coupon_sections(): void
    {
        $this->seedShopGraph('shop-home');

        $this->getJson('/api/v1/shop/shop-home/home')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.featured_products.data.0.name', 'Linen Shirt')
            ->assertJsonPath('data.latest_coupons.data.0.code', 'SAVE10');
    }

    public function test_shop_coupons_returns_public_valid_coupons(): void
    {
        $this->seedShopGraph('shop-coupons');

        $this->getJson('/api/v1/shop/shop-coupons/coupons')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.coupons.data.0.code', 'SAVE10');
    }

    public function test_shop_products_returns_catalog_filter_payload(): void
    {
        $this->seedShopGraph('shop-products');

        $response = $this->getJson('/api/v1/shop/shop-products/products?page=1&category_slug=fashion');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('products.data.0.name', 'Linen Shirt')
            ->assertJsonPath('allBrands.data.0.name', 'Acme')
            ->assertJsonPath('rootCategories.data.0.name', 'Fashion')
            ->assertJsonPath('attributes.data.0.name', 'Size');
    }

    public function test_shop_register_creates_pending_shop(): void
    {
        $response = $this->postJson('/api/v1/shop/register', [
            'name' => 'Seller User',
            'phone' => '+2348000001111',
            'email' => 'seller@example.com',
            'password' => 'secret123',
            'confirmPassword' => 'secret123',
            'shopName' => 'Seller Shop',
            'shopPhone' => '08000001111',
            'shopAddress' => '12 Allen Avenue',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Thanks for registering your shop.');

        $this->assertDatabaseHas('users', ['email' => 'seller@example.com', 'user_type' => 'seller']);
        $this->assertDatabaseHas('shops', ['name' => 'Seller Shop', 'verification_status' => 0]);
    }

    public function test_delivery_dashboard_requires_delivery_boy_auth(): void
    {
        $user = $this->createUser(['user_type' => 'customer']);

        $this->withToken($user->createToken('frontend-web')->plainTextToken)
            ->getJson('/api/v1/delivery-boy/dashboard')
            ->assertStatus(403);
    }

    public function test_delivery_dashboard_and_lists_return_expected_payloads(): void
    {
        $deliveryBoy = $this->createUser(['user_type' => 'delivery_boy', 'email_verified_at' => now()]);
        $this->seedDeliveryProfile($deliveryBoy);
        $graph = $this->seedDeliveryOrders($deliveryBoy);
        $token = $deliveryBoy->createToken('frontend-web')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/delivery-boy/dashboard')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_complete_delivery', 1)
            ->assertJsonPath('data.deliveryboy.total_collection', 75.0);

        $this->withToken($token)
            ->getJson('/api/v1/delivery-boy/assigned-deliveries?page=1')
            ->assertOk()
            ->assertJsonPath('data.data.0.id', $graph['assigned_order_id']);

        $this->withToken($token)
            ->getJson('/api/v1/delivery-boy/pending-deliveries?page=1')
            ->assertOk()
            ->assertJsonPath('data.data.0.id', $graph['assigned_order_id']);

        $this->withToken($token)
            ->getJson('/api/v1/delivery-boy/picked-up-deliveries?page=1')
            ->assertOk()
            ->assertJsonPath('data.data.0.id', $graph['picked_up_order_id']);

        $this->withToken($token)
            ->getJson('/api/v1/delivery-boy/on-the-way-deliveries?page=1')
            ->assertOk()
            ->assertJsonPath('data.data.0.id', $graph['on_the_way_order_id']);

        $this->withToken($token)
            ->getJson('/api/v1/delivery-boy/completed-deliveries?page=1')
            ->assertOk()
            ->assertJsonPath('data.data.0.id', $graph['completed_order_id']);

        $this->withToken($token)
            ->getJson('/api/v1/delivery-boy/cancel-deliveries?page=1')
            ->assertOk()
            ->assertJsonPath('data.data.0.id', $graph['cancelled_order_id']);

        $this->withToken($token)
            ->getJson('/api/v1/delivery-boy/collections-list?page=1')
            ->assertOk()
            ->assertJsonPath('data.0.code', 'DELIVERY-COMPLETED');

        $this->withToken($token)
            ->getJson('/api/v1/delivery-boy/earnings-list?page=1')
            ->assertOk()
            ->assertJsonPath('data.0.code', 'DELIVERY-COMPLETED');
    }

    public function test_delivery_status_update_enforces_transitions_and_assignment(): void
    {
        $deliveryBoy = $this->createUser(['user_type' => 'delivery_boy', 'email_verified_at' => now()]);
        $otherDeliveryBoy = $this->createUser(['user_type' => 'delivery_boy', 'email' => 'other-delivery@example.com']);
        $this->seedDeliveryProfile($deliveryBoy);
        $this->seedDeliveryProfile($otherDeliveryBoy);
        $graph = $this->seedDeliveryOrders($deliveryBoy);

        $token = $deliveryBoy->createToken('frontend-web')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/delivery-boy/update-delivery-status', [
                'order_id' => $graph['assigned_order_id'],
                'status' => 'picked_up',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('orders', [
            'id' => $graph['assigned_order_id'],
            'delivery_status' => 'picked_up',
        ]);

        $this->withToken($token)
            ->postJson('/api/v1/delivery-boy/update-delivery-status', [
                'order_id' => $graph['picked_up_order_id'],
                'status' => 'delivered',
            ])
            ->assertStatus(422);

        $this->withToken($otherDeliveryBoy->createToken('frontend-web')->plainTextToken)
            ->postJson('/api/v1/delivery-boy/update-delivery-status', [
                'order_id' => $graph['on_the_way_order_id'],
                'status' => 'delivered',
            ])
            ->assertStatus(404);
    }

    public function test_delivery_cancel_request_sets_cancel_flag(): void
    {
        $deliveryBoy = $this->createUser(['user_type' => 'delivery_boy', 'email_verified_at' => now()]);
        $this->seedDeliveryProfile($deliveryBoy);
        $graph = $this->seedDeliveryOrders($deliveryBoy);

        $this->withToken($deliveryBoy->createToken('frontend-web')->plainTextToken)
            ->postJson('/api/v1/delivery-boy/cancel-request', [
                'order_id' => $graph['on_the_way_order_id'],
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('orders', [
            'id' => $graph['on_the_way_order_id'],
            'cancel_request' => 1,
        ]);
    }

    public function test_delivery_boy_can_open_assigned_order_detail_via_phase_six_endpoint(): void
    {
        $deliveryBoy = $this->createUser(['user_type' => 'delivery_boy', 'email_verified_at' => now()]);
        $this->seedDeliveryProfile($deliveryBoy);
        $graph = $this->seedDeliveryOrders($deliveryBoy);

        $this->withToken($deliveryBoy->createToken('frontend-web')->plainTextToken)
            ->getJson('/api/v1/user/order/DELIVERY-COMPLETED')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code', 'DELIVERY-COMPLETED');
    }

    private function createSchema(): void
    {
        foreach ([
            'uploads',
            'reviews',
            'delivery_histories',
            'delivery_boys',
            'order_updates',
            'order_details',
            'orders',
            'combined_orders',
            'shop_brands',
            'shop_categories',
            'product_attribute_values',
            'attribute_category',
            'product_categories',
            'product_variations',
            'attribute_value_translations',
            'attribute_values',
            'attribute_translations',
            'attributes',
            'brand_translations',
            'brands',
            'coupons',
            'product_taxes',
            'product_translations',
            'products',
            'category_translations',
            'categories',
            'shops',
            'translations',
            'currencies',
            'settings',
            'personal_access_tokens',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable()->unique();
            $table->string('phone')->nullable()->unique();
            $table->string('avatar')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            $table->string('password');
            $table->string('referral_code')->nullable();
            $table->string('user_type')->default('customer');
            $table->boolean('banned')->default(false);
            $table->decimal('balance', 12, 2)->default(0);
            $table->unsignedInteger('club_points')->default(0);
            $table->unsignedBigInteger('shop_id')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('personal_access_tokens', function (Blueprint $table): void {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('type')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('currencies', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('symbol')->default('$');
            $table->decimal('exchange_rate', 12, 6)->default(1);
            $table->timestamps();
        });

        Schema::create('translations', function (Blueprint $table): void {
            $table->id();
            $table->string('lang', 10);
            $table->string('lang_key');
            $table->text('lang_value')->nullable();
            $table->timestamps();
        });

        Schema::create('shops', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo')->nullable();
            $table->text('banners')->nullable();
            $table->text('products_banners')->nullable();
            $table->text('featured_products')->nullable();
            $table->text('banners_1')->nullable();
            $table->text('banners_2')->nullable();
            $table->text('banners_3')->nullable();
            $table->text('banners_4')->nullable();
            $table->decimal('rating', 8, 2)->default(0);
            $table->decimal('min_order', 12, 2)->default(0);
            $table->boolean('published')->default(true);
            $table->boolean('approval')->default(true);
            $table->boolean('verification_status')->default(true);
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->timestamps();
        });

        Schema::create('uploads', function (Blueprint $table): void {
            $table->id();
            $table->string('file_original_name')->nullable();
            $table->string('file_name')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('extension')->nullable();
            $table->string('type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamps();
        });

        Schema::create('reviews', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('shop_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedTinyInteger('rating')->default(5);
            $table->unsignedTinyInteger('status')->default(1);
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
            $table->string('banner')->nullable();
            $table->string('icon')->nullable();
            $table->unsignedBigInteger('parent_id')->default(0);
            $table->unsignedInteger('level')->default(0);
            $table->unsignedInteger('order_level')->default(0);
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

        Schema::create('brands', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
            $table->string('logo')->nullable();
            $table->timestamps();
        });

        Schema::create('brand_translations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('brand_id');
            $table->string('lang', 10);
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('shop_categories', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('shop_id');
            $table->unsignedBigInteger('category_id');
            $table->timestamps();
        });

        Schema::create('shop_brands', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('shop_id');
            $table->unsignedBigInteger('brand_id');
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('shop_id')->nullable();
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->string('name')->nullable();
            $table->string('slug')->unique();
            $table->string('thumbnail_img')->nullable();
            $table->text('photos')->nullable();
            $table->text('tags')->nullable();
            $table->decimal('lowest_price', 12, 2)->default(0);
            $table->decimal('highest_price', 12, 2)->default(0);
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('min_qty')->default(1);
            $table->unsignedInteger('max_qty')->default(10);
            $table->string('unit')->nullable();
            $table->decimal('rating', 8, 2)->default(0);
            $table->decimal('earn_point', 8, 2)->default(0);
            $table->boolean('digital')->default(false);
            $table->boolean('published')->default(true);
            $table->boolean('approved')->default(true);
            $table->boolean('is_variant')->default(true);
            $table->unsignedInteger('num_of_sale')->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->string('discount_type')->default('flat');
            $table->timestamps();
        });

        Schema::create('product_translations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->string('lang', 10);
            $table->string('name');
            $table->string('unit')->nullable();
            $table->timestamps();
        });

        Schema::create('product_taxes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->string('tax_type')->default('flat');
            $table->decimal('tax', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('coupons', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('shop_id');
            $table->string('code');
            $table->string('banner')->nullable();
            $table->integer('start_date');
            $table->integer('end_date');
            $table->timestamps();
        });

        Schema::create('attributes', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('attribute_translations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('attribute_id');
            $table->string('lang', 10);
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('attribute_values', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('attribute_id');
            $table->string('value')->nullable();
            $table->timestamps();
        });

        Schema::create('attribute_value_translations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('attribute_value_id');
            $table->string('lang', 10);
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('attribute_category', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('attribute_id');
            $table->unsignedBigInteger('category_id');
            $table->timestamps();
        });

        Schema::create('product_variations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->string('code')->nullable();
            $table->string('img')->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('current_stock')->default(0);
            $table->timestamps();
        });

        Schema::create('product_categories', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('category_id');
            $table->timestamps();
        });

        Schema::create('product_attribute_values', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('attribute_id');
            $table->unsignedBigInteger('attribute_value_id');
            $table->timestamps();
        });

        Schema::create('combined_orders', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('code');
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->text('shipping_address')->nullable();
            $table->text('billing_address')->nullable();
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('combined_order_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('shop_id')->nullable();
            $table->unsignedBigInteger('assign_delivery_boy')->nullable();
            $table->string('code')->nullable();
            $table->string('payment_type')->nullable();
            $table->string('payment_status')->default('paid');
            $table->string('delivery_status')->default('pending');
            $table->unsignedTinyInteger('cancel_request')->default(0);
            $table->timestamp('cancel_request_at')->nullable();
            $table->timestamp('delivery_history_date')->nullable();
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->decimal('shipping_cost', 12, 2)->default(0);
            $table->decimal('coupon_discount', 12, 2)->default(0);
            $table->string('manual_payment_data')->nullable();
            $table->string('delivery_type')->nullable();
            $table->string('type_of_delivery')->nullable();
            $table->unsignedBigInteger('pickup_point_id')->nullable();
            $table->string('courier_name')->nullable();
            $table->string('tracking_number')->nullable();
            $table->string('tracking_url')->nullable();
            $table->timestamps();
        });

        Schema::create('order_details', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('product_variation_id')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('order_updates', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('delivery_boys', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->decimal('total_collection', 12, 2)->default(0);
            $table->decimal('total_earning', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('delivery_histories', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('delivery_boy_id');
            $table->string('delivery_status')->nullable();
            $table->string('payment_type')->nullable();
            $table->decimal('collection', 12, 2)->default(0);
            $table->decimal('earning', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    private function seedSettings(): void
    {
        DB::table('currencies')->insert([
            'id' => 1,
            'name' => 'US Dollar',
            'symbol' => '$',
            'exchange_rate' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('settings')->insert([
            ['type' => 'system_default_currency', 'value' => '1', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'no_of_decimals', 'value' => '2', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'symbol_format', 'value' => '1', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'delivery_boy_payment_type', 'value' => 'commission', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'delivery_boy_commission', 'value' => '15', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Cache::flush();
    }

    private function createUser(array $attributes = []): User
    {
        static $counter = 1;

        return User::query()->create(array_merge([
            'name' => 'Test User',
            'email' => 'user' . $counter++ . '@example.com',
            'phone' => '+2348000000' . str_pad((string) $counter, 3, '0', STR_PAD_LEFT),
            'password' => Hash::make('secret123'),
            'user_type' => 'customer',
            'banned' => false,
            'balance' => 0,
            'club_points' => 0,
        ], $attributes));
    }

    private function seedShopGraph(string $slug, array $overrides = []): array
    {
        $owner = $this->createUser(['user_type' => 'seller', 'email' => $slug . '@example.com']);

        $shopId = (int) DB::table('shops')->insertGetId(array_merge([
            'user_id' => $owner->id,
            'name' => ucwords(str_replace('-', ' ', $slug)),
            'slug' => $slug,
            'logo' => 'uploads/shop-logo.png',
            'banners' => 'uploads/shop-banner.png',
            'products_banners' => json_encode(['uploads/shop-products-banner.png']),
            'featured_products' => json_encode([1]),
            'banners_1' => json_encode(['images' => ['uploads/banner-1.png'], 'links' => ['https://example.com/1']]),
            'banners_2' => json_encode(['images' => ['uploads/banner-2.png'], 'links' => ['https://example.com/2']]),
            'banners_3' => json_encode(['images' => ['uploads/banner-3.png'], 'links' => ['https://example.com/3']]),
            'banners_4' => json_encode(['images' => ['uploads/banner-4.png'], 'links' => ['https://example.com/4']]),
            'rating' => 4.6,
            'min_order' => 10,
            'published' => 1,
            'approval' => 1,
            'verification_status' => 1,
            'phone' => '08000000000',
            'address' => '12 Allen Avenue',
            'created_at' => now()->subMonth(),
            'updated_at' => now(),
        ], $overrides));

        $categoryId = (int) DB::table('categories')->insertGetId([
            'name' => 'Fashion',
            'slug' => 'fashion',
            'level' => 0,
            'order_level' => 10,
            'featured' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('category_translations')->insert([
            'category_id' => $categoryId,
            'lang' => 'en',
            'name' => 'Fashion',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $brandId = (int) DB::table('brands')->insertGetId([
            'name' => 'Acme',
            'slug' => 'acme',
            'logo' => 'uploads/brand-logo.png',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('brand_translations')->insert([
            'brand_id' => $brandId,
            'lang' => 'en',
            'name' => 'Acme',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('shop_categories')->insert([
            'shop_id' => $shopId,
            'category_id' => $categoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('shop_brands')->insert([
            'shop_id' => $shopId,
            'brand_id' => $brandId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $attributeId = (int) DB::table('attributes')->insertGetId([
            'name' => 'Size',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('attribute_translations')->insert([
            'attribute_id' => $attributeId,
            'lang' => 'en',
            'name' => 'Size',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $attributeValueId = (int) DB::table('attribute_values')->insertGetId([
            'attribute_id' => $attributeId,
            'value' => 'M',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('attribute_value_translations')->insert([
            'attribute_value_id' => $attributeValueId,
            'lang' => 'en',
            'name' => 'M',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('attribute_category')->insert([
            'attribute_id' => $attributeId,
            'category_id' => $categoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $productId = (int) DB::table('products')->insertGetId([
            'shop_id' => $shopId,
            'brand_id' => $brandId,
            'name' => 'Linen Shirt',
            'slug' => 'linen-shirt-' . $slug,
            'thumbnail_img' => 'uploads/product-thumb.png',
            'photos' => 'uploads/product-thumb.png',
            'tags' => 'shirt,fashion',
            'lowest_price' => 40,
            'highest_price' => 40,
            'stock' => 20,
            'min_qty' => 1,
            'max_qty' => 5,
            'unit' => 'pc',
            'rating' => 4.8,
            'earn_point' => 2,
            'published' => 1,
            'approved' => 1,
            'is_variant' => 1,
            'num_of_sale' => 25,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_translations')->insert([
            'product_id' => $productId,
            'lang' => 'en',
            'name' => 'Linen Shirt',
            'unit' => 'pc',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_taxes')->insert([
            'product_id' => $productId,
            'tax_type' => 'flat',
            'tax' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_variations')->insert([
            'product_id' => $productId,
            'code' => 'M',
            'img' => 'uploads/product-thumb.png',
            'stock' => 20,
            'current_stock' => 20,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_categories')->insert([
            'product_id' => $productId,
            'category_id' => $categoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_attribute_values')->insert([
            'product_id' => $productId,
            'attribute_id' => $attributeId,
            'attribute_value_id' => $attributeValueId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('coupons')->insert([
            'shop_id' => $shopId,
            'code' => 'SAVE10',
            'banner' => 'uploads/coupon-banner.png',
            'start_date' => strtotime('-1 day'),
            'end_date' => strtotime('+1 day'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('shops')->where('id', $shopId)->update([
            'featured_products' => json_encode([$productId]),
        ]);

        return [
            'shop_id' => $shopId,
            'product_id' => $productId,
        ];
    }

    private function seedDeliveryProfile(User $user): void
    {
        DB::table('delivery_boys')->insert([
            'user_id' => $user->id,
            'total_collection' => 75,
            'total_earning' => 15,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedDeliveryOrders(User $deliveryBoy): array
    {
        $customer = $this->createUser(['email' => 'customer-' . $deliveryBoy->id . '@example.com']);
        $shopGraph = $this->seedShopGraph('delivery-shop-' . $deliveryBoy->id);

        $orderIds = [];
        foreach ([
            'DELIVERY-ASSIGNED' => 'pending',
            'DELIVERY-PICKED' => 'picked_up',
            'DELIVERY-WAY' => 'on_the_way',
            'DELIVERY-COMPLETED' => 'delivered',
            'DELIVERY-CANCELLED' => 'cancelled',
        ] as $code => $status) {
            $combinedOrderId = (int) DB::table('combined_orders')->insertGetId([
                'user_id' => $customer->id,
                'code' => $code,
                'grand_total' => 75,
                'shipping_address' => json_encode(['address' => '12 Allen Avenue']),
                'billing_address' => json_encode(['address' => '12 Allen Avenue']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $orderId = (int) DB::table('orders')->insertGetId([
                'combined_order_id' => $combinedOrderId,
                'user_id' => $customer->id,
                'shop_id' => $shopGraph['shop_id'],
                'assign_delivery_boy' => $deliveryBoy->id,
                'code' => $code . '-SHOP',
                'payment_type' => 'cash_on_delivery',
                'payment_status' => $status === 'delivered' ? 'paid' : 'unpaid',
                'delivery_status' => $status,
                'cancel_request' => 0,
                'grand_total' => 75,
                'created_at' => now(),
                'updated_at' => now(),
                'delivery_history_date' => now(),
            ]);

            DB::table('order_details')->insert([
                'order_id' => $orderId,
                'product_id' => $shopGraph['product_id'],
                'quantity' => 1,
                'price' => 70,
                'tax' => 5,
                'total' => 75,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $orderIds[$status] = $orderId;

            if ($status === 'delivered') {
                DB::table('delivery_histories')->insert([
                    'order_id' => $orderId,
                    'delivery_boy_id' => $deliveryBoy->id,
                    'delivery_status' => 'delivered',
                    'payment_type' => 'cash_on_delivery',
                    'collection' => 75,
                    'earning' => 15,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return [
            'assigned_order_id' => $orderIds['pending'],
            'picked_up_order_id' => $orderIds['picked_up'],
            'on_the_way_order_id' => $orderIds['on_the_way'],
            'completed_order_id' => $orderIds['delivered'],
            'cancelled_order_id' => $orderIds['cancelled'],
        ];
    }
}
