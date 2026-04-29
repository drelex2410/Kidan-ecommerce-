<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('products') || Schema::hasColumn('products', 'refund_policy_id')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('refund_policy_id')
                ->nullable()
                ->after('brand_id')
                ->constrained('refund_policies')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('products') || !Schema::hasColumn('products', 'refund_policy_id')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('refund_policy_id');
        });
    }
};
