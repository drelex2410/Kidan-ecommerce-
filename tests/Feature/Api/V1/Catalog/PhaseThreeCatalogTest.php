<?php

namespace Tests\Feature\Api\V1\Catalog;

use App\Models\Product;
use App\Models\ProductVariation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PhaseThreeCatalogTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        DB::reconnect('sqlite');

        $this->createCatalogSchema();
        $this->seedSettings();
    }

    public function test_product_listing_returns_rich_contract(): void
    {
        [$category, $brand, $product] = $this->seedCatalog();

        $response = $this->getJson('/api/v1/product/search?category_slug=shirts&brand_ids=' . $brand->id);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('products.data.0.slug', 'linen-shirt')
            ->assertJsonPath('attributes.data.0.name', 'Size')
            ->assertJsonPath('allBrands.data.0.slug', 'kidan-brand')
            ->assertJsonPath('currentCategory.slug', 'shirts')
            ->assertJsonPath('rootCategories.data.0.slug', 'shirts')
            ->assertJsonPath('total', 1)
            ->assertJsonPath('seo.title', 'Shirts Meta');
    }

    public function test_product_listing_supports_keyword_and_pagination_metadata(): void
    {
        $this->seedCatalog();

        $response = $this->getJson('/api/v1/product/search?keyword=linen&sort_by=latest&page=1');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('products.data.0.slug', 'linen-shirt')
            ->assertJsonPath('currentPage', 1)
            ->assertJsonPath('totalPage', 1);
    }

    public function test_product_details_returns_variant_aware_payload(): void
    {
        [, , $product] = $this->seedCatalog();

        $response = $this->getJson('/api/v1/product/details/' . $product->slug);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.slug', 'linen-shirt')
            ->assertJsonPath('data.variation_options.0.name', 'Size')
            ->assertJsonPath('data.variations.0.current_stock', 7)
            ->assertJsonPath('data.review_summary.total_count', 1)
            ->assertJsonPath('data.min_qty', 1)
            ->assertJsonPath('data.max_qty', 5);
    }

    public function test_product_details_returns_404_for_invalid_slug(): void
    {
        $this->getJson('/api/v1/product/details/missing-product')
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_related_products_endpoint_returns_catalog_cards(): void
    {
        [, , $product, $relatedProduct] = $this->seedCatalog(true);

        $response = $this->getJson('/api/v1/product/related/' . $product->id);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.slug', $relatedProduct->slug);
    }

    public function test_bought_together_products_endpoint_returns_catalog_cards(): void
    {
        [, , $product, $relatedProduct] = $this->seedCatalog(true);

        DB::table('order_details')->insert([
            ['order_id' => 100, 'product_id' => $product->id],
            ['order_id' => 100, 'product_id' => $relatedProduct->id],
        ]);

        $response = $this->getJson('/api/v1/product/bought-together/' . $product->id);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.slug', $relatedProduct->slug);
    }

    public function test_random_products_endpoint_returns_public_products(): void
    {
        $this->seedCatalog(true);

        $response = $this->getJson('/api/v1/product/random/2');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_latest_products_endpoint_returns_latest_public_products(): void
    {
        $this->seedCatalog(true);

        $response = $this->getJson('/api/v1/product/latest/2');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_all_categories_endpoint_returns_root_categories_with_children(): void
    {
        $this->seedCatalog();

        $response = $this->getJson('/api/v1/all-categories');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.slug', 'shirts')
            ->assertJsonPath('data.0.children.data.0.slug', 'linen-shirts');
    }

    public function test_first_level_categories_endpoint_returns_root_only(): void
    {
        $this->seedCatalog();

        $response = $this->getJson('/api/v1/categories/first-level');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.slug', 'shirts');
    }

    public function test_all_brands_endpoint_returns_brand_cards(): void
    {
        $this->seedCatalog();

        $response = $this->getJson('/api/v1/all-brands');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.slug', 'kidan-brand');
    }

    public function test_all_offers_endpoint_returns_active_offers(): void
    {
        $this->seedCatalog();
        $offer = $this->seedOffer();

        $response = $this->getJson('/api/v1/all-offers');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.slug', $offer->slug);
    }

    public function test_offer_by_slug_endpoint_returns_products(): void
    {
        [, , $product] = $this->seedCatalog();
        $offer = $this->seedOffer($product);

        $response = $this->getJson('/api/v1/offer/' . $offer->slug);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.slug', $offer->slug)
            ->assertJsonPath('data.products.data.0.slug', $product->slug);
    }

    public function test_ajax_search_endpoint_returns_keyword_suggestions_and_products(): void
    {
        [, , $product] = $this->seedCatalog();

        $response = $this->getJson('/api/v1/search.ajax/linen');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('products.data.0.slug', $product->slug)
            ->assertJsonPath('categories.0.slug', 'linen-shirts')
            ->assertJsonPath('brands.0.slug', 'kidan-brand');
    }

    private function createCatalogSchema(): void
    {
        foreach ([
            'settings',
            'product_variation_combinations',
            'product_variations',
            'product_attribute_values',
            'attribute_value_translations',
            'attribute_values',
            'attribute_translations',
            'attribute_category',
            'attributes',
            'offer_products',
            'offers',
            'order_details',
            'reviews',
            'product_categories',
            'category_translations',
            'categories',
            'brand_translations',
            'brands',
            'product_taxes',
            'product_translations',
            'products',
            'shops',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('type')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('shops', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->string('logo')->nullable();
            $table->string('banners')->nullable();
            $table->decimal('rating', 8, 2)->default(0);
            $table->decimal('min_order', 12, 2)->default(0);
            $table->boolean('published')->default(true);
            $table->boolean('approval')->default(true);
            $table->boolean('verification_status')->default(true);
            $table->timestamps();
        });

        Schema::create('brands', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
            $table->string('logo')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->timestamps();
        });

        Schema::create('brand_translations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('brand_id');
            $table->string('lang', 10);
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('slug')->unique();
            $table->unsignedBigInteger('parent_id')->default(0);
            $table->unsignedInteger('level')->default(0);
            $table->integer('order_level')->default(0);
            $table->string('banner')->nullable();
            $table->string('icon')->nullable();
            $table->boolean('featured')->default(false);
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->timestamps();
        });

        Schema::create('category_translations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('category_id');
            $table->string('lang', 10);
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('attributes', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('attribute_translations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('attribute_id');
            $table->string('lang', 10);
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('attribute_values', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('attribute_id');
            $table->string('value')->nullable();
            $table->timestamps();
        });

        Schema::create('attribute_value_translations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('attribute_value_id');
            $table->string('lang', 10);
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('attribute_category', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('attribute_id');
            $table->unsignedBigInteger('category_id');
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('shop_id')->nullable();
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->string('name')->nullable();
            $table->string('slug')->unique();
            $table->string('thumbnail_img')->nullable();
            $table->text('photos')->nullable();
            $table->decimal('lowest_price', 12, 2)->default(0);
            $table->decimal('highest_price', 12, 2)->default(0);
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('min_qty')->default(1);
            $table->unsignedInteger('max_qty')->default(10);
            $table->decimal('rating', 8, 2)->default(0);
            $table->decimal('earn_point', 8, 2)->default(0);
            $table->boolean('is_variant')->default(false);
            $table->boolean('digital')->default(false);
            $table->boolean('published')->default(true);
            $table->boolean('approved')->default(true);
            $table->boolean('featured')->default(false);
            $table->string('unit')->nullable();
            $table->text('tags')->nullable();
            $table->decimal('discount', 12, 2)->default(0);
            $table->string('discount_type')->default('flat');
            $table->unsignedBigInteger('discount_start_date')->nullable();
            $table->unsignedBigInteger('discount_end_date')->nullable();
            $table->unsignedInteger('standard_delivery_time')->default(2);
            $table->unsignedInteger('express_delivery_time')->default(1);
            $table->boolean('has_warranty')->default(false);
            $table->boolean('for_pickup')->default(false);
            $table->unsignedInteger('num_of_sale')->default(0);
            $table->string('meta_title')->nullable();
            $table->timestamps();
        });

        Schema::create('product_translations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->string('lang', 10);
            $table->string('name');
            $table->string('unit')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('product_taxes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->string('tax_type')->default('flat');
            $table->decimal('tax', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('product_categories', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('category_id');
            $table->timestamps();
        });

        Schema::create('product_attribute_values', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('attribute_id');
            $table->unsignedBigInteger('attribute_value_id');
            $table->timestamps();
        });

        Schema::create('product_variations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->string('code')->nullable();
            $table->string('sku')->nullable();
            $table->string('img')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('current_stock')->default(0);
            $table->timestamps();
        });

        Schema::create('product_variation_combinations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('product_variation_id');
            $table->unsignedBigInteger('attribute_id');
            $table->unsignedBigInteger('attribute_value_id');
            $table->timestamps();
        });

        Schema::create('reviews', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedTinyInteger('rating')->default(5);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('order_details', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('product_id');
            $table->timestamps();
        });

        Schema::create('offers', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('banner')->nullable();
            $table->boolean('status')->default(true);
            $table->unsignedBigInteger('start_date');
            $table->unsignedBigInteger('end_date');
            $table->timestamps();
        });

        Schema::create('offer_products', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('offer_id');
            $table->unsignedBigInteger('product_id');
            $table->timestamps();
        });
    }

    private function seedSettings(): void
    {
        DB::table('settings')->insert([
            [
                'type' => 'meta_title',
                'value' => 'Default Catalog Meta',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'meta_description',
                'value' => 'Default Catalog Description',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        Cache::flush();
    }

    private function seedCatalog(bool $withRelated = false): array
    {
        $shopId = DB::table('shops')->insertGetId([
            'name' => 'Kidan Shop',
            'slug' => 'kidan-shop',
            'published' => 1,
            'approval' => 1,
            'verification_status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $brandId = DB::table('brands')->insertGetId([
            'name' => 'Kidan Brand',
            'slug' => 'kidan-brand',
            'meta_title' => 'Brand Meta',
            'meta_description' => 'Brand Description',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $brand = \App\Models\Brand::query()->findOrFail($brandId);
        DB::table('brand_translations')->insert([
            'brand_id' => $brand->id,
            'lang' => 'en',
            'name' => 'Kidan Brand',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Shirts',
            'slug' => 'shirts',
            'level' => 0,
            'meta_title' => 'Shirts Meta',
            'meta_description' => 'Shirts Description',
            'order_level' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $category = \App\Models\Category::query()->findOrFail($categoryId);
        DB::table('category_translations')->insert([
            'category_id' => $category->id,
            'lang' => 'en',
            'name' => 'Shirts',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $childCategoryId = DB::table('categories')->insertGetId([
            'name' => 'Linen Shirts',
            'slug' => 'linen-shirts',
            'parent_id' => $category->id,
            'level' => 1,
            'order_level' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('category_translations')->insert([
            'category_id' => $childCategoryId,
            'lang' => 'en',
            'name' => 'Linen Shirts',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $attributeId = DB::table('attributes')->insertGetId([
            'name' => 'Size',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $attribute = \App\Models\Attribute::query()->findOrFail($attributeId);
        DB::table('attribute_translations')->insert([
            'attribute_id' => $attribute->id,
            'lang' => 'en',
            'name' => 'Size',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $smallId = DB::table('attribute_values')->insertGetId([
            'attribute_id' => $attribute->id,
            'value' => 'S',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $small = \App\Models\AttributeValue::query()->findOrFail($smallId);
        DB::table('attribute_value_translations')->insert([
            'attribute_value_id' => $small->id,
            'lang' => 'en',
            'name' => 'Small',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('attribute_category')->insert([
            'attribute_id' => $attribute->id,
            'category_id' => $category->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $product = Product::query()->create([
            'shop_id' => $shopId,
            'brand_id' => $brand->id,
            'name' => 'Linen Shirt',
            'slug' => 'linen-shirt',
            'lowest_price' => 100,
            'highest_price' => 120,
            'stock' => 10,
            'min_qty' => 1,
            'max_qty' => 5,
            'rating' => 4.5,
            'earn_point' => 10,
            'is_variant' => 1,
            'published' => 1,
            'approved' => 1,
            'tags' => 'linen,summer,shirt',
            'discount' => 5,
            'discount_type' => 'flat',
            'meta_title' => 'Linen Shirt Meta',
        ]);
        DB::table('product_translations')->insert([
            'product_id' => $product->id,
            'lang' => 'en',
            'name' => 'Linen Shirt',
            'unit' => 'pc',
            'description' => '<p>Breathable linen shirt</p>',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('product_categories')->insert([
            ['product_id' => $product->id, 'category_id' => $category->id, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $product->id, 'category_id' => $childCategoryId, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('product_attribute_values')->insert([
            'product_id' => $product->id,
            'attribute_id' => $attribute->id,
            'attribute_value_id' => $small->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $variation = ProductVariation::query()->create([
            'product_id' => $product->id,
            'code' => 'Small',
            'sku' => 'LINEN-S',
            'price' => 100,
            'stock' => 7,
            'current_stock' => 7,
        ]);
        DB::table('product_variation_combinations')->insert([
            'product_id' => $product->id,
            'product_variation_id' => $variation->id,
            'attribute_id' => $attribute->id,
            'attribute_value_id' => $small->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('reviews')->insert([
            'product_id' => $product->id,
            'rating' => 5,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $relatedProduct = null;
        if ($withRelated) {
            $relatedProduct = Product::query()->create([
                'shop_id' => $shopId,
                'brand_id' => $brand->id,
                'name' => 'Cotton Shirt',
                'slug' => 'cotton-shirt',
                'lowest_price' => 95,
                'highest_price' => 110,
                'stock' => 12,
                'min_qty' => 1,
                'max_qty' => 5,
                'published' => 1,
                'approved' => 1,
                'created_at' => now()->addMinute(),
                'updated_at' => now()->addMinute(),
            ]);
            DB::table('product_translations')->insert([
                'product_id' => $relatedProduct->id,
                'lang' => 'en',
                'name' => 'Cotton Shirt',
                'unit' => 'pc',
                'description' => 'Cotton shirt',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('product_categories')->insert([
                'product_id' => $relatedProduct->id,
                'category_id' => $category->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return [$category, $brand, $product, $relatedProduct];
    }

    private function seedOffer(?Product $product = null): \App\Models\Offer
    {
        $offerId = DB::table('offers')->insertGetId([
            'title' => 'Summer Offer',
            'slug' => 'summer-offer',
            'status' => 1,
            'start_date' => now()->subDay()->timestamp,
            'end_date' => now()->addDay()->timestamp,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $offer = \App\Models\Offer::query()->findOrFail($offerId);

        if ($product) {
            DB::table('offer_products')->insert([
                'offer_id' => $offer->id,
                'product_id' => $product->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $offer;
    }
}
