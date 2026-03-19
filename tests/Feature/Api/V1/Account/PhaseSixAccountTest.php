<?php

namespace Tests\Feature\Api\V1\Account;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PhaseSixAccountTest extends TestCase
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

        $this->createAccountSchema();
        $this->seedPricingSettings();
    }

    public function test_orders_list_returns_only_current_users_combined_orders(): void
    {
        $user = $this->createUser(['email_verified_at' => now()]);
        $other = $this->createUser(['email' => 'other@example.com']);
        [$orderCode] = $this->seedOrderGraph($user);
        $this->seedOrderGraph($other, 'OTHER-ORDER');

        $response = $this->withToken($user->createToken('frontend-web')->plainTextToken)
            ->getJson('/api/v1/user/orders?page=1');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.code', $orderCode)
            ->assertJsonCount(1, 'data');
    }

    public function test_orders_require_authentication(): void
    {
        $this->getJson('/api/v1/user/orders')->assertUnauthorized();
    }

    public function test_order_detail_returns_nested_order_contract(): void
    {
        $user = $this->createUser(['email_verified_at' => now()]);
        [$orderCode] = $this->seedOrderGraph($user);

        $response = $this->withToken($user->createToken('frontend-web')->plainTextToken)
            ->getJson('/api/v1/user/order/' . $orderCode);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code', $orderCode)
            ->assertJsonPath('data.orders.0.products.data.0.name', 'Linen Shirt');
    }

    public function test_invoice_download_returns_owner_safe_payload(): void
    {
        $user = $this->createUser(['email_verified_at' => now()]);
        [, $orderId] = $this->seedOrderGraph($user);

        $response = $this->withToken($user->createToken('frontend-web')->plainTextToken)
            ->getJson('/api/v1/order/invoice-download/' . $orderId);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('invoice_name', 'COMBINED-ORDER');

        $this->assertNotEmpty($response->json('invoice_url'));
    }

    public function test_invoice_download_denies_non_owner(): void
    {
        $user = $this->createUser(['email_verified_at' => now()]);
        $other = $this->createUser(['email' => 'other@example.com']);
        [, $orderId] = $this->seedOrderGraph($other);

        $this->withToken($user->createToken('frontend-web')->plainTextToken)
            ->getJson('/api/v1/order/invoice-download/' . $orderId)
            ->assertUnauthorized();
    }

    public function test_downloads_endpoint_returns_paid_digital_products(): void
    {
        $user = $this->createUser(['email_verified_at' => now()]);
        $this->seedDigitalPurchase($user);

        $response = $this->withToken($user->createToken('frontend-web')->plainTextToken)
            ->getJson('/api/v1/user/orders/downloads?page=1');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.name', 'Digital Pattern')
            ->assertJsonPath('meta.current_page', 1);
    }

    public function test_notifications_endpoints_return_unread_and_paginated_notifications(): void
    {
        $user = $this->createUser(['email_verified_at' => now()]);
        $this->seedNotification($user, 'ORDER-1', 'confirmed');
        $this->seedNotification($user, 'ORDER-2', 'shipped', now()->subMinute());

        $token = $user->createToken('frontend-web')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/user/notification')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('notifications.0.data.order_code', 'ORDER-1');

        $response = $this->withToken($token)
            ->getJson('/api/v1/user/all-notification?page=1');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.data.0.data.order_code', 'ORDER-1');

        $this->assertDatabaseMissing('notifications', [
            'notifiable_id' => $user->id,
            'read_at' => null,
        ]);
    }

    public function test_wishlist_list_add_and_remove_are_idempotent(): void
    {
        $user = $this->createUser(['email_verified_at' => now()]);
        [, , $product] = $this->seedCatalog();
        $token = $user->createToken('frontend-web')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/user/wishlists', ['product_id' => $product->id])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('product.id', $product->id);

        $this->withToken($token)
            ->postJson('/api/v1/user/wishlists', ['product_id' => $product->id])
            ->assertOk();

        $this->withToken($token)
            ->getJson('/api/v1/user/wishlists')
            ->assertOk()
            ->assertJsonPath('data.0.id', $product->id);

        $this->withToken($token)
            ->deleteJson('/api/v1/user/wishlists/' . $product->id)
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_follows_list_follow_and_unfollow_work_with_user_info_hydration_model(): void
    {
        $user = $this->createUser(['email_verified_at' => now()]);
        $shopId = $this->seedShop('Followed Shop', 'followed-shop');
        $token = $user->createToken('frontend-web')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/user/follow', ['shop_id' => $shopId])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->withToken($token)
            ->getJson('/api/v1/user/follow')
            ->assertOk()
            ->assertJsonPath('data.0.id', $shopId);

        $this->withToken($token)
            ->deleteJson('/api/v1/user/follow/' . $shopId)
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_profile_update_success_returns_updated_user_shape(): void
    {
        $user = $this->createUser(['email_verified_at' => now()]);

        $response = $this->withToken($user->createToken('frontend-web')->plainTextToken)
            ->postJson('/api/v1/user/info/update', [
                'name' => 'Updated User',
                'password' => 'secret999',
                'confirmPassword' => 'secret999',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('user.name', 'Updated User');

        $this->assertTrue(Hash::check('secret999', $user->fresh()->password));
    }

    public function test_profile_update_validation_failure_is_machine_readable(): void
    {
        $user = $this->createUser(['email_verified_at' => now()]);

        $this->withToken($user->createToken('frontend-web')->plainTextToken)
            ->postJson('/api/v1/user/info/update', [
                'name' => '',
                'password' => '123',
                'confirmPassword' => '999',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'password']);
    }

    public function test_address_crud_and_default_selection_preserve_checkout_compatible_shape(): void
    {
        $user = $this->createUser(['email_verified_at' => now()]);
        $token = $user->createToken('frontend-web')->plainTextToken;
        [$countryId, $stateId, $cityId] = $this->seedLocationTree();

        $create = $this->withToken($token)
            ->postJson('/api/v1/user/address/create', [
                'address' => '12 Allen Avenue',
                'postal_code' => '100001',
                'country' => $countryId,
                'state' => $stateId,
                'city' => $cityId,
                'phone' => '+2348000000000',
            ]);

        $create->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.address', '12 Allen Avenue');

        $addressId = $create->json('data.id');

        $this->withToken($token)
            ->postJson('/api/v1/user/address/update', [
                'id' => $addressId,
                'address' => '14 Allen Avenue',
                'postal_code' => '100002',
                'country' => $countryId,
                'state' => $stateId,
                'city' => $cityId,
                'phone' => '+2348111111111',
            ])
            ->assertOk()
            ->assertJsonPath('data.0.address', '14 Allen Avenue');

        $defaultShippingResponse = $this->withToken($token)
            ->getJson('/api/v1/user/address/default-shipping/' . $addressId);

        $defaultShippingResponse->assertOk();
        $this->assertTrue((bool) $defaultShippingResponse->json('data.0.default_shipping'));

        $defaultBillingResponse = $this->withToken($token)
            ->getJson('/api/v1/user/address/default-billing/' . $addressId);

        $defaultBillingResponse->assertOk();
        $this->assertTrue((bool) $defaultBillingResponse->json('data.0.default_billing'));

        $this->withToken($token)
            ->getJson('/api/v1/user/addresses')
            ->assertOk()
            ->assertJsonPath('data.0.id', $addressId);

        $this->withToken($token)
            ->getJson('/api/v1/user/address/delete/' . $addressId)
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_address_ownership_is_enforced(): void
    {
        $user = $this->createUser(['email_verified_at' => now()]);
        $other = $this->createUser(['email' => 'other@example.com']);
        [$countryId, $stateId, $cityId] = $this->seedLocationTree();

        $addressId = DB::table('addresses')->insertGetId([
            'user_id' => $other->id,
            'address' => 'Other Address',
            'country' => 'Nigeria',
            'country_id' => $countryId,
            'state' => 'Lagos',
            'state_id' => $stateId,
            'city' => 'Ikeja',
            'city_id' => $cityId,
            'postal_code' => '100001',
            'phone' => '+2348000000000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withToken($user->createToken('frontend-web')->plainTextToken)
            ->getJson('/api/v1/user/address/delete/' . $addressId)
            ->assertUnauthorized();
    }

    public function test_dashboard_and_coupons_surfaces_return_frontend_expected_keys(): void
    {
        $user = $this->createUser(['email_verified_at' => now()]);
        $this->seedOrderGraph($user);
        DB::table('wallets')->insert([
            'user_id' => $user->id,
            'amount' => 2500,
            'type' => 'Added',
            'details' => 'Test recharge',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('coupons')->insert([
            'shop_id' => null,
            'code' => 'ACCOUNT10',
            'type' => 'cart_base',
            'discount' => 10,
            'discount_type' => 'percent',
            'details' => json_encode(['min_buy' => 10, 'max_discount' => 10]),
            'start_date' => strtotime('-1 day'),
            'end_date' => strtotime('+1 day'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $token = $user->createToken('frontend-web')->plainTextToken;

        $dashboardResponse = $this->withToken($token)
            ->getJson('/api/v1/user/dashboard');

        $dashboardResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('last_recharge.amount', 2500);

        $this->assertStringContainsString('linen-shirt', (string) $dashboardResponse->json('recent_purchased_products.data.0.slug'));

        $this->withToken($token)
            ->getJson('/api/v1/user/coupons')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.data.0.code', 'ACCOUNT10');
    }

    private function createAccountSchema(): void
    {
        foreach ([
            'notifications',
            'wallets',
            'uploads',
            'coupons',
            'order_details',
            'orders',
            'combined_orders',
            'wishlists',
            'shop_followers',
            'pickup_points',
            'product_variation_combinations',
            'product_variations',
            'attribute_value_translations',
            'attribute_values',
            'attribute_translations',
            'attributes',
            'product_taxes',
            'product_translations',
            'products',
            'shops',
            'addresses',
            'cities',
            'states',
            'countries',
            'currencies',
            'translations',
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
            $table->string('user_type')->default('customer');
            $table->boolean('banned')->default(false);
            $table->decimal('balance', 12, 2)->default(0);
            $table->unsignedInteger('club_points')->default(0);
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

        Schema::create('translations', function (Blueprint $table): void {
            $table->id();
            $table->string('lang', 10);
            $table->string('lang_key');
            $table->text('lang_value')->nullable();
            $table->timestamps();
        });

        Schema::create('currencies', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('symbol')->default('$');
            $table->decimal('exchange_rate', 12, 6)->default(1);
            $table->timestamps();
        });

        Schema::create('countries', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('states', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('country_id');
            $table->string('name');
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('cities', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('state_id')->nullable();
            $table->string('name');
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('addresses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('country_id')->nullable();
            $table->unsignedBigInteger('state_id')->nullable();
            $table->unsignedBigInteger('city_id')->nullable();
            $table->string('address')->nullable();
            $table->string('country')->nullable();
            $table->string('state')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('set_default')->default(false);
            $table->boolean('default_shipping')->default(false);
            $table->boolean('default_billing')->default(false);
            $table->timestamps();
        });

        Schema::create('shops', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->string('logo')->nullable();
            $table->string('banners')->nullable();
            $table->decimal('rating', 8, 2)->default(0);
            $table->decimal('min_order', 12, 2)->default(0);
            $table->boolean('published')->default(true);
            $table->boolean('approval')->default(true);
            $table->boolean('verification_status')->default(true);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('shop_id')->nullable();
            $table->string('name')->nullable();
            $table->string('slug')->unique();
            $table->string('thumbnail_img')->nullable();
            $table->text('photos')->nullable();
            $table->unsignedBigInteger('file_name')->nullable();
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

        Schema::create('product_variations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->string('code')->nullable();
            $table->string('img')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('current_stock')->default(0);
            $table->timestamps();
        });

        Schema::create('product_variation_combinations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('product_variation_id');
            $table->unsignedBigInteger('attribute_id');
            $table->unsignedBigInteger('attribute_value_id');
            $table->timestamps();
        });

        Schema::create('pickup_points', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->string('location')->nullable();
            $table->timestamps();
        });

        Schema::create('shop_followers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('shop_id');
            $table->timestamps();
        });

        Schema::create('wishlists', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('product_id');
            $table->timestamps();
        });

        Schema::create('combined_orders', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('code');
            $table->text('shipping_address')->nullable();
            $table->text('billing_address')->nullable();
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('shop_id');
            $table->unsignedBigInteger('combined_order_id');
            $table->string('code')->nullable();
            $table->text('shipping_address')->nullable();
            $table->text('billing_address')->nullable();
            $table->decimal('shipping_cost', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->string('coupon_code')->nullable();
            $table->decimal('coupon_discount', 12, 2)->default(0);
            $table->string('delivery_type')->nullable();
            $table->string('payment_type')->nullable();
            $table->string('type_of_delivery')->nullable();
            $table->unsignedBigInteger('pickup_point_id')->nullable();
            $table->string('payment_status')->nullable();
            $table->string('delivery_status')->nullable();
            $table->boolean('manual_payment')->default(false);
            $table->text('manual_payment_data')->nullable();
            $table->string('courier_name')->nullable();
            $table->string('tracking_number')->nullable();
            $table->string('tracking_url')->nullable();
            $table->timestamps();
        });

        Schema::create('order_details', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('product_variation_id');
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->unsignedInteger('quantity')->default(1);
            $table->string('product_referral_code')->nullable();
            $table->timestamps();
        });

        Schema::create('coupons', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('shop_id')->nullable();
            $table->string('code')->unique();
            $table->string('type');
            $table->decimal('discount', 12, 2)->default(0);
            $table->string('discount_type')->default('flat');
            $table->text('details')->nullable();
            $table->unsignedBigInteger('start_date');
            $table->unsignedBigInteger('end_date');
            $table->string('banner')->nullable();
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

        Schema::create('wallets', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('type')->nullable();
            $table->text('details')->nullable();
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    private function seedPricingSettings(): void
    {
        DB::table('currencies')->insert([
            'id' => 1,
            'name' => 'Dollar',
            'symbol' => '$',
            'exchange_rate' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('settings')->insert([
            ['type' => 'decimal_separator', 'value' => '1', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'no_of_decimals', 'value' => '2', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'symbol_format', 'value' => '1', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'system_default_currency', 'value' => '1', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Cache::flush();
    }

    private function createUser(array $attributes = []): User
    {
        static $counter = 1;

        $email = $attributes['email'] ?? 'user' . $counter++ . '@example.com';

        return User::query()->create(array_merge([
            'name' => 'Test User',
            'email' => $email,
            'password' => Hash::make('secret123'),
            'user_type' => 'customer',
            'banned' => false,
            'balance' => 0,
            'club_points' => 0,
        ], $attributes));
    }

    private function seedLocationTree(): array
    {
        $countryId = DB::table('countries')->insertGetId([
            'name' => 'Nigeria',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $stateId = DB::table('states')->insertGetId([
            'country_id' => $countryId,
            'name' => 'Lagos',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $cityId = DB::table('cities')->insertGetId([
            'state_id' => $stateId,
            'name' => 'Ikeja',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$countryId, $stateId, $cityId];
    }

    private function seedShop(string $name = 'Kidan Shop', string $slug = 'kidan-shop'): int
    {
        return DB::table('shops')->insertGetId([
            'name' => $name,
            'slug' => $slug,
            'published' => 1,
            'approval' => 1,
            'verification_status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedCatalog(bool $digital = false): array
    {
        static $catalogCounter = 1;

        $shopId = $this->seedShop();
        $slug = ($digital ? 'digital-pattern' : 'linen-shirt') . '-' . $catalogCounter++;

        $productId = DB::table('products')->insertGetId([
            'shop_id' => $shopId,
            'name' => $digital ? 'Digital Pattern' : 'Linen Shirt',
            'slug' => $slug,
            'thumbnail_img' => null,
            'photos' => '',
            'lowest_price' => 100,
            'highest_price' => 100,
            'stock' => 10,
            'min_qty' => 1,
            'max_qty' => 5,
            'unit' => 'pc',
            'rating' => 4.5,
            'earn_point' => 5,
            'digital' => $digital ? 1 : 0,
            'published' => 1,
            'approved' => 1,
            'is_variant' => 1,
            'discount' => 0,
            'discount_type' => 'flat',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_translations')->insert([
            'product_id' => $productId,
            'lang' => 'en',
            'name' => $digital ? 'Digital Pattern' : 'Linen Shirt',
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

        $variationId = DB::table('product_variations')->insertGetId([
            'product_id' => $productId,
            'code' => 'SIZE/L',
            'price' => 100,
            'stock' => 10,
            'current_stock' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$shopId, $variationId, DB::table('products')->where('id', $productId)->first()];
    }

    private function seedOrderGraph(User $user, string $combinedCode = 'COMBINED-ORDER'): array
    {
        [$countryId, $stateId, $cityId] = $this->seedLocationTree();
        [$shopId, $variationId, $product] = $this->seedCatalog();

        $address = [
            'address' => '12 Allen Avenue',
            'country' => 'Nigeria',
            'country_id' => $countryId,
            'state' => 'Lagos',
            'state_id' => $stateId,
            'city' => 'Ikeja',
            'city_id' => $cityId,
            'postal_code' => '100001',
            'phone' => '+2348000000000',
        ];

        $combinedOrderId = DB::table('combined_orders')->insertGetId([
            'user_id' => $user->id,
            'code' => $combinedCode,
            'shipping_address' => json_encode($address),
            'billing_address' => json_encode($address),
            'grand_total' => 105,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $orderId = DB::table('orders')->insertGetId([
            'user_id' => $user->id,
            'shop_id' => $shopId,
            'combined_order_id' => $combinedOrderId,
            'code' => '1',
            'shipping_address' => json_encode($address),
            'billing_address' => json_encode($address),
            'shipping_cost' => 0,
            'grand_total' => 105,
            'payment_type' => 'cash_on_delivery',
            'payment_status' => 'unpaid',
            'delivery_type' => 'standard',
            'type_of_delivery' => 'home_delivery',
            'delivery_status' => 'order_placed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('order_details')->insert([
            'order_id' => $orderId,
            'product_id' => $product->id,
            'product_variation_id' => $variationId,
            'price' => 100,
            'tax' => 5,
            'total' => 105,
            'quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$combinedCode, $orderId];
    }

    private function seedDigitalPurchase(User $user): void
    {
        [$shopId, $variationId, $product] = $this->seedCatalog(true);

        $uploadId = DB::table('uploads')->insertGetId([
            'file_original_name' => 'pattern',
            'file_name' => 'downloads/pattern.pdf',
            'extension' => 'pdf',
            'type' => 'document',
            'file_size' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('products')->where('id', $product->id)->update(['file_name' => $uploadId]);

        $combinedOrderId = DB::table('combined_orders')->insertGetId([
            'user_id' => $user->id,
            'code' => 'DOWNLOAD-ORDER',
            'grand_total' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $orderId = DB::table('orders')->insertGetId([
            'user_id' => $user->id,
            'shop_id' => $shopId,
            'combined_order_id' => $combinedOrderId,
            'code' => '1',
            'grand_total' => 100,
            'payment_type' => 'wallet',
            'payment_status' => 'paid',
            'delivery_status' => 'delivered',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('order_details')->insert([
            'order_id' => $orderId,
            'product_id' => $product->id,
            'product_variation_id' => $variationId,
            'price' => 100,
            'tax' => 0,
            'total' => 100,
            'quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedNotification(User $user, string $orderCode, string $status, $createdAt = null): void
    {
        DB::table('notifications')->insert([
            'id' => (string) str()->uuid(),
            'type' => 'App\\Notifications\\OrderNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode([
                'order_code' => $orderCode,
                'status' => $status,
            ]),
            'created_at' => $createdAt ?? now(),
            'updated_at' => $createdAt ?? now(),
        ]);
    }
}
