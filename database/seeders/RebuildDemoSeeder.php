<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class RebuildDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedSettings();
        $this->seedGeo();
        $users = $this->seedUsers();
        $shopId = $this->seedShop($users['seller_id'] ?? null);
        $this->seedCatalog($shopId);
        $this->seedContent();
        $this->seedAccountData($users['customer_id'] ?? null, $shopId);
        $this->seedPayments();
    }

    private function seedSettings(): void
    {
        if (!Schema::hasTable('settings')) {
            return;
        }

        $settings = [
            'site_name' => 'Kidan Store',
            'meta_title' => 'Kidan Store',
            'meta_description' => 'API-first rebuilt storefront demo data',
            'meta_keywords' => 'kidan, ecommerce, rebuild',
            'paypal_payment' => '1',
            'stripe_payment' => '1',
            'paystack_payment' => '1',
            'flutterwave_payment' => '1',
            'offline_payment' => '1',
            'wallet_system' => '1',
            'club_point' => '1',
            'affiliate_system' => '1',
            'conversation_system' => '1',
            'delivery_boy' => '1',
            'pickup_point' => '1',
            'customer_login_with' => 'email',
            'customer_otp_with' => 'email',
            'system_default_currency' => '1',
            'decimal_separator' => '1',
            'no_of_decimals' => '2',
            'symbol_format' => '1',
            'truncate_price' => '0',
            'refund_request_time_period' => '7',
            'refund_request_order_status' => json_encode(['delivered']),
            'refund_reason_types' => json_encode(['Damaged item', 'Wrong item']),
        ];

        foreach ($settings as $type => $value) {
            DB::table('settings')->updateOrInsert(
                ['type' => $type],
                ['value' => $value, 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }

    private function seedGeo(): void
    {
        if (Schema::hasTable('currencies')) {
            DB::table('currencies')->updateOrInsert(
                ['id' => 1],
                $this->filterColumns('currencies', [
                    'id' => 1,
                    'name' => 'US Dollar',
                    'symbol' => '$',
                    'code' => 'USD',
                    'exchange_rate' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        if (Schema::hasTable('languages')) {
            DB::table('languages')->updateOrInsert(
                ['code' => 'en'],
                $this->filterColumns('languages', [
                    'name' => 'English',
                    'code' => 'en',
                    'flag' => 'en',
                    'rtl' => 0,
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        if (Schema::hasTable('countries')) {
            DB::table('countries')->updateOrInsert(
                ['code' => 'US'],
                $this->filterColumns('countries', [
                    'name' => 'United States',
                    'code' => 'US',
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }

    private function seedUsers(): array
    {
        if (!Schema::hasTable('users')) {
            return [];
        }

        $password = Hash::make('secret123');

        $adminId = $this->upsertUser('admin@kidanstore.test', 'Kidan Admin', 'admin', $password);
        $sellerId = $this->upsertUser('seller@kidanstore.test', 'Kidan Seller', 'seller', $password);
        $customerId = $this->upsertUser('customer@kidanstore.test', 'Kidan Customer', 'customer', $password);
        $deliveryBoyId = $this->upsertUser('delivery@kidanstore.test', 'Kidan Delivery', 'delivery_boy', $password);

        return [
            'admin_id' => $adminId,
            'seller_id' => $sellerId,
            'customer_id' => $customerId,
            'delivery_boy_id' => $deliveryBoyId,
        ];
    }

    private function seedShop(?int $sellerId): ?int
    {
        if (!$sellerId || !Schema::hasTable('shops')) {
            return null;
        }

        $data = $this->filterColumns('shops', [
            'user_id' => $sellerId,
            'name' => 'Kidan Demo Shop',
            'slug' => 'kidan-demo-shop',
            'rating' => 5,
            'num_of_products' => 1,
            'published' => 1,
            'approval' => 1,
            'verification_status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('shops')->updateOrInsert(['slug' => 'kidan-demo-shop'], $data);

        return (int) DB::table('shops')->where('slug', 'kidan-demo-shop')->value('id');
    }

    private function seedCatalog(?int $shopId): void
    {
        if (!$shopId || !Schema::hasTable('products')) {
            return;
        }

        $productData = $this->filterColumns('products', [
            'shop_id' => $shopId,
            'name' => 'Rebuild Demo Product',
            'slug' => 'rebuild-demo-product',
            'unit_price' => 120,
            'lowest_price' => 120,
            'highest_price' => 120,
            'min_qty' => 1,
            'max_qty' => 5,
            'digital' => 0,
            'published' => 1,
            'approved' => 1,
            'stock_visibility_state' => 1,
            'current_stock' => 25,
            'num_of_sale' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('products')->updateOrInsert(['slug' => 'rebuild-demo-product'], $productData);
        $productId = (int) DB::table('products')->where('slug', 'rebuild-demo-product')->value('id');

        if (Schema::hasTable('product_translations')) {
            DB::table('product_translations')->updateOrInsert(
                ['product_id' => $productId, 'lang' => 'en'],
                $this->filterColumns('product_translations', [
                    'product_id' => $productId,
                    'lang' => 'en',
                    'name' => 'Rebuild Demo Product',
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        if (Schema::hasTable('product_variations')) {
            DB::table('product_variations')->updateOrInsert(
                ['product_id' => $productId, 'code' => 'REBUILD-DEMO-001'],
                $this->filterColumns('product_variations', [
                    'product_id' => $productId,
                    'code' => 'REBUILD-DEMO-001',
                    'price' => 120,
                    'stock' => 25,
                    'current_stock' => 25,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }

    private function seedContent(): void
    {
        if (Schema::hasTable('pages')) {
            DB::table('pages')->updateOrInsert(
                ['slug' => 'return-policy'],
                $this->filterColumns('pages', [
                    'title' => 'Return Policy',
                    'slug' => 'return-policy',
                    'content' => '<p>Demo return policy content.</p>',
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        if (Schema::hasTable('blog_categories')) {
            DB::table('blog_categories')->updateOrInsert(
                ['slug' => 'news'],
                $this->filterColumns('blog_categories', [
                    'category_name' => 'News',
                    'slug' => 'news',
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        if (Schema::hasTable('blogs')) {
            $categoryId = Schema::hasTable('blog_categories')
                ? DB::table('blog_categories')->where('slug', 'news')->value('id')
                : null;

            DB::table('blogs')->updateOrInsert(
                ['slug' => 'rebuild-demo-journal-entry'],
                $this->filterColumns('blogs', [
                    'blog_category_id' => $categoryId,
                    'title' => 'Rebuild Demo Journal Entry',
                    'slug' => 'rebuild-demo-journal-entry',
                    'short_description' => 'Demo journal entry for local rebuild verification.',
                    'description' => '<p>This is demo journal content for local verification.</p>',
                    'status' => 1,
                    'published_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }

    private function seedAccountData(?int $customerId, ?int $shopId): void
    {
        if (!$customerId) {
            return;
        }

        if (Schema::hasTable('addresses')) {
            DB::table('addresses')->updateOrInsert(
                ['user_id' => $customerId, 'address' => '123 Demo Street'],
                $this->filterColumns('addresses', [
                    'user_id' => $customerId,
                    'address' => '123 Demo Street',
                    'country' => 'US',
                    'postal_code' => '10001',
                    'phone' => '+1234567890',
                    'default_shipping' => 1,
                    'default_billing' => 1,
                    'set_default' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        if ($shopId && Schema::hasTable('combined_orders') && Schema::hasTable('orders')) {
            DB::table('combined_orders')->updateOrInsert(
                ['code' => 'DEMO-ORDER-1001'],
                $this->filterColumns('combined_orders', [
                    'user_id' => $customerId,
                    'code' => 'DEMO-ORDER-1001',
                    'grand_total' => 120,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );

            $combinedOrderId = (int) DB::table('combined_orders')->where('code', 'DEMO-ORDER-1001')->value('id');

            DB::table('orders')->updateOrInsert(
                ['combined_order_id' => $combinedOrderId, 'code' => '1'],
                $this->filterColumns('orders', [
                    'user_id' => $customerId,
                    'shop_id' => $shopId,
                    'combined_order_id' => $combinedOrderId,
                    'code' => '1',
                    'grand_total' => 120,
                    'payment_type' => 'cash_on_delivery',
                    'payment_status' => 'unpaid',
                    'delivery_status' => 'order_placed',
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        if (Schema::hasTable('wallets')) {
            DB::table('wallets')->updateOrInsert(
                ['user_id' => $customerId, 'details' => 'Demo wallet top up'],
                $this->filterColumns('wallets', [
                    'user_id' => $customerId,
                    'amount' => 50,
                    'payment_method' => 'stripe',
                    'details' => 'Demo wallet top up',
                    'type' => 'Added',
                    'approval' => 1,
                    'offline_payment' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }

    private function seedPayments(): void
    {
        if (Schema::hasTable('manual_payment_methods')) {
            DB::table('manual_payment_methods')->updateOrInsert(
                ['id' => 1],
                $this->filterColumns('manual_payment_methods', [
                    'id' => 1,
                    'heading' => 'Bank Transfer',
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }

    private function upsertUser(string $email, string $name, string $userType, string $password): int
    {
        DB::table('users')->updateOrInsert(
            ['email' => $email],
            $this->filterColumns('users', [
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'user_type' => $userType,
                'email_verified_at' => now(),
                'balance' => $userType === 'customer' ? 50 : 0,
                'created_at' => now(),
                'updated_at' => now(),
            ])
        );

        return (int) DB::table('users')->where('email', $email)->value('id');
    }

    private function filterColumns(string $table, array $data): array
    {
        if (!Schema::hasTable($table)) {
            return [];
        }

        $columns = Schema::getColumnListing($table);

        return array_intersect_key($data, array_flip($columns));
    }
}
