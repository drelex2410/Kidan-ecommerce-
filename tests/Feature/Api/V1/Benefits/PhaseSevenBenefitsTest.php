<?php

namespace Tests\Feature\Api\V1\Benefits;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PhaseSevenBenefitsTest extends TestCase
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

        $this->createBenefitsSchema();
        $this->seedBenefitSettings();
    }

    public function test_refund_requests_list_returns_current_users_requests(): void
    {
        $user = $this->createUser(['email_verified_at' => now()]);
        $other = $this->createUser(['email' => 'other@example.com']);
        $refundId = $this->seedRefundRequestGraph($user);
        $this->seedRefundRequestGraph($other, 'OTHER-COMBINED');

        $response = $this->withToken($user->createToken('frontend-web')->plainTextToken)
            ->getJson('/api/v1/user/refund-requests?page=1');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.id', $refundId)
            ->assertJsonCount(1, 'data');
    }

    public function test_refund_create_context_enforces_ownership(): void
    {
        $user = $this->createUser(['email_verified_at' => now()]);
        $other = $this->createUser(['email' => 'other@example.com']);
        [, $orderId] = $this->seedEligibleOrder($other, 'OTHER-COMBINED');

        $this->withToken($user->createToken('frontend-web')->plainTextToken)
            ->getJson('/api/v1/user/refund-request/create/' . $orderId)
            ->assertOk()
            ->assertJsonPath('success', false);
    }

    public function test_refund_store_creates_request_for_eligible_order(): void
    {
        $user = $this->createUser(['email_verified_at' => now()]);
        [$orderDetailId, $orderId] = $this->seedEligibleOrder($user);
        $token = $user->createToken('frontend-web')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson('/api/v1/user/refund-request/store', [
                'order_id' => $orderId,
                'refund_items' => json_encode([
                    [
                        'status' => true,
                        'order_detail_id' => $orderDetailId,
                        'quantity' => 1,
                    ],
                ]),
                'refund_reasons' => 'Damaged',
                'refund_note' => 'Item arrived damaged.',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Your request has been submitted successfully');

        $this->assertDatabaseHas('refund_requests', [
            'order_id' => $orderId,
            'user_id' => $user->id,
        ]);
    }

    public function test_wallet_history_returns_current_users_entries(): void
    {
        $user = $this->createUser(['email_verified_at' => now()]);
        $this->seedWalletEntry($user, 5000, 'Recharge', 'Added');

        $response = $this->withToken($user->createToken('frontend-web')->plainTextToken)
            ->getJson('/api/v1/user/wallet/history?page=1');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.amount', 5000)
            ->assertJsonPath('meta.current_page', 1);
    }

    public function test_wallet_recharge_route_returns_explicit_handoff_failure(): void
    {
        $user = $this->createUser(['email_verified_at' => now()]);

        $this->withToken($user->createToken('frontend-web')->plainTextToken)
            ->postJson('/api/v1/user/wallet/recharge', ['amount' => 100])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_club_point_history_and_conversion_work(): void
    {
        $user = $this->createUser(['email_verified_at' => now(), 'balance' => 0]);
        $clubPointId = $this->seedClubPoint($user, 20);
        $token = $user->createToken('frontend-web')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/user/earning/history?page=1')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.id', $clubPointId);

        $response = $this->withToken($token)
            ->postJson('/api/v1/user/convert-point-into-wallet', ['id' => $clubPointId]);

        $response->assertOk();
        $this->assertSame(1, $response->json());
        $this->assertDatabaseHas('club_points', [
            'id' => $clubPointId,
            'convert_status' => 1,
        ]);
        $this->assertSame('10.00', number_format((float) $user->fresh()->balance, 2, '.', ''));
    }

    public function test_club_point_conversion_returns_legacy_unpaid_signal_for_unpaid_orders(): void
    {
        $user = $this->createUser(['email_verified_at' => now()]);
        $clubPointId = $this->seedClubPoint($user, 20, 'unpaid');

        $response = $this->withToken($user->createToken('frontend-web')->plainTextToken)
            ->postJson('/api/v1/user/convert-point-into-wallet', ['id' => $clubPointId]);

        $response->assertOk();
        $this->assertSame(3, $response->json());
    }

    public function test_affiliate_registration_balance_and_referral_contracts_work(): void
    {
        $user = $this->createUser(['email_verified_at' => now()]);
        $token = $user->createToken('frontend-web')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/user/affiliate/store', [
                'name' => 'Affiliate User',
                'email' => 'user@example.com',
                'phone' => '+2348000000000',
                'address' => '12 Allen Avenue',
                'description' => 'Content creator',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->withToken($token)
            ->getJson('/api/v1/user/affiliate/user-check')
            ->assertOk()
            ->assertJsonPath('affiliate_option', true)
            ->assertJsonPath('user_referral_code', $user->fresh()->referral_code);

        $this->withToken($token)
            ->getJson('/api/v1/user/affiliate/balance')
            ->assertOk()
            ->assertJsonStructure(['affiliate_balance', 'status']);

        $this->withToken($token)
            ->getJson('/api/v1/user/affiliate/referral-code')
            ->assertOk()
            ->assertJsonPath('status', 200);
    }

    public function test_affiliate_stats_and_histories_return_paginated_contracts(): void
    {
        $user = $this->createUser(['email_verified_at' => now()]);
        $profileId = $this->seedAffiliateProfile($user, 150);
        $orderDetailId = $this->seedAffiliateEarningOrder($user);
        DB::table('affiliate_stats')->insert([
            'affiliate_user_id' => $profileId,
            'no_of_click' => 10,
            'no_of_order_item' => 2,
            'no_of_delivered' => 1,
            'no_of_cancel' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('affiliate_payments')->insert([
            'affiliate_user_id' => $profileId,
            'amount' => 25,
            'payment_method' => 'Converted To Wallet',
            'payment_details' => 'Converted To Wallet',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('affiliate_withdraw_requests')->insert([
            'user_id' => $user->id,
            'amount' => 20,
            'status' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('affiliate_logs')->insert([
            'user_id' => $user->id,
            'referred_by_user_id' => $user->id,
            'order_detail_id' => $orderDetailId,
            'affiliate_type' => 'product_sharing',
            'amount' => 15,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $token = $user->createToken('frontend-web')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/user/affiliate/stats')
            ->assertOk()
            ->assertJsonPath('data.click', 10)
            ->assertJsonPath('data.item', 2);

        $this->withToken($token)
            ->getJson('/api/v1/user/affiliate/payment-history?page=1')
            ->assertOk()
            ->assertJsonPath('data.0.payment_method', 'Converted To Wallet')
            ->assertJsonPath('meta.current_page', 1);

        $this->withToken($token)
            ->getJson('/api/v1/user/affiliate/earning-history?page=1')
            ->assertOk()
            ->assertJsonPath('data.0.referrel_type', 'product_sharing')
            ->assertJsonPath('meta.current_page', 1);

        $this->withToken($token)
            ->getJson('/api/v1/user/affiliate/withdraw-request?page=1')
            ->assertOk()
            ->assertJsonPath('data.0.amount', 20)
            ->assertJsonPath('meta.current_page', 1);
    }

    public function test_affiliate_payment_settings_and_convert_request_work(): void
    {
        $user = $this->createUser(['email_verified_at' => now(), 'balance' => 0]);
        $this->seedAffiliateProfile($user, 150);
        $token = $user->createToken('frontend-web')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/user/affiliate/payment-settings', [
                'paypalEmail' => 'paypal@example.com',
                'bankInformations' => 'Bank details',
            ])
            ->assertOk()
            ->assertJsonPath('status', 200);

        $this->withToken($token)
            ->postJson('/api/v1/user/affiliate/convert-request', [
                'amount' => 50,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('affiliate_payments', [
            'payment_method' => 'Converted To Wallet',
            'amount' => 50,
        ]);
        $this->assertSame('50.00', number_format((float) $user->fresh()->balance, 2, '.', ''));
    }

    public function test_affiliate_withdraw_request_rejects_insufficient_balance(): void
    {
        $user = $this->createUser(['email_verified_at' => now()]);
        $this->seedAffiliateProfile($user, 20);

        $this->withToken($user->createToken('frontend-web')->plainTextToken)
            ->postJson('/api/v1/user/affiliate/withdraw-request', [
                'amount' => 25,
            ])
            ->assertOk()
            ->assertJsonPath('success', false);
    }

    private function createBenefitsSchema(): void
    {
        foreach ([
            'affiliate_logs',
            'affiliate_stats',
            'affiliate_options',
            'affiliate_payments',
            'affiliate_withdraw_requests',
            'affiliate_users',
            'club_points',
            'wallets',
            'refund_request_items',
            'refund_requests',
            'order_updates',
            'order_details',
            'orders',
            'combined_orders',
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
            $table->string('verification_code')->nullable();
            $table->timestamp('verification_sent_at')->nullable();
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
            $table->string('slug')->nullable();
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
            $table->boolean('published')->default(true);
            $table->boolean('approved')->default(true);
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
            $table->string('variation_key')->nullable();
            $table->string('sku')->nullable();
            $table->unsignedInteger('current_stock')->default(0);
            $table->timestamps();
        });

        Schema::create('product_variation_combinations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_variation_id');
            $table->unsignedBigInteger('attribute_id');
            $table->unsignedBigInteger('attribute_value_id');
            $table->timestamps();
        });

        Schema::create('combined_orders', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('code');
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('combined_order_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('shop_id')->nullable();
            $table->string('code')->nullable();
            $table->string('payment_status')->default('paid');
            $table->string('delivery_status')->default('delivered');
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->decimal('shipping_cost', 12, 2)->default(0);
            $table->decimal('coupon_discount', 12, 2)->default(0);
            $table->string('payment_type')->nullable();
            $table->boolean('manual_payment')->default(false);
            $table->text('manual_payment_data')->nullable();
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

        Schema::create('refund_requests', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('shop_id')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->text('reasons')->nullable();
            $table->text('refund_note')->nullable();
            $table->text('attachments')->nullable();
            $table->unsignedTinyInteger('admin_approval')->default(0);
            $table->timestamps();
        });

        Schema::create('refund_request_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('refund_request_id');
            $table->unsignedBigInteger('order_detail_id');
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();
        });

        Schema::create('wallets', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('payment_method')->nullable();
            $table->string('payment_details')->nullable();
            $table->string('details')->nullable();
            $table->string('type')->nullable();
            $table->string('reciept')->nullable();
            $table->boolean('approval')->default(true);
            $table->timestamps();
        });

        Schema::create('club_points', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('points')->default(0);
            $table->unsignedBigInteger('combined_order_id')->nullable();
            $table->unsignedTinyInteger('convert_status')->default(0);
            $table->timestamps();
        });

        Schema::create('affiliate_users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->decimal('balance', 12, 2)->default(0);
            $table->unsignedTinyInteger('status')->default(0);
            $table->text('informations')->nullable();
            $table->string('paypal_email')->nullable();
            $table->text('bank_information')->nullable();
            $table->timestamps();
        });

        Schema::create('affiliate_payments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('affiliate_user_id');
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('payment_method')->nullable();
            $table->string('payment_details')->nullable();
            $table->timestamps();
        });

        Schema::create('affiliate_withdraw_requests', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->decimal('amount', 12, 2)->default(0);
            $table->unsignedTinyInteger('status')->default(0);
            $table->timestamps();
        });

        Schema::create('affiliate_options', function (Blueprint $table): void {
            $table->id();
            $table->string('type');
            $table->unsignedTinyInteger('status')->default(0);
            $table->timestamps();
        });

        Schema::create('affiliate_stats', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('affiliate_user_id');
            $table->unsignedInteger('no_of_click')->default(0);
            $table->unsignedInteger('no_of_order_item')->default(0);
            $table->unsignedInteger('no_of_delivered')->default(0);
            $table->unsignedInteger('no_of_cancel')->default(0);
            $table->timestamps();
        });

        Schema::create('affiliate_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('referred_by_user_id');
            $table->unsignedBigInteger('order_detail_id')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->string('affiliate_type')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    private function seedBenefitSettings(): void
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
            ['type' => 'wallet_system', 'value' => '1', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'club_point', 'value' => '1', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'club_point_convert_rate', 'value' => '2', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'affiliate_system', 'value' => '1', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'refund_request_order_status', 'value' => json_encode(['delivered']), 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'refund_request_time_period', 'value' => '7', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('affiliate_options')->insert([
            ['type' => 'product_sharing', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'category_wise_affiliate', 'status' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);

        Cache::flush();
    }

    private function createUser(array $attributes = []): User
    {
        static $emailCounter = 1;
        static $phoneCounter = 1;

        $email = $attributes['email'] ?? 'user' . $emailCounter++ . '@example.com';
        $phone = $attributes['phone'] ?? '+2348000000' . str_pad((string) $phoneCounter++, 3, '0', STR_PAD_LEFT);

        return User::query()->create(array_merge([
            'name' => 'Test User',
            'email' => $email,
            'phone' => $phone,
            'password' => Hash::make('secret123'),
            'user_type' => 'customer',
            'banned' => false,
            'balance' => 0,
            'club_points' => 0,
        ], $attributes));
    }

    private function seedShop(User $user): int
    {
        return (int) DB::table('shops')->insertGetId([
            'user_id' => $user->id,
            'name' => 'Benefit Shop',
            'slug' => 'benefit-shop-' . $user->id,
            'published' => 1,
            'approval' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedProduct(int $shopId): int
    {
        $productId = (int) DB::table('products')->insertGetId([
            'shop_id' => $shopId,
            'name' => 'Linen Shirt',
            'slug' => 'linen-shirt-' . $shopId,
            'lowest_price' => 40,
            'highest_price' => 40,
            'stock' => 20,
            'min_qty' => 1,
            'max_qty' => 5,
            'published' => 1,
            'approved' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_translations')->insert([
            'product_id' => $productId,
            'lang' => 'en',
            'name' => 'Linen Shirt',
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

        return $productId;
    }

    private function seedEligibleOrder(User $user, string $combinedCode = 'COMBINED-REFUND'): array
    {
        $shopId = $this->seedShop($user);
        $productId = $this->seedProduct($shopId);

        $combinedOrderId = (int) DB::table('combined_orders')->insertGetId([
            'user_id' => $user->id,
            'code' => $combinedCode,
            'grand_total' => 45,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $orderId = (int) DB::table('orders')->insertGetId([
            'combined_order_id' => $combinedOrderId,
            'user_id' => $user->id,
            'shop_id' => $shopId,
            'code' => 'ORDER-' . $combinedCode,
            'payment_status' => 'paid',
            'delivery_status' => 'delivered',
            'grand_total' => 45,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        $orderDetailId = (int) DB::table('order_details')->insertGetId([
            'order_id' => $orderId,
            'product_id' => $productId,
            'quantity' => 1,
            'price' => 40,
            'tax' => 5,
            'total' => 45,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$orderDetailId, $orderId];
    }

    private function seedRefundRequestGraph(User $user, string $combinedCode = 'COMBINED-REFUND'): int
    {
        [$orderDetailId, $orderId] = $this->seedEligibleOrder($user, $combinedCode);

        $refundId = (int) DB::table('refund_requests')->insertGetId([
            'order_id' => $orderId,
            'user_id' => $user->id,
            'shop_id' => 1,
            'amount' => 45,
            'reasons' => json_encode(['Damaged']),
            'refund_note' => 'Damaged item',
            'admin_approval' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('refund_request_items')->insert([
            'refund_request_id' => $refundId,
            'order_detail_id' => $orderDetailId,
            'quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $refundId;
    }

    private function seedWalletEntry(User $user, float $amount, string $details, string $type): void
    {
        DB::table('wallets')->insert([
            'user_id' => $user->id,
            'amount' => $amount,
            'payment_method' => 'manual',
            'payment_details' => $details,
            'details' => $details,
            'type' => $type,
            'approval' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedClubPoint(User $user, int $points, string $paymentStatus = 'paid'): int
    {
        $combinedOrderId = (int) DB::table('combined_orders')->insertGetId([
            'user_id' => $user->id,
            'code' => 'CLUB-' . $user->id . '-' . $points,
            'grand_total' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('orders')->insert([
            'combined_order_id' => $combinedOrderId,
            'user_id' => $user->id,
            'shop_id' => $this->seedShop($user),
            'code' => 'CLUB-ORDER-' . $points,
            'payment_status' => $paymentStatus,
            'delivery_status' => 'delivered',
            'grand_total' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (int) DB::table('club_points')->insertGetId([
            'user_id' => $user->id,
            'points' => $points,
            'combined_order_id' => $combinedOrderId,
            'convert_status' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedAffiliateProfile(User $user, float $balance): int
    {
        return (int) DB::table('affiliate_users')->insertGetId([
            'user_id' => $user->id,
            'balance' => $balance,
            'status' => 1,
            'informations' => json_encode(['name' => $user->name]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedAffiliateEarningOrder(User $user): int
    {
        [$orderDetailId] = $this->seedEligibleOrder($user, 'AFFILIATE-COMBINED');

        return $orderDetailId;
    }
}
