<?php

use App\Models\Page;
use App\Models\Upload;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pages') || ! Schema::hasTable('page_sections') || ! Schema::hasTable('uploads')) {
            return;
        }

        $page = Page::firstOrCreate(
            ['slug' => 'about-us'],
            [
                'type' => 'custom_page',
                'title' => 'About Us',
                'content' => null,
                'meta_title' => 'About us',
                'is_published' => true,
            ]
        );

        $page->forceFill([
            'type' => 'custom_page',
            'title' => 'About Us',
            'meta_title' => 'About us',
            'is_published' => true,
        ])->save();

        $heroImageId = $this->ensureUpload('public/assets/img/about_hero.jpg');
        $panelImageId = $this->ensureUpload('public/assets/img/about1.jpg');
        $visionImageId = $this->ensureUpload('public/assets/img/about2.jpg');
        $badgeImageId = $this->ensureUpload('public/assets/img/kidan-badge.svg');

        DB::table('page_sections')->where('page_id', $page->id)->delete();

        $introHtml = '<p><strong>KIDAN</strong> is a lifestyle ecosystem that integrates <strong>African culture, luxury, fashion, hospitality, and regenerative living</strong> into a unified, modern experience. Rooted in <strong>contemporary African aesthetics</strong> and driven by <strong>sustainable innovation</strong>, KIDAN exists to shape a new narrative - one where Africa\'s heritage and future meet beautifully. We are committed to "creating a beautiful African future through design, nature, craftsmanship, and products." Our work bridges physical spaces, curated products, and immersive experiences creating a world where elegance, culture, and innovation coexist seamlessly.</p>';

        $sections = [
            [
                'type' => 'about_hero_split',
                'title' => 'OUR FOUNDATION',
                'subtitle' => 'Walk with us and find out who we are.',
                'image' => $heroImageId,
                'image_2' => $badgeImageId,
                'settings_json' => json_encode([
                    'alignment' => 'left',
                    'title_max_width' => 430,
                    'image_alt' => 'Portrait of a Kidan muse',
                ]),
            ],
            [
                'type' => 'editorial_intro',
                'content' => $introHtml,
                'settings_json' => json_encode([
                    'max_width' => 1040,
                    'text_align' => 'center',
                ]),
            ],
            [
                'type' => 'tabs_content',
                'settings_json' => json_encode([
                    'default_tab' => 0,
                    'tabs' => [
                        [
                            'tab_label' => 'About Us',
                            'content_title' => 'About Kidan',
                            'content_body' => 'KIDANSTORE is a modern African luxury and lifestyle house, created to celebrate elegance, heritage, and the evolving expression of the global African woman.',
                            'button_text' => null,
                            'button_link' => null,
                        ],
                        [
                            'tab_label' => 'Career',
                            'content_title' => 'Careers at Kidan',
                            'content_body' => 'We build opportunities for thoughtful creators, operators, and storytellers who want to shape an African luxury ecosystem with intention and care.',
                            'button_text' => null,
                            'button_link' => null,
                        ],
                        [
                            'tab_label' => 'Kidan Tribe',
                            'content_title' => 'Kidan Tribe',
                            'content_body' => 'Kidan Tribe is our growing community of tastemakers, culture-shapers, and collaborators who connect around style, design, and modern African identity.',
                            'button_text' => null,
                            'button_link' => null,
                        ],
                        [
                            'tab_label' => 'Partnership & Affiliate',
                            'content_title' => 'Partnership & Affiliate',
                            'content_body' => 'We collaborate with aligned brands, creatives, and advocates who share our commitment to elegant storytelling, cultural value, and intentional growth.',
                            'button_text' => null,
                            'button_link' => null,
                        ],
                        [
                            'tab_label' => 'Kidan Youth',
                            'content_title' => 'Kidan Youth',
                            'content_body' => 'Kidan Youth exists to inspire the next generation through access, confidence, and creative participation in fashion, culture, and lifestyle.',
                            'button_text' => null,
                            'button_link' => null,
                        ],
                        [
                            'tab_label' => 'Press & Events',
                            'content_title' => 'Press & Events',
                            'content_body' => 'From editorial moments to curated experiences, our press and events chapter captures the conversations and gatherings shaping the Kidan world.',
                            'button_text' => null,
                            'button_link' => null,
                        ],
                    ],
                ]),
            ],
            [
                'type' => 'image_content_panel',
                'title' => 'What we do',
                'subtitle' => 'At KIDANSTORE, we curate:',
                'content' => 'KIDANSTORE blends premium aesthetic standards with the warmth of African identity, creating a shopping experience that feels both global and deeply personal.',
                'image' => $panelImageId,
                'settings_json' => json_encode([
                    'panel_theme' => 'mocha',
                    'bullets' => [
                        ['text' => 'Contemporary fashion'],
                        ['text' => 'African luxury designers'],
                        ['text' => 'Accessories, beauty, and lifestyle pieces'],
                        ['text' => 'Home, fragrance, and everyday essentials'],
                        ['text' => 'Exclusive KIDAN collections'],
                    ],
                ]),
            ],
            [
                'type' => 'vision_mission_split',
                'image' => $visionImageId,
                'settings_json' => json_encode([
                    'background_style' => 'sand',
                    'vision_title' => 'Our Vision',
                    'vision_text' => 'To become Africa\'s leading Fashion and Lifestyle destination, known globally for Curation, Craftsmanship, Culture and Accessibility. KIDANSTORE is more than an e-commerce platform - it is a modern luxury house and brand universe.',
                    'mission_title' => 'Our Mission',
                    'mission_text' => 'To make exceptional style accessible, intentional, and beautifully curated. Founded with a belief that fashion is both identity and power, KIDANSTORE brings together premium fashion, African designers, global luxury accents, and everyday essentials into one refined, seamless shopping experience.',
                ]),
            ],
            [
                'type' => 'editorial_quote',
                'settings_json' => json_encode([
                    'quote_text' => 'Fashion is about dressing according to what\'s fashionable. Style is more about being yourself.',
                    'author' => 'Oscar de la Renta',
                ]),
            ],
        ];

        foreach ($sections as $index => $section) {
            DB::table('page_sections')->insert([
                'page_id' => $page->id,
                'section_key' => (string) Str::uuid(),
                'type' => $section['type'],
                'title' => $section['title'] ?? null,
                'subtitle' => $section['subtitle'] ?? null,
                'content' => $section['content'] ?? null,
                'button_text' => $section['button_text'] ?? null,
                'button_link' => $section['button_link'] ?? null,
                'image' => $section['image'] ?? null,
                'image_2' => $section['image_2'] ?? null,
                'settings_json' => $section['settings_json'] ?? null,
                'sort_order' => $index + 1,
                'is_visible' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
    }

    private function ensureUpload(string $relativePath): int
    {
        $fullPath = public_path(Str::after($relativePath, 'public/'));
        $extension = pathinfo($fullPath, PATHINFO_EXTENSION);

        $upload = Upload::firstOrCreate(
            ['file_name' => $relativePath],
            [
                'file_original_name' => basename($relativePath),
                'user_id' => 1,
                'file_size' => file_exists($fullPath) ? filesize($fullPath) : 0,
                'extension' => $extension,
                'type' => 'image',
            ]
        );

        return (int) $upload->id;
    }
};
