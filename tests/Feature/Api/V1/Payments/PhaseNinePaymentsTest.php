<?php

namespace Tests\Feature\Api\V1\Payments;

use App\Models\CombinedOrder;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PhaseNinePaymentsTest extends TestCase
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

    public function test_payment_initializer_returns_handoff_for_valid_unpaid_order(): void
    {
        $user = $this->createUser();
        $combinedOrder = $this->createCombinedOrder($user, 'unpaid');

        $response = $this->withToken($user->createToken('frontend-web')->plainTextToken)
            ->postJson('/api/v1/payment/paystack/pay', [
                'redirect_to' => '/checkout',
                'payment_method' => 'paystack',
                'payment_type' => 'cart_payment',
                'user_id' => $user->id,
                'order_code' => $combinedOrder->code,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('go_to_payment', true)
            ->assertJsonPath('payment_method', 'paystack')
            ->assertJsonPath('order_code', $combinedOrder->code)
            ->assertJsonPath('grand_total', 120);

        $this->assertDatabaseHas('payments', [
            'gateway' => 'paystack',
            'payment_type' => 'cart_payment',
            'order_code' => $combinedOrder->code,
            'status' => 'initiated',
        ]);
    }

    public function test_payment_initializer_rejects_unauthorized_order_owner(): void
    {
        $owner = $this->createUser(['email' => 'owner@example.com']);
        $intruder = $this->createUser(['email' => 'intruder@example.com']);
        $combinedOrder = $this->createCombinedOrder($owner, 'unpaid');

        $response = $this->withToken($intruder->createToken('frontend-web')->plainTextToken)
            ->postJson('/api/v1/payment/paystack/pay', [
                'redirect_to' => '/checkout',
                'payment_method' => 'paystack',
                'payment_type' => 'cart_payment',
                'user_id' => $intruder->id,
                'order_code' => $combinedOrder->code,
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'You are not allowed to pay for this order.');
    }

    public function test_payment_initializer_rejects_already_paid_orders(): void
    {
        $user = $this->createUser();
        $combinedOrder = $this->createCombinedOrder($user, 'paid');

        $response = $this->withToken($user->createToken('frontend-web')->plainTextToken)
            ->postJson('/api/v1/payment/paystack/pay', [
                'redirect_to' => '/checkout',
                'payment_method' => 'paystack',
                'payment_type' => 'repayment',
                'user_id' => $user->id,
                'order_code' => $combinedOrder->code,
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'This order has already been paid.');
    }

    public function test_payment_initializer_rejects_unsupported_gateway(): void
    {
        $user = $this->createUser();

        $response = $this->withToken($user->createToken('frontend-web')->plainTextToken)
            ->postJson('/api/v1/payment/not-a-gateway/pay', [
                'redirect_to' => '/checkout',
                'payment_method' => 'not-a-gateway',
                'payment_type' => 'wallet_payment',
                'user_id' => $user->id,
                'amount' => 40,
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unsupported payment gateway.');
    }

    public function test_offline_payment_initializer_returns_pending_contract(): void
    {
        $user = $this->createUser();
        $combinedOrder = $this->createCombinedOrder($user, 'unpaid');
        DB::table('manual_payment_methods')->insert([
            'id' => 1,
            'heading' => 'Bank Transfer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withToken($user->createToken('frontend-web')->plainTextToken)
            ->postJson('/api/v1/payment/offline_payment-1/pay', [
                'redirect_to' => '/user/purchase-history',
                'payment_method' => 'offline_payment-1',
                'payment_type' => 'repayment',
                'user_id' => $user->id,
                'order_code' => $combinedOrder->code,
                'transactionId' => 'TX12345',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('go_to_payment', false)
            ->assertJsonPath('payment_method', 'offline_payment-1')
            ->assertJsonPath('order_code', $combinedOrder->code);

        $this->assertDatabaseHas('payments', [
            'gateway' => 'offline_payment-1',
            'payment_type' => 'repayment',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'combined_order_id' => $combinedOrder->id,
            'manual_payment' => 1,
            'payment_type' => 'offline_payment-1',
        ]);
    }

    public function test_stripe_success_callback_marks_order_paid_and_redirects(): void
    {
        $user = $this->createUser();
        $combinedOrder = $this->createCombinedOrder($user, 'unpaid');
        $payment = $this->createPayment($user, $combinedOrder, 'cart_payment', 'stripe', 120);

        $response = $this->withSession([
            'payment_id' => $payment->id,
            'payment_method' => 'stripe',
        ])->get('/payment/stripe/success');

        $response->assertRedirect('/checkout?cart_payment=success&payment_method=stripe&order_code=' . $combinedOrder->code);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'paid',
        ]);
        $this->assertDatabaseHas('orders', [
            'combined_order_id' => $combinedOrder->id,
            'payment_status' => 'paid',
            'payment_type' => 'stripe',
        ]);
    }

    public function test_stripe_cancel_callback_marks_payment_failed(): void
    {
        $user = $this->createUser();
        $combinedOrder = $this->createCombinedOrder($user, 'unpaid');
        $payment = $this->createPayment($user, $combinedOrder, 'cart_payment', 'stripe', 120);

        $response = $this->withSession([
            'payment_id' => $payment->id,
            'payment_method' => 'stripe',
        ])->get('/payment/stripe/cancel');

        $response->assertRedirect('/checkout?cart_payment=failed&payment_method=stripe&order_code=' . $combinedOrder->code);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'failed',
        ]);
    }

    public function test_duplicate_success_callback_is_idempotent_for_wallet_recharge(): void
    {
        $user = $this->createUser(['balance' => 0]);
        $payment = $this->createPayment($user, null, 'wallet_payment', 'stripe', 50);

        $this->withSession([
            'payment_id' => $payment->id,
            'payment_method' => 'stripe',
        ])->get('/payment/stripe/success');

        $this->withSession([
            'payment_id' => $payment->id,
            'payment_method' => 'stripe',
        ])->get('/payment/stripe/success');

        $this->assertSame('50.00', number_format((float) User::query()->find($user->id)->balance, 2, '.', ''));
        $this->assertSame(1, DB::table('wallets')->count());
        $this->assertSame(1, DB::table('payment_transactions')->count());
    }

    private function createSchema(): void
    {
        foreach ([
            'payment_transactions',
            'payments',
            'wallets',
            'manual_payment_methods',
            'orders',
            'combined_orders',
            'settings',
            'currencies',
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
            $table->string('password');
            $table->string('user_type')->default('customer');
            $table->boolean('banned')->default(false);
            $table->decimal('balance', 12, 2)->default(0);
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
            $table->string('code')->default('USD');
            $table->decimal('exchange_rate', 12, 6)->default(1);
            $table->timestamps();
        });

        Schema::create('combined_orders', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('code');
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->text('shipping_address')->nullable();
            $table->text('billing_address')->nullable();
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('shop_id')->default(1);
            $table->unsignedBigInteger('combined_order_id');
            $table->string('code')->nullable();
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->string('payment_type')->nullable();
            $table->text('payment_details')->nullable();
            $table->string('payment_status')->nullable();
            $table->string('delivery_status')->nullable();
            $table->boolean('manual_payment')->default(false);
            $table->text('manual_payment_data')->nullable();
            $table->timestamps();
        });

        Schema::create('manual_payment_methods', function (Blueprint $table): void {
            $table->id();
            $table->string('heading');
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
            $table->boolean('approval')->default(false);
            $table->boolean('offline_payment')->default(false);
            $table->string('reciept')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('combined_order_id')->nullable();
            $table->string('gateway');
            $table->string('payment_type');
            $table->string('payment_method');
            $table->string('order_code')->nullable();
            $table->decimal('amount', 20, 2)->default(0);
            $table->string('currency', 10)->nullable();
            $table->string('status')->default('initiated');
            $table->string('redirect_to')->nullable();
            $table->text('meta')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_transactions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('payment_id');
            $table->string('gateway');
            $table->string('event_type');
            $table->string('reference')->nullable();
            $table->string('status');
            $table->string('fingerprint')->unique();
            $table->text('payload')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    private function seedSettings(): void
    {
        DB::table('currencies')->insert([
            'id' => 1,
            'name' => 'US Dollar',
            'symbol' => '$',
            'code' => 'USD',
            'exchange_rate' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('settings')->insert([
            ['type' => 'system_default_currency', 'value' => '1', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'paystack_payment', 'value' => '1', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'stripe_payment', 'value' => '1', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'offline_payment', 'value' => '1', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Cache::flush();
    }

    private function createUser(array $attributes = []): User
    {
        static $index = 1;

        $user = User::query()->create(array_merge([
            'name' => 'Test User',
            'email' => 'user' . $index++ . '@example.com',
            'password' => Hash::make('secret123'),
            'user_type' => 'customer',
            'banned' => false,
            'balance' => 0,
        ], $attributes));

        return $user;
    }

    private function createCombinedOrder(User $user, string $paymentStatus): CombinedOrder
    {
        $combinedOrderId = DB::table('combined_orders')->insertGetId([
            'user_id' => $user->id,
            'code' => 'ORD-' . random_int(1000, 9999),
            'grand_total' => 120,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('orders')->insert([
            'user_id' => $user->id,
            'shop_id' => 1,
            'combined_order_id' => $combinedOrderId,
            'code' => '1',
            'grand_total' => 120,
            'payment_status' => $paymentStatus,
            'delivery_status' => 'order_placed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return CombinedOrder::query()->findOrFail($combinedOrderId);
    }

    private function createPayment(User $user, ?CombinedOrder $combinedOrder, string $paymentType, string $gateway, float $amount): Payment
    {
        return Payment::query()->create([
            'user_id' => $user->id,
            'combined_order_id' => $combinedOrder?->id,
            'gateway' => $gateway,
            'payment_type' => $paymentType,
            'payment_method' => $gateway,
            'order_code' => $combinedOrder?->code,
            'amount' => $amount,
            'currency' => 'USD',
            'status' => 'initiated',
            'redirect_to' => '/checkout',
            'meta' => json_encode([]),
        ]);
    }
}
