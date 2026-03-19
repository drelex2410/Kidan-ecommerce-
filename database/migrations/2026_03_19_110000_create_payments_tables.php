<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('combined_order_id')->nullable()->constrained('combined_orders')->nullOnDelete();
                $table->string('gateway');
                $table->string('payment_type');
                $table->string('payment_method');
                $table->string('order_code')->nullable()->index();
                $table->decimal('amount', 20, 2)->default(0);
                $table->string('currency', 10)->nullable();
                $table->string('status')->default('initiated')->index();
                $table->string('redirect_to')->nullable();
                $table->json('meta')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('failed_at')->nullable();
                $table->timestamps();

                $table->index(['gateway', 'payment_type']);
            });
        }

        if (!Schema::hasTable('payment_transactions')) {
            Schema::create('payment_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
                $table->string('gateway');
                $table->string('event_type');
                $table->string('reference')->nullable()->index();
                $table->string('status')->index();
                $table->string('fingerprint')->unique();
                $table->json('payload')->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->timestamps();

                $table->index(['payment_id', 'event_type']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('payments');
    }
};
