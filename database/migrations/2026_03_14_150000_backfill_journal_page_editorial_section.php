<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pages') || ! Schema::hasTable('page_sections') || ! Schema::hasTable('blogs') || ! Schema::hasTable('page_translations')) {
            return;
        }

        $page = DB::table('pages')->where('slug', 'journal')->first();

        if (! $page) {
            $pageId = DB::table('pages')->insertGetId([
                'type' => 'custom_page',
                'title' => 'Journal',
                'slug' => 'journal',
                'is_published' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('page_translations')->insert([
                'page_id' => $pageId,
                'title' => 'Journal',
                'content' => '',
                'lang' => env('DEFAULT_LANGUAGE', 'en'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $pageId = $page->id;
        }

        $existingSection = DB::table('page_sections')
            ->where('page_id', $pageId)
            ->where('type', 'journal_editorial')
            ->first();

        if ($existingSection) {
            return;
        }

        $sourceBlog = DB::table('blogs')
            ->where('status', 1)
            ->orderByRaw('COALESCE(published_at, created_at) desc')
            ->first();

        $translation = null;
        if ($sourceBlog && Schema::hasTable('blog_translations')) {
            $translation = DB::table('blog_translations')
                ->where('blog_id', $sourceBlog->id)
                ->where('lang', env('DEFAULT_LANGUAGE', 'en'))
                ->first();
        }

        DB::table('page_sections')->insert([
            'page_id' => $pageId,
            'section_key' => (string) Str::uuid(),
            'type' => 'journal_editorial',
            'title' => $translation->editorial_title ?? $sourceBlog->title ?? 'Journal editorial section',
            'subtitle' => null,
            'content' => $translation->editorial_content ?? $sourceBlog->short_description ?? null,
            'button_text' => null,
            'button_link' => null,
            'image' => $sourceBlog->editorial_image ?? null,
            'image_2' => null,
            'settings_json' => json_encode([
                'product_source_type' => $sourceBlog->product_source_type ?? null,
                'product_category_id' => $sourceBlog->product_category_id ?? null,
                'product_brand_id' => $sourceBlog->product_brand_id ?? null,
                'related_products_limit' => $sourceBlog->related_products_limit ?? 4,
                'youtube_urls' => json_decode($sourceBlog->youtube_urls ?? '[]', true) ?: [],
            ]),
            'sort_order' => 1,
            'is_visible' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('pages') || ! Schema::hasTable('page_sections')) {
            return;
        }

        $pageId = DB::table('pages')->where('slug', 'journal')->value('id');
        if (! $pageId) {
            return;
        }

        DB::table('page_sections')
            ->where('page_id', $pageId)
            ->where('type', 'journal_editorial')
            ->delete();
    }
};
