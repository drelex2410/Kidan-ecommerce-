<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('uploads')) {
            return;
        }

        Schema::table('uploads', function (Blueprint $table) {
            if (!Schema::hasColumn('uploads', 'processing_status')) {
                $table->string('processing_status', 30)->default('ready')->after('file_size');
            }

            if (!Schema::hasColumn('uploads', 'processing_error')) {
                $table->text('processing_error')->nullable()->after('processing_status');
            }

            if (!Schema::hasColumn('uploads', 'file_hash')) {
                $table->string('file_hash', 64)->nullable()->after('processing_error');
            }

            if (!Schema::hasColumn('uploads', 'mime_type')) {
                $table->string('mime_type', 191)->nullable()->after('file_hash');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('uploads')) {
            return;
        }

        Schema::table('uploads', function (Blueprint $table) {
            foreach (['mime_type', 'file_hash', 'processing_error', 'processing_status'] as $column) {
                if (Schema::hasColumn('uploads', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
