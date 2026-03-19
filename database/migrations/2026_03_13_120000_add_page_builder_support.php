<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pages') && ! Schema::hasColumn('pages', 'is_published')) {
            Schema::table('pages', function (Blueprint $table) {
                $table->boolean('is_published')->default(true)->after('meta_image');
            });
        }

        if (! Schema::hasTable('page_sections')) {
            Schema::create('page_sections', function (Blueprint $table) {
                $table->id();
                $table->integer('page_id');
                $table->uuid('section_key')->unique();
                $table->string('type', 100);
                $table->string('title')->nullable();
                $table->string('subtitle')->nullable();
                $table->longText('content')->nullable();
                $table->string('button_text')->nullable();
                $table->string('button_link')->nullable();
                $table->unsignedBigInteger('image')->nullable();
                $table->unsignedBigInteger('image_2')->nullable();
                $table->longText('settings_json')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_visible')->default(true);
                $table->timestamps();

                $table->foreign('page_id')->references('id')->on('pages')->onDelete('cascade');
                $table->index(['page_id', 'sort_order']);
                $table->index(['page_id', 'is_visible']);
            });
        }

        if (Schema::hasTable('pages') && Schema::hasColumn('pages', 'is_published')) {
            DB::table('pages')->whereNull('is_published')->update(['is_published' => 1]);
        }

        $this->seedAboutPageSections();
    }

    public function down(): void
    {
        if (Schema::hasTable('page_sections')) {
            Schema::dropIfExists('page_sections');
        }

        if (Schema::hasTable('pages') && Schema::hasColumn('pages', 'is_published')) {
            Schema::table('pages', function (Blueprint $table) {
                $table->dropColumn('is_published');
            });
        }
    }

    private function seedAboutPageSections(): void
    {
        if (! Schema::hasTable('pages') || ! Schema::hasTable('page_sections')) {
            return;
        }

        $aboutPage = DB::table('pages')->where('slug', 'about-us')->first();

        if (! $aboutPage) {
            $pageId = DB::table('pages')->insertGetId([
                'type' => 'custom_page',
                'title' => 'About Us',
                'slug' => 'about-us',
                'meta_title' => 'About Us',
                'is_published' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $pageId = $aboutPage->id;
        }

        if (DB::table('page_sections')->where('page_id', $pageId)->exists()) {
            return;
        }

        $sections = [
            [
                'type' => 'hero',
                'title' => 'About Kidan',
                'subtitle' => 'A modern African luxury and lifestyle house rooted in elegance, heritage, and bold expression.',
                'button_text' => 'Explore Our World',
                'button_link' => '/all-brands',
                'settings_json' => json_encode([
                    'heading' => 'About Kidan',
                    'subheading' => 'A modern African luxury and lifestyle house rooted in elegance, heritage, and bold expression.',
                    'alignment' => 'left',
                ]),
            ],
            [
                'type' => 'image_text_split',
                'title' => 'What we do',
                'content' => 'At KIDANSTORE, we curate contemporary fashion, African luxury designers, accessories, beauty, lifestyle pieces, home, fragrance, and exclusive KIDAN collections.',
                'settings_json' => json_encode([
                    'title' => 'What we do',
                    'description' => 'At KIDANSTORE, we curate contemporary fashion, African luxury designers, accessories, beauty, lifestyle pieces, home, fragrance, and exclusive KIDAN collections.',
                    'image_position' => 'left',
                ]),
            ],
            [
                'type' => 'vision_mission',
                'title' => 'Vision and Mission',
                'settings_json' => json_encode([
                    'vision_title' => 'Our Vision',
                    'vision_text' => "To become Africa's leading fashion and lifestyle destination, known globally for curation, craftsmanship, culture, and accessibility.",
                    'mission_title' => 'Our Mission',
                    'mission_text' => 'To make exceptional style accessible, intentional, and beautifully curated through a refined, seamless shopping experience.',
                ]),
            ],
            [
                'type' => 'quote',
                'settings_json' => json_encode([
                    'quote_text' => "Fashion is about dressing according to what's fashionable. Style is more about being yourself.",
                    'author' => 'Oscar de la Renta',
                ]),
            ],
            [
                'type' => 'cta_banner',
                'title' => 'Discover the Kidan Edit',
                'subtitle' => 'Step into curated fashion, beauty, and lifestyle collections crafted for the modern African woman.',
                'button_text' => 'Shop New Arrivals',
                'button_link' => '/all-offers',
                'settings_json' => json_encode([
                    'heading' => 'Discover the Kidan Edit',
                    'subheading' => 'Step into curated fashion, beauty, and lifestyle collections crafted for the modern African woman.',
                    'button_text' => 'Shop New Arrivals',
                    'button_link' => '/all-offers',
                    'background_style' => 'sand',
                ]),
            ],
        ];

        foreach ($sections as $index => $section) {
            DB::table('page_sections')->insert([
                'page_id' => $pageId,
                'section_key' => (string) Str::uuid(),
                'type' => $section['type'],
                'title' => $section['title'] ?? null,
                'subtitle' => $section['subtitle'] ?? null,
                'content' => $section['content'] ?? null,
                'button_text' => $section['button_text'] ?? null,
                'button_link' => $section['button_link'] ?? null,
                'image' => null,
                'image_2' => null,
                'settings_json' => $section['settings_json'] ?? null,
                'sort_order' => $index + 1,
                'is_visible' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
