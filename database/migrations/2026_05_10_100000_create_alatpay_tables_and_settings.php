<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('alatpay_transactions')) {
            Schema::create('alatpay_transactions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
                $table->unsignedInteger('user_id')->nullable();
                $table->integer('combined_order_id')->nullable();
                $table->string('reference')->unique();
                $table->string('transaction_id')->nullable()->index();
                $table->string('provider_reference')->nullable()->index();
                $table->string('provider_record_id')->nullable()->index();
                $table->string('merchant_id')->nullable()->index();
                $table->string('order_code')->nullable()->index();
                $table->string('order_identifier')->nullable()->index();
                $table->string('tenant_id')->nullable()->index();
                $table->string('escrow_id')->nullable()->index();
                $table->string('session_reference')->nullable()->index();
                $table->string('payment_channel')->default('bank_transfer')->index();
                $table->string('currency', 10)->default('NGN')->index();
                $table->decimal('amount', 20, 2)->default(0);
                $table->string('environment', 40)->default('sandbox')->index();
                $table->string('status', 40)->default('pending')->index();
                $table->string('checkout_url')->nullable();
                $table->timestamp('expires_at')->nullable()->index();
                $table->timestamp('completed_at')->nullable()->index();
                $table->timestamp('failed_at')->nullable();
                $table->timestamp('last_reconciled_at')->nullable();
                $table->json('instructions')->nullable();
                $table->json('provider_payload')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['payment_id', 'status']);
                $table->index(['user_id', 'status']);
                $table->index(['order_code', 'status']);

                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('combined_order_id')->references('id')->on('combined_orders')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('alatpay_webhook_logs')) {
            Schema::create('alatpay_webhook_logs', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('alatpay_transaction_id')->nullable()->constrained('alatpay_transactions')->nullOnDelete();
                $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
                $table->string('tenant_id')->nullable()->index();
                $table->uuid('correlation_id')->index();
                $table->string('event_type')->nullable()->index();
                $table->string('reference')->nullable()->index();
                $table->string('provider_reference')->nullable()->index();
                $table->string('fingerprint')->unique();
                $table->string('signature')->nullable();
                $table->string('timestamp_header')->nullable();
                $table->string('status', 40)->default('received')->index();
                $table->unsignedSmallInteger('attempts')->default(0);
                $table->json('headers')->nullable();
                $table->json('payload')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamp('received_at')->nullable()->index();
                $table->timestamp('processed_at')->nullable()->index();
                $table->timestamps();

                $table->index(['event_type', 'status']);
            });
        }

        if (!Schema::hasTable('alatpay_reconciliation_logs')) {
            Schema::create('alatpay_reconciliation_logs', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('alatpay_transaction_id')->nullable()->constrained('alatpay_transactions')->nullOnDelete();
                $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
                $table->string('tenant_id')->nullable()->index();
                $table->uuid('correlation_id')->nullable()->index();
                $table->string('reference')->nullable()->index();
                $table->string('provider_reference')->nullable()->index();
                $table->string('action', 80)->index();
                $table->string('status', 40)->default('queued')->index();
                $table->string('response_code')->nullable();
                $table->string('message')->nullable();
                $table->unsignedInteger('latency_ms')->nullable();
                $table->unsignedSmallInteger('attempts')->default(0);
                $table->timestamp('reconciled_at')->nullable()->index();
                $table->timestamp('next_retry_at')->nullable()->index();
                $table->json('payload')->nullable();
                $table->timestamps();

                $table->index(['action', 'status']);
            });
        }

        if (!Schema::hasTable('alatpay_refunds')) {
            Schema::create('alatpay_refunds', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
                $table->foreignId('alatpay_transaction_id')->nullable()->constrained('alatpay_transactions')->nullOnDelete();
                $table->unsignedInteger('refund_request_id')->nullable()->index();
                $table->unsignedInteger('requested_by')->nullable();
                $table->string('reference')->unique();
                $table->string('provider_reference')->nullable()->index();
                $table->string('tenant_id')->nullable()->index();
                $table->string('order_code')->nullable()->index();
                $table->decimal('amount', 20, 2)->default(0);
                $table->string('currency', 10)->default('NGN')->index();
                $table->string('status', 40)->default('pending')->index();
                $table->string('reason')->nullable();
                $table->text('admin_notes')->nullable();
                $table->timestamp('requested_at')->nullable()->index();
                $table->timestamp('completed_at')->nullable()->index();
                $table->timestamp('failed_at')->nullable();
                $table->json('provider_payload')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['payment_id', 'status']);

                $table->foreign('requested_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        $now = now();
        $defaults = [
            ['type' => 'alatpay_payment', 'value' => '0', 'created_at' => $now, 'updated_at' => $now],
            ['type' => 'alatpay_env', 'value' => 'sandbox', 'created_at' => $now, 'updated_at' => $now],
            ['type' => 'alatpay_base_url', 'value' => 'https://wema-alatdev-apimgt.developer.azure-api.net', 'created_at' => $now, 'updated_at' => $now],
            ['type' => 'alatpay_callback_url', 'value' => '', 'created_at' => $now, 'updated_at' => $now],
            ['type' => 'alatpay_supported_currencies', 'value' => json_encode(['NGN']), 'created_at' => $now, 'updated_at' => $now],
            ['type' => 'alatpay_charge_type', 'value' => 'percentage', 'created_at' => $now, 'updated_at' => $now],
            ['type' => 'alatpay_charge_flat', 'value' => '0', 'created_at' => $now, 'updated_at' => $now],
            ['type' => 'alatpay_charge_percent', 'value' => '0', 'created_at' => $now, 'updated_at' => $now],
        ];

        foreach ($defaults as $default) {
            if (!DB::table('settings')->where('type', $default['type'])->exists()) {
                DB::table('settings')->insert($default);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('alatpay_refunds');
        Schema::dropIfExists('alatpay_reconciliation_logs');
        Schema::dropIfExists('alatpay_webhook_logs');
        Schema::dropIfExists('alatpay_transactions');

        DB::table('settings')->whereIn('type', [
            'alatpay_payment',
            'alatpay_env',
            'alatpay_base_url',
            'alatpay_callback_url',
            'alatpay_supported_currencies',
            'alatpay_charge_type',
            'alatpay_charge_flat',
            'alatpay_charge_percent',
        ])->delete();
    }
};
