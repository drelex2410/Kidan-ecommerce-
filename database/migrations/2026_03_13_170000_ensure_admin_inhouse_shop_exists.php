<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users') || !Schema::hasTable('shops')) {
            return;
        }

        $admin = DB::table('users')
            ->where('user_type', 'admin')
            ->orderBy('id')
            ->first();

        if (!$admin) {
            return;
        }

        $shop = DB::table('shops')
            ->where('user_id', $admin->id)
            ->orderBy('id')
            ->first();

        if (!$shop) {
            $shopId = DB::table('shops')->insertGetId($this->newAdminShopPayload($admin->id));
        } else {
            $shopId = $shop->id;
        }

        DB::table('users')
            ->whereIn('user_type', ['admin', 'staff'])
            ->update(['shop_id' => $shopId]);

        $this->backfillShopAssignments('products', $shopId);
        $this->backfillShopAssignments('reviews', $shopId);
        $this->backfillShopAssignments('coupons', $shopId);
    }

    public function down(): void
    {
        //
    }

    private function newAdminShopPayload(int $adminId): array
    {
        $name = trim((string) config('app.name'));
        $name = $name !== '' ? $name : 'Inhouse Shop';
        $payload = [
            'user_id' => $adminId,
            'approval' => 1,
            'published' => 1,
            'name' => $name,
            'slug' => $this->generateUniqueSlug($name),
            'min_order' => 0,
        ];

        if (Schema::hasColumn('shops', 'verification_status')) {
            $payload['verification_status'] = 1;
        }

        if (Schema::hasColumn('shops', 'created_at')) {
            $payload['created_at'] = now();
        }

        if (Schema::hasColumn('shops', 'updated_at')) {
            $payload['updated_at'] = now();
        }

        return $payload;
    }

    private function backfillShopAssignments(string $table, int $shopId): void
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'shop_id')) {
            return;
        }

        DB::table($table)
            ->whereNull('shop_id')
            ->update(['shop_id' => $shopId]);
    }

    private function generateUniqueSlug(string $name): string
    {
        $slugBase = Str::slug($name, '-');
        $slugBase = $slugBase !== '' ? $slugBase : 'inhouse-shop';
        $slug = $slugBase;
        $counter = 2;

        while (DB::table('shops')->where('slug', $slug)->exists()) {
            $slug = $slugBase . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
};
