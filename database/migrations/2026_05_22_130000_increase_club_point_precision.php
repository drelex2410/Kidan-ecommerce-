<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (Schema::hasColumn('users', 'club_points')) {
            DB::statement('ALTER TABLE users MODIFY club_points DECIMAL(20,6) NOT NULL DEFAULT 0');
        }

        if (Schema::hasColumn('products', 'earn_point')) {
            DB::statement('ALTER TABLE products MODIFY earn_point DECIMAL(20,6) NOT NULL DEFAULT 0');
        }

        if (Schema::hasColumn('club_points', 'points')) {
            DB::statement('ALTER TABLE club_points MODIFY points DECIMAL(20,6) NOT NULL DEFAULT 0');
        }

        if (Schema::hasColumn('club_point_details', 'point')) {
            DB::statement('ALTER TABLE club_point_details MODIFY point DECIMAL(20,6) NOT NULL DEFAULT 0');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (Schema::hasColumn('users', 'club_points')) {
            DB::statement('ALTER TABLE users MODIFY club_points DECIMAL(12,3) NOT NULL DEFAULT 0');
        }

        if (Schema::hasColumn('products', 'earn_point')) {
            DB::statement('ALTER TABLE products MODIFY earn_point DOUBLE(8,2) NOT NULL DEFAULT 0');
        }

        if (Schema::hasColumn('club_points', 'points')) {
            DB::statement('ALTER TABLE club_points MODIFY points DOUBLE(18,2) NOT NULL DEFAULT 0');
        }

        if (Schema::hasColumn('club_point_details', 'point')) {
            DB::statement('ALTER TABLE club_point_details MODIFY point DOUBLE(8,2) NOT NULL DEFAULT 0');
        }
    }
};
