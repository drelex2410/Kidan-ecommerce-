<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable()->unique()->after('email');
            }
            if (!Schema::hasColumn('users', 'avatar')) {
                $table->string('avatar')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('users', 'phone_verified_at')) {
                $table->timestamp('phone_verified_at')->nullable()->after('email_verified_at');
            }
            if (!Schema::hasColumn('users', 'verification_code')) {
                $table->string('verification_code', 10)->nullable()->after('remember_token');
            }
            if (!Schema::hasColumn('users', 'verification_sent_at')) {
                $table->timestamp('verification_sent_at')->nullable()->after('verification_code');
            }
            if (!Schema::hasColumn('users', 'user_type')) {
                $table->string('user_type')->default('customer')->after('password');
            }
            if (!Schema::hasColumn('users', 'banned')) {
                $table->boolean('banned')->default(false)->after('user_type');
            }
            if (!Schema::hasColumn('users', 'balance')) {
                $table->decimal('balance', 12, 2)->default(0)->after('banned');
            }
            if (!Schema::hasColumn('users', 'club_points')) {
                $table->unsignedInteger('club_points')->default(0)->after('balance');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            foreach ([
                'club_points',
                'balance',
                'banned',
                'user_type',
                'verification_sent_at',
                'verification_code',
                'phone_verified_at',
                'avatar',
                'phone',
            ] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
