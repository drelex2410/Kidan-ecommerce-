<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Mail\EmailManager;
use App\Models\AuthCode;
use App\Models\Setting;
use App\Models\ShopFollower;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PhaseTwoAuthTest extends TestCase
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

        $this->createPhaseTwoSchema();
        $this->seedSettings();
    }

    public function test_signup_success_returns_token_when_verification_is_disabled(): void
    {
        $response = $this->postJson('/api/v1/auth/signup', [
            'name' => 'Alex Doe',
            'email' => 'alex@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'device_name' => 'frontend-web',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('verified', true)
            ->assertJsonPath('user.email', 'alex@example.com')
            ->assertJsonPath('data.user.verification_status', 'verified')
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'user' => ['id', 'name', 'email', 'user_type'],
                'data' => ['token', 'user'],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'alex@example.com',
            'user_type' => 'customer',
        ]);
    }

    public function test_signup_validation_failure_is_machine_readable(): void
    {
        $response = $this->postJson('/api/v1/auth/signup', [
            'email' => 'not-an-email',
            'password' => '123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_login_success_returns_bearer_token_and_user_summary(): void
    {
        $user = $this->createUser([
            'email' => 'alex@example.com',
            'password' => Hash::make('secret123'),
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'alex@example.com',
            'password' => 'secret123',
            'form_type' => 'customer',
            'device_name' => 'frontend-web',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('verified', true)
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('data.user.verification_status', 'verified');

        $this->assertNotNull($response->json('access_token'));
        $this->assertSame(1, $user->fresh()->tokens()->count());
    }

    public function test_login_invalid_credentials_returns_explicit_failure(): void
    {
        $this->createUser([
            'email' => 'alex@example.com',
            'password' => Hash::make('secret123'),
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'alex@example.com',
            'password' => 'wrong-password',
            'form_type' => 'customer',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Invalid login information');
    }

    public function test_logout_revokes_only_the_current_token(): void
    {
        $user = $this->createUser([
            'email_verified_at' => now(),
        ]);

        $currentToken = $user->createToken('frontend-web')->plainTextToken;
        $user->createToken('mobile-app');

        $response = $this->withHeader('Authorization', 'Bearer ' . $currentToken)
            ->postJson('/api/v1/auth/logout');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Logged out successfully.');

        $this->assertSame(1, $user->fresh()->tokens()->count());
    }

    public function test_user_info_returns_frontend_hydration_payload(): void
    {
        $user = $this->createUser([
            'name' => 'Alex Doe',
            'email' => 'alex@example.com',
            'email_verified_at' => now(),
            'balance' => 12500.00,
            'club_points' => 40,
        ]);

        $shopId = DB::table('shops')->insertGetId([
            'user_id' => $user->id,
            'name' => 'Alex Shop',
            'slug' => 'alex-shop',
            'approval' => 1,
            'published' => 1,
            'min_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        ShopFollower::query()->create([
            'user_id' => $user->id,
            'shop_id' => $shopId,
        ]);

        DB::table('addresses')->insert([
            'user_id' => $user->id,
            'set_default' => 1,
            'default_shipping' => 1,
            'default_billing' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $token = $user->createToken('frontend-web')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/user/info');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.verification_status', 'verified')
            ->assertJsonPath('followed_shops.0', $shopId)
            ->assertJsonPath('permissions.wallet_system', true)
            ->assertJsonPath('permissions.club_point', false)
            ->assertJsonPath('data.is_authenticated', true)
            ->assertJsonPath('data.profile.has_default_shipping_address', true)
            ->assertJsonPath('data.profile.has_default_billing_address', true);
    }

    public function test_user_info_requires_bearer_token(): void
    {
        $this->getJson('/api/v1/user/info')
            ->assertUnauthorized();
    }

    public function test_verify_success_marks_user_verified_and_returns_token(): void
    {
        Mail::fake();
        $this->setSetting('customer_otp_with', 'email');

        $user = $this->createUser([
            'email' => 'alex@example.com',
            'email_verified_at' => null,
        ]);

        AuthCode::query()->create([
            'user_id' => $user->id,
            'purpose' => 'verification',
            'channel' => 'email',
            'target' => 'alex@example.com',
            'code' => '123456',
            'expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->postJson('/api/v1/auth/verify', [
            'email' => 'alex@example.com',
            'code' => '123456',
            'device_name' => 'frontend-web',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('verified', true)
            ->assertJsonPath('data.user.verification_status', 'verified');

        $this->assertNotNull($response->json('access_token'));
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_verify_failure_returns_explicit_error(): void
    {
        $this->setSetting('customer_otp_with', 'email');

        $user = $this->createUser([
            'email' => 'alex@example.com',
            'email_verified_at' => null,
        ]);

        AuthCode::query()->create([
            'user_id' => $user->id,
            'purpose' => 'verification',
            'channel' => 'email',
            'target' => 'alex@example.com',
            'code' => '123456',
            'expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->postJson('/api/v1/auth/verify', [
            'email' => 'alex@example.com',
            'code' => '654321',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Code does not match.');
    }

    public function test_resend_code_rotates_verification_code_and_returns_success(): void
    {
        Mail::fake();
        $this->setSetting('customer_otp_with', 'email');

        $user = $this->createUser([
            'email' => 'alex@example.com',
            'email_verified_at' => null,
            'verification_code' => '111111',
        ]);

        $response = $this->postJson('/api/v1/auth/resend-code', [
            'email' => 'alex@example.com',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('verified', false);

        $user->refresh();

        $this->assertNotSame('111111', $user->verification_code);
        $this->assertDatabaseHas('auth_codes', [
            'user_id' => $user->id,
            'purpose' => 'verification',
            'target' => 'alex@example.com',
        ]);
        Mail::assertQueued(EmailManager::class);
    }

    public function test_password_create_issues_reset_code(): void
    {
        Mail::fake();
        $user = $this->createUser([
            'email' => 'alex@example.com',
        ]);

        $response = $this->postJson('/api/v1/auth/password/create', [
            'email' => 'alex@example.com',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('email', true)
            ->assertJsonPath('phone', false);

        $this->assertDatabaseHas('auth_codes', [
            'user_id' => $user->id,
            'purpose' => 'password_reset',
            'target' => 'alex@example.com',
        ]);
        Mail::assertQueued(EmailManager::class);
    }

    public function test_password_reset_updates_password_and_invalidates_existing_tokens(): void
    {
        Mail::fake();
        $user = $this->createUser([
            'email' => 'alex@example.com',
            'password' => Hash::make('old-secret'),
        ]);
        $user->createToken('frontend-web');

        $this->postJson('/api/v1/auth/password/create', [
            'email' => 'alex@example.com',
        ])->assertOk();

        $code = AuthCode::query()
            ->where('purpose', 'password_reset')
            ->where('target', 'alex@example.com')
            ->latest('id')
            ->value('code');

        $response = $this->postJson('/api/v1/auth/password/reset', [
            'email' => 'alex@example.com',
            'code' => $code,
            'password' => 'new-secret',
            'password_confirmation' => 'new-secret',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Your password has been updated.');

        $this->assertTrue(Hash::check('new-secret', $user->fresh()->password));
        $this->assertSame(0, $user->fresh()->tokens()->count());
    }

    private function createPhaseTwoSchema(): void
    {
        Schema::dropIfExists('shop_followers');
        Schema::dropIfExists('addresses');
        Schema::dropIfExists('auth_codes');
        Schema::dropIfExists('shops');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('translations');
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable()->unique();
            $table->string('phone')->nullable()->unique();
            $table->string('avatar')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            $table->string('password');
            $table->string('verification_code')->nullable();
            $table->timestamp('verification_sent_at')->nullable();
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

        Schema::create('translations', function (Blueprint $table): void {
            $table->id();
            $table->string('lang', 10);
            $table->string('lang_key');
            $table->text('lang_value')->nullable();
            $table->timestamps();
            $table->index(['lang', 'lang_key']);
        });

        Schema::create('shops', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('approval')->default(false);
            $table->boolean('published')->default(false);
            $table->decimal('min_order', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('shop_followers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('shop_id');
            $table->timestamps();
            $table->unique(['user_id', 'shop_id']);
        });

        Schema::create('addresses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->boolean('set_default')->default(false);
            $table->boolean('default_shipping')->default(false);
            $table->boolean('default_billing')->default(false);
            $table->timestamps();
        });

        Schema::create('auth_codes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('purpose');
            $table->string('channel');
            $table->string('target');
            $table->string('code', 10);
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });
    }

    private function seedSettings(): void
    {
        Setting::query()->insert([
            [
                'type' => 'customer_login_with',
                'value' => 'email',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'customer_otp_with',
                'value' => 'disabled',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'conversation_system',
                'value' => '1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'wallet_system',
                'value' => '1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'club_point',
                'value' => '0',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        Cache::flush();
    }

    private function setSetting(string $type, string $value): void
    {
        Setting::query()->updateOrCreate(
            ['type' => $type],
            ['value' => $value]
        );

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
}
