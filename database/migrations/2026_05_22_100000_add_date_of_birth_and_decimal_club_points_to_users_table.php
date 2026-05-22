<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (!Schema::hasColumn('users', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable()->after('phone');
            }
        });

        if (!Schema::hasColumn('users', 'club_points')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE users MODIFY club_points DECIMAL(12,3) NOT NULL DEFAULT 0');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql' && Schema::hasColumn('users', 'club_points')) {
            DB::statement('ALTER TABLE users MODIFY club_points INT UNSIGNED NOT NULL DEFAULT 0');
        }

        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'date_of_birth')) {
                $table->dropColumn('date_of_birth');
            }
        });
    }
};
