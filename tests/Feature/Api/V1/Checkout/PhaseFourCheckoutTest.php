<?php

namespace Tests\Feature\Api\V1\Checkout;

use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PhaseFourCheckoutTest extends TestCase
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

        $this->createCheckoutSchema();
        $this->seedPricingSettings();
    }

    public function test_guest_cart_retrieval_returns_cart_items_and_shops(): void
    {
        [$variation] = $this->seedCatalogForCheckout();
        $this->seedCartLine(['temp_user_id' => 'guest-123', 'product_variation_id' => $variation->id]);

        $response = $this->postJson('/api/v1/carts', [
            'temp_user_id' => 'guest-123',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('cart_items.data.0.variation_id', $variation->id)
            ->assertJsonPath('shops.data.0.slug', 'kidan-shop');
    }

    public function test_authenticated_cart_retrieval_returns_user_owned_lines(): void
    {
        $user = $this->createUser();
        [$variation] = $this->seedCatalogForCheckout();
        $this->seedCartLine(['user_id' => $user->id, 'temp_user_id' => null, 'product_variation_id' => $variation->id]);

        $response = $this->withToken($user->createToken('frontend-web')->plainTextToken)
            ->postJson('/api/v1/carts');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('cart_items.data.0.qty', 2)
            ->assertJsonPath('summary.item_count', 1);
    }

    public function test_add_to_cart_success_returns_updated_contract(): void
    {
        [$variation] = $this->seedCatalogForCheckout();

        $response = $this->postJson('/api/v1/carts/add', [
            'variation_id' => $variation->id,
            'qty' => 2,
            'temp_user_id' => 'guest-123',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.variation_id', $variation->id)
            ->assertJsonPath('data.qty', 2)
            ->assertJsonPath('shop.slug', 'kidan-shop');
    }

    public function test_add_to_cart_invalid_variation_returns_not_found(): void
    {
        $this->postJson('/api/v1/carts/add', [
            'variation_id' => 99999,
            'qty' => 1,
            'temp_user_id' => 'guest-123',
        ])->assertStatus(404);
    }

    public function test_add_to_cart_respects_max_stock_constraints(): void
    {
        [$variation] = $this->seedCatalogForCheckout([
            'product' => ['max_qty' => 2],
            'variation' => ['current_stock' => 2],
        ]);

        $response = $this->postJson('/api/v1/carts/add', [
            'variation_id' => $variation->id,
            'qty' => 3,
            'temp_user_id' => 'guest-123',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Max quantity reached');
    }

    public function test_change_quantity_updates_cart_line(): void
    {
        [$variation] = $this->seedCatalogForCheckout();
        $cartId = $this->seedCartLine(['temp_user_id' => 'guest-123', 'product_variation_id' => $variation->id]);

        $response = $this->postJson('/api/v1/carts/change-quantity', [
            'cart_id' => $cartId,
            'type' => 'plus',
            'temp_user_id' => 'guest-123',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Cart updated');

        $this->assertSame(3, (int) DB::table('carts')->where('id', $cartId)->value('quantity'));
    }

    public function test_change_quantity_rejects_invalid_quantity(): void
    {
        [$variation] = $this->seedCatalogForCheckout([
            'product' => ['max_qty' => 2],
        ]);
        $cartId = $this->seedCartLine([
            'temp_user_id' => 'guest-123',
            'product_variation_id' => $variation->id,
            'quantity' => 2,
        ]);

        $response = $this->postJson('/api/v1/carts/change-quantity', [
            'cart_id' => $cartId,
            'type' => 'plus',
            'temp_user_id' => 'guest-123',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Max quantity reached');
    }

    public function test_destroy_cart_item_removes_line(): void
    {
        [$variation] = $this->seedCatalogForCheckout();
        $cartId = $this->seedCartLine(['temp_user_id' => 'guest-123', 'product_variation_id' => $variation->id]);

        $response = $this->postJson('/api/v1/carts/destroy', [
            'cart_id' => $cartId,
            'temp_user_id' => 'guest-123',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('carts', ['id' => $cartId]);
    }

    public function test_temp_cart_merge_moves_guest_lines_to_authenticated_user(): void
    {
        $user = $this->createUser();
        [$variation] = $this->seedCatalogForCheckout();
        $this->seedCartLine(['temp_user_id' => 'guest-123', 'product_variation_id' => $variation->id]);

        $response = $this->withToken($user->createToken('frontend-web')->plainTextToken)
            ->postJson('/api/v1/temp-id-cart-update', [
                'temp_user_id' => 'guest-123',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('cart_items.data.0.variation_id', $variation->id);

        $this->assertDatabaseHas('carts', [
            'user_id' => $user->id,
            'temp_user_id' => null,
        ]);
    }

    public function test_coupon_apply_success_returns_coupon_details_and_totals(): void
    {
        $user = $this->createUser();
        [$variation, $product, $shopId] = $this->seedCatalogForCheckout();
        $cartId = $this->seedCartLine(['user_id' => $user->id, 'temp_user_id' => null, 'product_variation_id' => $variation->id]);

        DB::table('coupons')->insert([
            'id' => 1,
            'shop_id' => $shopId,
            'code' => 'SAVE10',
            'type' => 'cart_base',
            'discount' => 10,
            'discount_type' => 'percent',
            'details' => json_encode(['min_buy' => 50, 'max_discount' => 20]),
            'start_date' => strtotime('-1 day'),
            'end_date' => strtotime('+1 day'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withToken($user->createToken('frontend-web')->plainTextToken)
            ->postJson('/api/v1/checkout/coupon/apply', [
                'coupon_code' => 'SAVE10',
                'shop_id' => $shopId,
                'cart_item_ids' => [$cartId],
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('coupon_details.coupon_type', 'cart_base')
            ->assertJsonPath('discount_amount', 20);
    }

    public function test_coupon_apply_invalid_or_expired_returns_explicit_failure(): void
    {
        $user = $this->createUser();
        [$variation] = $this->seedCatalogForCheckout();
        $cartId = $this->seedCartLine(['user_id' => $user->id, 'temp_user_id' => null, 'product_variation_id' => $variation->id]);

        DB::table('coupons')->insert([
            'id' => 2,
            'shop_id' => 1,
            'code' => 'OLD',
            'type' => 'cart_base',
            'discount' => 5,
            'discount_type' => 'amount',
            'details' => json_encode(['min_buy' => 10, 'max_discount' => 5]),
            'start_date' => strtotime('-10 day'),
            'end_date' => strtotime('-1 day'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withToken($user->createToken('frontend-web')->plainTextToken)
            ->postJson('/api/v1/checkout/coupon/apply', [
                'coupon_code' => 'OLD',
                'shop_id' => 1,
                'cart_item_ids' => [$cartId],
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'The coupon is invalid.');
    }

    public function test_shipping_cost_resolution_returns_zone_rates(): void
    {
        $user = $this->createUser();
        [$variation] = $this->seedCatalogForCheckout();
        $this->seedCartLine(['user_id' => $user->id, 'temp_user_id' => null, 'product_variation_id' => $variation->id]);
        $addressId = $this->seedAddress($user->id, 1);

        $response = $this->withToken($user->createToken('frontend-web')->plainTextToken)
            ->getJson('/api/v1/checkout/get-shipping-cost/' . $addressId);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('standard_delivery_cost', 12.5)
            ->assertJsonPath('express_delivery_cost', 20);
    }

    public function test_shipping_cost_rejects_other_users_address(): void
    {
        $user = $this->createUser();
        $other = $this->createUser(['email' => 'other@example.com']);
        [$variation] = $this->seedCatalogForCheckout();
        $this->seedCartLine(['user_id' => $user->id, 'temp_user_id' => null, 'product_variation_id' => $variation->id]);
        $addressId = $this->seedAddress($other->id, 1);

        $response = $this->withToken($user->createToken('frontend-web')->plainTextToken)
            ->getJson('/api/v1/checkout/get-shipping-cost/' . $addressId);

        $response->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_order_store_success_creates_order_and_returns_payment_handoff_contract(): void
    {
        $user = $this->createUser(['email_verified_at' => now()]);
        [$variation, $product, $shopId] = $this->seedCatalogForCheckout();
        $cartId = $this->seedCartLine(['user_id' => $user->id, 'temp_user_id' => null, 'product_variation_id' => $variation->id]);
        $shippingAddressId = $this->seedAddress($user->id, 1);
        $billingAddressId = $this->seedAddress($user->id, 1, ['default_billing' => 1]);

        DB::table('coupons')->insert([
            'id' => 3,
            'shop_id' => $shopId,
            'code' => 'SHOP5',
            'type' => 'cart_base',
            'discount' => 5,
            'discount_type' => 'amount',
            'details' => json_encode(['min_buy' => 10, 'max_discount' => 5]),
            'start_date' => strtotime('-1 day'),
            'end_date' => strtotime('+1 day'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withToken($user->createToken('frontend-web')->plainTextToken)
            ->postJson('/api/v1/checkout/order/store', [
                'shipping_address_id' => $shippingAddressId,
                'billing_address_id' => $billingAddressId,
                'payment_type' => 'cash_on_delivery',
                'delivery_type' => 'standard',
                'type_of_delivery' => 'home_delivery',
                'pickup_point_id' => null,
                'cart_item_ids' => [$cartId],
                'coupon_codes' => ['SHOP5'],
                'transactionId' => null,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('go_to_payment', false)
            ->assertJsonPath('payment_method', 'cash_on_delivery');

        $this->assertDatabaseCount('combined_orders', 1);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_details', 1);
        $this->assertDatabaseMissing('carts', ['id' => $cartId]);
    }

    public function test_order_store_rejects_empty_cart(): void
    {
        $user = $this->createUser();
        $shippingAddressId = $this->seedAddress($user->id, 1);

        $response = $this->withToken($user->createToken('frontend-web')->plainTextToken)
            ->postJson('/api/v1/checkout/order/store', [
                'shipping_address_id' => $shippingAddressId,
                'billing_address_id' => $shippingAddressId,
                'payment_type' => 'cash_on_delivery',
                'delivery_type' => 'standard',
                'type_of_delivery' => 'home_delivery',
                'cart_item_ids' => [999],
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Your cart is empty. Please select a product.');
    }

    public function test_order_store_invalid_payload_returns_validation_errors(): void
    {
        $user = $this->createUser();

        $this->withToken($user->createToken('frontend-web')->plainTextToken)
            ->postJson('/api/v1/checkout/order/store', [
                'payment_type' => '',
                'cart_item_ids' => [],
            ])->assertStatus(422)
            ->assertJsonValidationErrors(['payment_type', 'type_of_delivery', 'cart_item_ids']);
    }

    private function createCheckoutSchema(): void
    {
        foreach ([
            'coupon_usages',
            'coupons',
            'order_details',
            'orders',
            'combined_orders',
            'carts',
            'product_variation_combinations',
            'product_variations',
            'attribute_value_translations',
            'attribute_translations',
            'attribute_values',
            'attributes',
            'product_taxes',
            'product_translations',
            'products',
            'shops',
            'addresses',
            'cities',
            'zones',
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

        Schema::create('currencies', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('symbol')->default('$');
            $table->decimal('exchange_rate', 12, 6)->default(1);
            $table->timestamps();
        });

        Schema::create('zones', function (Blueprint $table): void {
            $table->id();
            $table->decimal('standard_delivery_cost', 12, 2)->default(0);
            $table->decimal('express_delivery_cost', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('cities', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('zone_id')->nullable();
            $table->string('name')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('addresses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('city_id')->nullable();
            $table->string('address')->nullable();
            $table->string('country')->nullable();
            $table->unsignedBigInteger('country_id')->nullable();
            $table->string('state')->nullable();
            $table->unsignedBigInteger('state_id')->nullable();
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
            $table->decimal('commission', 8, 2)->default(0);
            $table->boolean('published')->default(true);
            $table->boolean('approval')->default(true);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('shop_id')->nullable();
            $table->string('name')->nullable();
            $table->string('slug')->unique();
            $table->string('thumbnail_img')->nullable();
            $table->decimal('lowest_price', 12, 2)->default(0);
            $table->decimal('highest_price', 12, 2)->default(0);
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('min_qty')->default(1);
            $table->unsignedInteger('max_qty')->default(10);
            $table->decimal('earn_point', 8, 2)->default(0);
            $table->boolean('digital')->default(false);
            $table->boolean('published')->default(true);
            $table->boolean('approved')->default(true);
            $table->boolean('for_pickup')->default(false);
            $table->unsignedInteger('standard_delivery_time')->default(2);
            $table->unsignedInteger('express_delivery_time')->default(1);
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

        Schema::create('carts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('temp_user_id')->nullable()->index();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('product_variation_id');
            $table->unsignedInteger('quantity')->default(1);
            $table->string('product_referral_code')->nullable();
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
            $table->timestamps();
        });

        Schema::create('coupon_usages', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('coupon_id');
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
        return User::query()->create(array_merge([
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => Hash::make('secret123'),
            'user_type' => 'customer',
            'banned' => false,
            'balance' => 0,
            'club_points' => 0,
        ], $attributes));
    }

    private function seedCatalogForCheckout(array $overrides = []): array
    {
        $shopId = DB::table('shops')->insertGetId([
            'name' => 'Kidan Shop',
            'slug' => 'kidan-shop',
            'min_order' => 0,
            'commission' => 0,
            'published' => 1,
            'approval' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $product = Product::query()->create(array_merge([
            'shop_id' => $shopId,
            'name' => 'Linen Shirt',
            'slug' => 'linen-shirt-' . Product::query()->count(),
            'lowest_price' => 100,
            'highest_price' => 100,
            'stock' => 10,
            'min_qty' => 1,
            'max_qty' => 5,
            'earn_point' => 5,
            'published' => 1,
            'approved' => 1,
            'digital' => 0,
            'for_pickup' => 1,
            'discount' => 0,
            'discount_type' => 'flat',
            'standard_delivery_time' => 2,
            'express_delivery_time' => 1,
        ], $overrides['product'] ?? []));

        DB::table('product_translations')->insert([
            'product_id' => $product->id,
            'lang' => 'en',
            'name' => 'Linen Shirt',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_taxes')->insert([
            'product_id' => $product->id,
            'tax_type' => 'flat',
            'tax' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $attributeId = DB::table('attributes')->insertGetId([
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

        $valueId = DB::table('attribute_values')->insertGetId([
            'attribute_id' => $attributeId,
            'value' => 'M',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('attribute_value_translations')->insert([
            'attribute_value_id' => $valueId,
            'lang' => 'en',
            'name' => 'Medium',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $variation = ProductVariation::query()->create(array_merge([
            'product_id' => $product->id,
            'code' => 'LINEN-M',
            'price' => 100,
            'stock' => 1,
            'current_stock' => 10,
        ], $overrides['variation'] ?? []));

        DB::table('product_variation_combinations')->insert([
            'product_id' => $product->id,
            'product_variation_id' => $variation->id,
            'attribute_id' => $attributeId,
            'attribute_value_id' => $valueId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$variation, $product, $shopId];
    }

    private function seedCartLine(array $attributes): int
    {
        $variation = ProductVariation::query()->findOrFail($attributes['product_variation_id']);

        return DB::table('carts')->insertGetId(array_merge([
            'user_id' => null,
            'temp_user_id' => 'guest-123',
            'product_id' => $variation->product_id,
            'product_variation_id' => $variation->id,
            'quantity' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }

    private function seedAddress(int $userId, int $zoneId, array $attributes = []): int
    {
        $cityId = DB::table('cities')->insertGetId([
            'zone_id' => $zoneId,
            'name' => 'Lagos',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (!DB::table('zones')->where('id', $zoneId)->exists()) {
            DB::table('zones')->insert([
                'id' => $zoneId,
                'standard_delivery_cost' => 12.5,
                'express_delivery_cost' => 20.0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return DB::table('addresses')->insertGetId(array_merge([
            'user_id' => $userId,
            'city_id' => $cityId,
            'address' => '12 Market Road',
            'country' => 'Nigeria',
            'state' => 'Lagos',
            'city' => 'Lagos',
            'postal_code' => '100001',
            'phone' => '08012345678',
            'default_shipping' => 1,
            'default_billing' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }
}
