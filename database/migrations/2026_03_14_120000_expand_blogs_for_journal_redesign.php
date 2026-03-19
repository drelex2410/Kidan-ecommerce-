<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blogs', function (Blueprint $table) {
            $table->unsignedBigInteger('author_user_id')->nullable()->after('category_id');
            $table->timestamp('published_at')->nullable()->after('status');
            $table->string('editorial_image')->nullable()->after('banner');
            $table->string('product_source_type', 20)->nullable()->after('editorial_image');
            $table->unsignedBigInteger('product_category_id')->nullable()->after('product_source_type');
            $table->unsignedBigInteger('product_brand_id')->nullable()->after('product_category_id');
            $table->unsignedSmallInteger('related_products_limit')->nullable()->after('product_brand_id');
            $table->text('related_product_ids')->nullable()->after('related_products_limit');
            $table->text('youtube_urls')->nullable()->after('related_product_ids');
        });

        Schema::table('blog_translations', function (Blueprint $table) {
            $table->string('hero_button_label')->nullable()->after('description');
            $table->string('editorial_title')->nullable()->after('hero_button_label');
            $table->text('editorial_content')->nullable()->after('editorial_title');
            $table->text('modal_summary')->nullable()->after('editorial_content');
        });
    }

    public function down(): void
    {
        Schema::table('blog_translations', function (Blueprint $table) {
            $table->dropColumn([
                'hero_button_label',
                'editorial_title',
                'editorial_content',
                'modal_summary',
            ]);
        });

        Schema::table('blogs', function (Blueprint $table) {
            $table->dropColumn([
                'author_user_id',
                'published_at',
                'editorial_image',
                'product_source_type',
                'product_category_id',
                'product_brand_id',
                'related_products_limit',
                'related_product_ids',
                'youtube_urls',
            ]);
        });
    }
};
