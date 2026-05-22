<?php

namespace Tests\Feature\Payments;

use App\Jobs\Payments\ProcessAlatPayWebhookJob;
use App\Jobs\Payments\ReconcileAlatPayTransactionJob;
use App\Models\AlatPayTransaction;
use App\Models\CombinedOrder;
use App\Models\Payment;
use App\Models\User;
use App\Services\Payments\AlatPay\AlatPayConfig;
use App\Services\Payments\AlatPay\AlatPayService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AlatPayGatewayTest extends TestCase
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

    public function test_alatpay_config_save_encrypts_secrets_and_preserves_them_on_blank_updates(): void
    {
        $config = app(AlatPayConfig::class);

        $config->save([
            'alatpay_env' => 'sandbox',
            'alatpay_base_url' => 'https://sandbox.alatpay.example',
            'alatpay_merchant_id' => 'merchant_123',
            'alatpay_client_id' => 'client_123',
            'alatpay_client_secret' => 'super-secret',
            'alatpay_subscription_key' => 'sub-key',
            'alatpay_callback_url' => 'https://example.com/api/v1/payment/alatpay/webhook',
            'alatpay_webhook_secret' => 'webhook-secret',
            'alatpay_supported_currencies' => ['NGN', 'USD'],
            'alatpay_charge_type' => 'percentage',
            'alatpay_charge_flat' => 0,
            'alatpay_charge_percent' => 1.5,
        ]);

        $storedSecret = DB::table('settings')->where('type', 'alatpay_client_secret')->value('value');

        $this->assertNotSame('super-secret', $storedSecret);
        $this->assertSame('super-secret', $config->clientSecret());
        $this->assertSame(['NGN', 'USD'], $config->supportedCurrencies());

        $config->save([
            'alatpay_env' => 'sandbox',
            'alatpay_base_url' => 'https://sandbox.alatpay.example',
            'alatpay_merchant_id' => 'merchant_123',
            'alatpay_client_id' => 'client_123',
            'alatpay_callback_url' => 'https://example.com/api/v1/payment/alatpay/webhook',
            'alatpay_supported_currencies' => ['NGN'],
            'alatpay_charge_type' => 'flat',
            'alatpay_charge_flat' => 100,
            'alatpay_charge_percent' => 0,
        ]);

        $this->assertSame('super-secret', $config->clientSecret());
        $this->assertSame('sub-key', $config->subscriptionKey());
        $this->assertSame('webhook-secret', $config->webhookSecret());
    }

    public function test_api_initializer_returns_handoff_for_alatpay_when_enabled(): void
    {
        $user = $this->createUser();
        $combinedOrder = $this->createCombinedOrder($user, 'unpaid');

        $response = $this->withToken($user->createToken('frontend-web')->plainTextToken)
            ->postJson('/api/v1/payment/alatpay/pay', [
                'redirect_to' => '/checkout',
                'payment_method' => 'alatpay',
                'payment_type' => 'cart_payment',
                'user_id' => $user->id,
                'order_code' => $combinedOrder->code,
                'currency' => 'NGN',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('go_to_payment', true)
            ->assertJsonPath('payment_method', 'alatpay')
            ->assertJsonPath('order_code', $combinedOrder->code);

        $this->assertDatabaseHas('payments', [
            'gateway' => 'alatpay',
            'payment_type' => 'cart_payment',
            'status' => 'initiated',
        ]);
    }

    public function test_web_initialization_creates_alatpay_transaction_and_redirects_to_instruction_page(): void
    {
        Queue::fake();

        Http::fake([
            'https://sandbox.alatpay.example/api/v1/bankTransfer/virtualAccount' => Http::response([
                'status' => true,
                'message' => 'Initialized',
                'data' => [
                    'id' => 'provider-row-1',
                    'merchantId' => 'merchant_123',
                    'transactionId' => 'txn_123',
                    'sessionId' => 'sess_123',
                    'status' => 'pending',
                    'amount' => 120.00,
                    'currency' => 'NGN',
                    'virtualBankAccountNumber' => '1234567890',
                    'virtualBankCode' => '035',
                    'businessName' => 'Kidan',
                    'expiredAt' => now()->addMinutes(30)->toIso8601String(),
                ],
            ], 200),
        ]);

        $user = $this->createUser();
        $combinedOrder = $this->createCombinedOrder($user, 'unpaid');

        $response = $this->actingAs($user)->get('/payment/alatpay/pay?' . http_build_query([
            'redirect_to' => '/checkout',
            'payment_method' => 'alatpay',
            'payment_type' => 'cart_payment',
            'user_id' => $user->id,
            'order_code' => $combinedOrder->code,
            'currency' => 'NGN',
            'tenant_id' => 'tenant-1',
            'escrow_id' => 'escrow-1',
            'payment_channel' => 'bank_transfer',
            'session_reference' => 'sess-local-123',
        ]));

        $transaction = AlatPayTransaction::query()->firstOrFail();

        $response->assertRedirect(route('alatpay.checkout', ['reference' => $transaction->reference]));

        $this->assertSame('pending', $transaction->status);
        $this->assertSame('tenant-1', $transaction->tenant_id);
        $this->assertSame('escrow-1', $transaction->escrow_id);
        $this->assertSame('sess-local-123', $transaction->session_reference);
        $this->assertSame('bank_transfer', $transaction->payment_channel);
        $this->assertStringContainsString('/payment/alatpay/checkout/', (string) $transaction->checkout_url);

        $this->assertDatabaseHas('payments', [
            'id' => $transaction->payment_id,
            'status' => 'pending',
            'gateway' => 'alatpay',
        ]);

        Queue::assertPushed(ReconcileAlatPayTransactionJob::class);
    }

    public function test_successful_verification_marks_payment_paid_and_is_idempotent(): void
    {
        Http::fake([
            'https://sandbox.alatpay.example/api/v1/transaction/check-transaction-status' => Http::response([
                'status' => true,
                'message' => 'Payment confirmed',
                'data' => [
                    'transactionId' => 'txn_123',
                    'sessionId' => 'sess_123',
                    'status' => 'successful',
                    'amount' => 120.00,
                    'currency' => 'NGN',
                ],
            ], 200),
        ]);

        $user = $this->createUser();
        $combinedOrder = $this->createCombinedOrder($user, 'unpaid');
        $payment = $this->createPayment($user, $combinedOrder, 120);
        $transaction = $this->createAlatPayTransaction($payment, [
            'status' => 'pending',
            'transaction_id' => 'txn_123',
            'provider_reference' => 'sess_123',
        ]);

        $service = app(AlatPayService::class);
        $first = $service->verify($transaction);
        $second = $service->verify($transaction->fresh());

        $this->assertSame('successful', $first['status']);
        $this->assertSame('successful', $second['status']);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'paid',
            'payment_method' => 'alatpay',
        ]);

        $this->assertDatabaseHas('orders', [
            'combined_order_id' => $combinedOrder->id,
            'payment_status' => 'paid',
            'payment_type' => 'alatpay',
        ]);

        $this->assertSame(2, DB::table('payment_transactions')->count());
    }

    public function test_amount_mismatch_keeps_payment_unpaid(): void
    {
        Http::fake([
            'https://sandbox.alatpay.example/api/v1/transaction/check-transaction-status' => Http::response([
                'status' => true,
                'message' => 'Payment confirmed',
                'data' => [
                    'transactionId' => 'txn_999',
                    'sessionId' => 'sess_999',
                    'status' => 'successful',
                    'amount' => 999.00,
                    'currency' => 'NGN',
                ],
            ], 200),
        ]);

        $user = $this->createUser();
        $combinedOrder = $this->createCombinedOrder($user, 'unpaid');
        $payment = $this->createPayment($user, $combinedOrder, 120);
        $transaction = $this->createAlatPayTransaction($payment, [
            'status' => 'pending',
            'transaction_id' => 'txn_999',
            'provider_reference' => 'sess_999',
        ]);

        app(AlatPayService::class)->verify($transaction);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'initiated',
        ]);

        $this->assertDatabaseHas('alatpay_transactions', [
            'id' => $transaction->id,
            'status' => 'processing',
        ]);
    }

    public function test_webhook_requires_valid_signature_and_valid_webhook_is_queued(): void
    {
        Queue::fake();

        $payload = json_encode([
            'event' => 'payment.success',
            'data' => [
                'orderId' => 'KIDAN-ALAT-99',
                'sessionId' => 'sess_webhook',
                'status' => 'successful',
                'metadata' => [
                    'tenant_id' => 'tenant-9',
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $timestamp = now()->toIso8601String();
        $signature = hash_hmac('sha256', $timestamp . '.' . $payload, 'webhook-secret');

        $invalid = $this->call('POST', '/api/v1/payment/alatpay/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_ALATPAY_TIMESTAMP' => $timestamp,
            'HTTP_X_ALATPAY_SIGNATURE' => 'invalid-signature',
        ], $payload);

        $invalid->assertStatus(401);

        $valid = $this->call('POST', '/api/v1/payment/alatpay/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_ALATPAY_TIMESTAMP' => $timestamp,
            'HTTP_X_ALATPAY_SIGNATURE' => $signature,
        ], $payload);

        $valid->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('alatpay_webhook_logs', [
            'event_type' => 'payment.success',
            'status' => 'queued',
            'tenant_id' => 'tenant-9',
        ]);

        Queue::assertPushed(ProcessAlatPayWebhookJob::class);
    }

    public function test_request_refund_creates_alatpay_refund_record(): void
    {
        Http::fake([
            'https://sandbox.alatpay.example/api/v1/refunds' => Http::response([
                'status' => true,
                'message' => 'Refund accepted',
                'data' => [
                    'id' => 'refund-provider-id',
                    'transactionId' => 'refund-txn-123',
                    'status' => 'successful',
                ],
            ], 200),
        ]);

        $user = $this->createUser();
        $combinedOrder = $this->createCombinedOrder($user, 'paid');
        $payment = $this->createPayment($user, $combinedOrder, 120, 'paid');
        $transaction = $this->createAlatPayTransaction($payment, [
            'status' => 'successful',
            'transaction_id' => 'txn_refund_1',
            'provider_reference' => 'sess_refund_1',
        ]);

        $refund = app(AlatPayService::class)->requestRefund(
            $transaction,
            50,
            'Customer returned one item',
            14,
            $user->id,
            ['source' => 'test-suite']
        );

        $this->assertSame('successful', $refund->status);
        $this->assertSame('refund-txn-123', $refund->provider_reference);

        $this->assertDatabaseHas('alatpay_refunds', [
            'id' => $refund->id,
            'alatpay_transaction_id' => $transaction->id,
            'refund_request_id' => 14,
            'status' => 'successful',
        ]);
    }

    private function createSchema(): void
    {
        foreach ([
            'alatpay_refunds',
            'alatpay_reconciliation_logs',
            'alatpay_webhook_logs',
            'alatpay_transactions',
            'payment_transactions',
            'payments',
            'orders',
            'combined_orders',
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
            $table->string('symbol')->default('N');
            $table->string('code')->default('NGN');
            $table->decimal('exchange_rate', 12, 6)->default(1);
            $table->timestamps();
        });

        Schema::create('combined_orders', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('guest_id')->nullable();
            $table->string('code');
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->text('shipping_address')->nullable();
            $table->text('billing_address')->nullable();
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('shop_id')->default(1);
            $table->unsignedBigInteger('combined_order_id');
            $table->string('code')->nullable();
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->string('payment_type')->nullable();
            $table->text('payment_details')->nullable();
            $table->string('payment_status')->nullable();
            $table->string('delivery_status')->nullable();
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
            $table->json('meta')->nullable();
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
            $table->json('payload')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('alatpay_transactions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('combined_order_id')->nullable();
            $table->string('reference')->unique();
            $table->string('transaction_id')->nullable();
            $table->string('provider_reference')->nullable();
            $table->string('provider_record_id')->nullable();
            $table->string('merchant_id')->nullable();
            $table->string('order_code')->nullable();
            $table->string('order_identifier')->nullable();
            $table->string('tenant_id')->nullable();
            $table->string('escrow_id')->nullable();
            $table->string('session_reference')->nullable();
            $table->string('payment_channel')->default('bank_transfer');
            $table->string('currency', 10)->default('NGN');
            $table->decimal('amount', 20, 2)->default(0);
            $table->string('environment', 40)->default('sandbox');
            $table->string('status', 40)->default('pending');
            $table->string('checkout_url')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('last_reconciled_at')->nullable();
            $table->json('instructions')->nullable();
            $table->json('provider_payload')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('alatpay_webhook_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('alatpay_transaction_id')->nullable();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->string('tenant_id')->nullable();
            $table->uuid('correlation_id')->index();
            $table->string('event_type')->nullable();
            $table->string('reference')->nullable();
            $table->string('provider_reference')->nullable();
            $table->string('fingerprint')->unique();
            $table->string('signature')->nullable();
            $table->string('timestamp_header')->nullable();
            $table->string('status', 40)->default('received');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->json('headers')->nullable();
            $table->json('payload')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('alatpay_reconciliation_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('alatpay_transaction_id')->nullable();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->string('tenant_id')->nullable();
            $table->uuid('correlation_id')->nullable();
            $table->string('reference')->nullable();
            $table->string('provider_reference')->nullable();
            $table->string('action', 80);
            $table->string('status', 40)->default('queued');
            $table->string('response_code')->nullable();
            $table->string('message')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('reconciled_at')->nullable();
            $table->timestamp('next_retry_at')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });

        Schema::create('alatpay_refunds', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->unsignedBigInteger('alatpay_transaction_id')->nullable();
            $table->unsignedInteger('refund_request_id')->nullable();
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->string('reference')->unique();
            $table->string('provider_reference')->nullable();
            $table->string('tenant_id')->nullable();
            $table->string('order_code')->nullable();
            $table->decimal('amount', 20, 2)->default(0);
            $table->string('currency', 10)->default('NGN');
            $table->string('status', 40)->default('pending');
            $table->string('reason')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->json('provider_payload')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function seedSettings(): void
    {
        DB::table('currencies')->insert([
            'id' => 1,
            'name' => 'Naira',
            'symbol' => 'N',
            'code' => 'NGN',
            'exchange_rate' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('settings')->insert([
            ['type' => 'system_default_currency', 'value' => '1', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'alatpay_payment', 'value' => '1', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'alatpay_env', 'value' => 'sandbox', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'alatpay_base_url', 'value' => 'https://sandbox.alatpay.example', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'alatpay_merchant_id', 'value' => 'merchant_123', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'alatpay_client_id', 'value' => 'client_123', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'alatpay_callback_url', 'value' => 'https://example.com/api/v1/payment/alatpay/webhook', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'alatpay_supported_currencies', 'value' => json_encode(['NGN']), 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'alatpay_charge_type', 'value' => 'percentage', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'alatpay_charge_flat', 'value' => '0', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'alatpay_charge_percent', 'value' => '0', 'created_at' => now(), 'updated_at' => now()],
        ]);

        app(AlatPayConfig::class)->save([
            'alatpay_env' => 'sandbox',
            'alatpay_base_url' => 'https://sandbox.alatpay.example',
            'alatpay_merchant_id' => 'merchant_123',
            'alatpay_client_id' => 'client_123',
            'alatpay_client_secret' => 'super-secret',
            'alatpay_subscription_key' => 'sub-key',
            'alatpay_callback_url' => 'https://example.com/api/v1/payment/alatpay/webhook',
            'alatpay_webhook_secret' => 'webhook-secret',
            'alatpay_supported_currencies' => ['NGN'],
            'alatpay_charge_type' => 'percentage',
            'alatpay_charge_flat' => 0,
            'alatpay_charge_percent' => 0,
        ]);

        Cache::flush();
    }

    private function createUser(array $attributes = []): User
    {
        static $index = 1;

        return User::query()->create(array_merge([
            'name' => 'Test User',
            'email' => 'alatpay-user' . $index++ . '@example.com',
            'password' => Hash::make('secret123'),
            'user_type' => 'customer',
            'banned' => false,
            'balance' => 0,
        ], $attributes));
    }

    private function createCombinedOrder(User $user, string $paymentStatus): CombinedOrder
    {
        $combinedOrderId = DB::table('combined_orders')->insertGetId([
            'user_id' => $user->id,
            'code' => 'ALAT-ORD-' . random_int(1000, 9999),
            'grand_total' => 120,
            'shipping_address' => json_encode([
                'name' => $user->name,
                'email' => $user->email,
                'phone' => '08000000000',
            ]),
            'billing_address' => json_encode([
                'name' => $user->name,
                'email' => $user->email,
                'phone' => '08000000000',
            ]),
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
            'delivery_status' => 'delivered',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return CombinedOrder::query()->findOrFail($combinedOrderId);
    }

    private function createPayment(User $user, CombinedOrder $combinedOrder, float $amount, string $status = 'initiated'): Payment
    {
        return Payment::query()->create([
            'user_id' => $user->id,
            'combined_order_id' => $combinedOrder->id,
            'gateway' => 'alatpay',
            'payment_type' => 'cart_payment',
            'payment_method' => 'alatpay',
            'order_code' => $combinedOrder->code,
            'amount' => $amount,
            'currency' => 'NGN',
            'status' => $status,
            'redirect_to' => '/checkout',
            'meta' => [
                'tenant_id' => 'tenant-1',
                'escrow_id' => 'escrow-1',
                'payment_channel' => 'bank_transfer',
                'session_reference' => 'session-1',
            ],
        ]);
    }

    private function createAlatPayTransaction(Payment $payment, array $overrides = []): AlatPayTransaction
    {
        return AlatPayTransaction::query()->create(array_merge([
            'payment_id' => $payment->id,
            'user_id' => $payment->user_id,
            'combined_order_id' => $payment->combined_order_id,
            'reference' => 'KIDAN-ALAT-' . $payment->id,
            'transaction_id' => 'txn-default',
            'provider_reference' => 'sess-default',
            'provider_record_id' => 'provider-default',
            'merchant_id' => 'merchant_123',
            'order_code' => $payment->order_code,
            'order_identifier' => 'KIDAN-ALAT-' . $payment->id,
            'tenant_id' => 'tenant-1',
            'escrow_id' => 'escrow-1',
            'session_reference' => 'session-1',
            'payment_channel' => 'bank_transfer',
            'currency' => 'NGN',
            'amount' => $payment->amount,
            'environment' => 'sandbox',
            'status' => 'pending',
            'checkout_url' => 'https://example.com/checkout',
            'instructions' => ['account_number' => '1234567890'],
            'provider_payload' => ['status' => true],
            'metadata' => $payment->meta,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }
}
