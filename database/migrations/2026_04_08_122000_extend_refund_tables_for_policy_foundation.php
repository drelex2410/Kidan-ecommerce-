<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('refund_requests')) {
            Schema::table('refund_requests', function (Blueprint $table) {
                if (!Schema::hasColumn('refund_requests', 'status')) {
                    $table->string('status', 30)->default('pending')->after('payment_type')->index();
                }

                if (!Schema::hasColumn('refund_requests', 'admin_notes')) {
                    $table->longText('admin_notes')->nullable()->after('refund_note');
                }

                if (!Schema::hasColumn('refund_requests', 'policy_snapshot')) {
                    $table->longText('policy_snapshot')->nullable()->after('admin_notes');
                }

                if (!Schema::hasColumn('refund_requests', 'requested_at')) {
                    $table->timestamp('requested_at')->nullable()->after('updated_at');
                }

                if (!Schema::hasColumn('refund_requests', 'reviewed_at')) {
                    $table->timestamp('reviewed_at')->nullable()->after('requested_at');
                }

                if (!Schema::hasColumn('refund_requests', 'reviewed_by')) {
                    $table->unsignedInteger('reviewed_by')->nullable()->after('reviewed_at')->index();
                }
            });

            DB::statement("
                UPDATE refund_requests
                SET requested_at = COALESCE(requested_at, created_at)
                WHERE requested_at IS NULL
            ");

            DB::statement("
                UPDATE refund_requests
                SET status = CASE
                    WHEN admin_approval = 1 THEN 'approved'
                    WHEN admin_approval = 2 THEN 'rejected'
                    WHEN seller_approval = 1 THEN 'under_review'
                    ELSE 'pending'
                END
            ");

            DB::statement("
                UPDATE refund_requests
                SET reviewed_at = COALESCE(reviewed_at, updated_at)
                WHERE reviewed_at IS NULL
                  AND admin_approval IN (1, 2)
            ");
        }

        if (Schema::hasTable('refund_request_items')) {
            Schema::table('refund_request_items', function (Blueprint $table) {
                if (!Schema::hasColumn('refund_request_items', 'product_id')) {
                    $table->bigInteger('product_id')->nullable()->after('order_detail_id');
                    $table->index('product_id', 'refund_request_items_product_id_index');
                }

                if (!Schema::hasColumn('refund_request_items', 'quantity_requested')) {
                    $table->integer('quantity_requested')->nullable()->after('quantity');
                }

                if (!Schema::hasColumn('refund_request_items', 'quantity_approved')) {
                    $table->integer('quantity_approved')->nullable()->after('quantity_requested');
                }

                if (!Schema::hasColumn('refund_request_items', 'applied_refund_policy_id')) {
                    $table->unsignedBigInteger('applied_refund_policy_id')->nullable()->after('product_id');
                    $table->index('applied_refund_policy_id', 'refund_request_items_policy_id_index');
                }

                if (!Schema::hasColumn('refund_request_items', 'item_status')) {
                    $table->string('item_status', 30)->default('pending')->after('quantity_approved')->index();
                }

                if (!Schema::hasColumn('refund_request_items', 'rejection_reason')) {
                    $table->longText('rejection_reason')->nullable()->after('item_status');
                }
            });

            DB::statement("
                UPDATE refund_request_items
                SET quantity_requested = COALESCE(quantity_requested, quantity)
                WHERE quantity_requested IS NULL
            ");

            if (Schema::hasTable('order_details')) {
                DB::statement("
                    UPDATE refund_request_items AS refund_items
                    INNER JOIN order_details AS order_details
                        ON order_details.id = refund_items.order_detail_id
                    SET refund_items.product_id = order_details.product_id
                    WHERE refund_items.product_id IS NULL
                ");
            }

            if (Schema::hasTable('refund_requests')) {
                DB::statement("
                    UPDATE refund_request_items AS refund_items
                    INNER JOIN refund_requests AS refund_requests
                        ON refund_requests.id = refund_items.refund_request_id
                    SET refund_items.item_status = CASE
                        WHEN refund_requests.admin_approval = 1 THEN 'approved'
                        WHEN refund_requests.admin_approval = 2 THEN 'rejected'
                        WHEN refund_requests.seller_approval = 1 THEN 'under_review'
                        ELSE 'pending'
                    END
                ");

                DB::statement("
                    UPDATE refund_request_items AS refund_items
                    INNER JOIN refund_requests AS refund_requests
                        ON refund_requests.id = refund_items.refund_request_id
                    SET refund_items.quantity_approved = COALESCE(refund_items.quantity_requested, refund_items.quantity)
                    WHERE refund_items.quantity_approved IS NULL
                      AND refund_requests.admin_approval = 1
                ");
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('refund_request_items')) {
            Schema::table('refund_request_items', function (Blueprint $table) {
                $columns = [
                    'product_id',
                    'quantity_requested',
                    'quantity_approved',
                    'applied_refund_policy_id',
                    'item_status',
                    'rejection_reason',
                ];

                $existingColumns = array_values(array_filter($columns, fn ($column) => Schema::hasColumn('refund_request_items', $column)));

                if ($existingColumns !== []) {
                    $table->dropColumn($existingColumns);
                }
            });
        }

        if (Schema::hasTable('refund_requests')) {
            Schema::table('refund_requests', function (Blueprint $table) {
                $columns = [
                    'status',
                    'admin_notes',
                    'policy_snapshot',
                    'requested_at',
                    'reviewed_at',
                    'reviewed_by',
                ];

                $existingColumns = array_values(array_filter($columns, fn ($column) => Schema::hasColumn('refund_requests', $column)));

                if ($existingColumns !== []) {
                    $table->dropColumn($existingColumns);
                }
            });
        }
    }
};
