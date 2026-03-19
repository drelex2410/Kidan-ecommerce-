<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('products') || Schema::hasColumn('products', 'today_deal')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->boolean('today_deal')->default(false)->after('approved');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('products') || !Schema::hasColumn('products', 'today_deal')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('today_deal');
        });
    }
};
