<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('refund_policies')) {
            return;
        }

        Schema::create('refund_policies', function (Blueprint $table) {
            $table->charset = 'utf8mb3';
            $table->collation = 'utf8mb3_unicode_ci';

            $table->id();
            $table->string('name');
            $table->string('code')->nullable()->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('refund_window_days')->default(0);
            $table->longText('allowed_order_statuses')->nullable();
            $table->boolean('allow_partial_refund')->default(false);
            $table->boolean('refund_shipping_fee')->default(false);
            $table->boolean('requires_admin_approval')->default(true);
            $table->boolean('requires_reason')->default(true);
            $table->boolean('requires_evidence')->default(false);
            $table->boolean('exclude_opened_items')->default(false);
            $table->boolean('exclude_digital_products')->default(false);
            $table->boolean('exclude_discounted_products')->default(false);
            $table->string('refund_method_type', 50)->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refund_policies');
    }
};
