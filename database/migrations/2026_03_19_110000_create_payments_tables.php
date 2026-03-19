<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->id();
                $this->addNullableForeignKey($table, 'user_id', 'users');
                $this->addNullableForeignKey($table, 'combined_order_id', 'combined_orders');
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

    private function addNullableForeignKey(
        Blueprint $table,
        string $column,
        string $parentTable,
        string $parentColumn = 'id'
    ): void {
        $type = $this->columnType($parentTable, $parentColumn);

        match (true) {
            str_contains($type, 'bigint') && str_contains($type, 'unsigned') => $table->unsignedBigInteger($column)->nullable(),
            str_contains($type, 'bigint') => $table->bigInteger($column)->nullable(),
            str_contains($type, 'int') && str_contains($type, 'unsigned') => $table->unsignedInteger($column)->nullable(),
            str_contains($type, 'int') => $table->integer($column)->nullable(),
            default => $table->unsignedBigInteger($column)->nullable(),
        };

        $table->foreign($column)
            ->references($parentColumn)
            ->on($parentTable)
            ->nullOnDelete();
    }

    private function columnType(string $table, string $column): string
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return 'bigint unsigned';
        }

        $result = DB::selectOne(sprintf(
            "SHOW COLUMNS FROM `%s` LIKE ?",
            str_replace('`', '``', $table)
        ), [$column]);

        return strtolower((string) ($result->Type ?? 'bigint unsigned'));
    }
};
