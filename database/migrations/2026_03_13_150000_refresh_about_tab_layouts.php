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

        $page = Page::where('slug', 'about-us')->first();
        if (! $page) {
            return;
        }

        $tabsSection = DB::table('page_sections')
            ->where('page_id', $page->id)
            ->where('type', 'tabs_content')
            ->orderBy('sort_order')
            ->first();

        if (! $tabsSection) {
            return;
        }

        $settings = json_decode($tabsSection->settings_json ?? '[]', true) ?: [];
        $existingTabs = $settings['tabs'] ?? [];
        $aboutTab = $existingTabs[0] ?? [
            'tab_label' => 'About Us',
            'layout' => 'basic',
            'content_title' => 'About Kidan',
            'content_body' => 'KIDANSTORE is a modern African luxury and lifestyle house, created to celebrate elegance, heritage, and the evolving expression of the global African woman.',
            'button_text' => null,
            'button_link' => null,
        ];

        $aboutTab['layout'] = $aboutTab['layout'] ?? 'basic';

        $careerImageId = $this->ensureUpload('public/assets/img/career1.jpg');
        $careerCircle1Id = $this->ensureUpload('public/assets/img/career_circle1.jpg');
        $careerCircle2Id = $this->ensureUpload('public/assets/img/career_circle2.jpg');
        $careerCircle3Id = $this->ensureUpload('public/assets/img/career_circle3.jpg');
        $youthImageId = $this->ensureUpload('public/assets/img/youth1.jpg');
        $pressVideoImageId = $this->ensureUpload('public/assets/img/press1.jpg');
        $pressEventImageId = $this->ensureUpload('public/assets/img/press2.jpg');

        $settings['default_tab'] = 0;
        $settings['tabs'] = [
            $aboutTab,
            [
                'tab_label' => 'Career',
                'layout' => 'career_showcase',
                'intro_title' => 'Our Future',
                'intro_body' => 'KIDANSTORE is building a universe: from digital retail to physical pop-up experiences, from curated fashion to in-house product lines, from a local platform to a global African luxury movement. This is just the beginning.',
                'content_title' => 'Our Team',
                'items' => [
                    [
                        'title' => 'Tony Stark',
                        'description' => 'CEO',
                        'image' => $careerImageId,
                    ],
                    [
                        'title' => 'Tony Stark',
                        'description' => 'Product Manager',
                        'image' => $careerImageId,
                    ],
                    [
                        'title' => 'Tony Stark',
                        'description' => 'Media Manager',
                        'image' => $careerImageId,
                    ],
                ],
                'extra_title' => 'Join Us',
                'items_secondary' => [
                    [
                        'title' => 'Kidan Members',
                        'description' => 'Join KIDAN as we build a lifestyle universe centered around community, sustainability, and impact. We are a lean, efficient, and community-driven team, with members working across the globe to shape a beautiful African future through design, nature, craftsmanship, and products.',
                        'image' => $careerCircle1Id,
                        'button_text' => 'Join Us',
                        'button_link' => '#',
                    ],
                    [
                        'title' => 'Our Delivery Point',
                        'description' => 'Become a KIDAN Delivery Point across cities, states, provinces, and countries. As a delivery partner, you help receive, hold, and distribute KIDAN packages, making delivery faster, smoother, and more connected for our customers. Join our growing ecosystem and support a community-centred network built on convenience and trust.',
                        'image' => $careerCircle2Id,
                        'button_text' => 'Join Us',
                        'button_link' => '#',
                    ],
                    [
                        'title' => 'Brand Partners',
                        'description' => 'Join KIDAN as a designer or brand partner and distribute your fashion, beauty, home, or lifestyle products through our growing ecosystem. We support both established brands and emerging creators-helping to showcase, elevate, and scale African design and craftsmanship. Become part of a platform that celebrates culture, quality, and innovation.',
                        'image' => $careerCircle3Id,
                        'button_text' => 'Join Us',
                        'button_link' => '#',
                    ],
                ],
            ],
            [
                'tab_label' => 'Kidan Tribe',
                'layout' => 'tribe_rewards',
                'intro_title' => 'You should know',
                'intro_body' => 'As a customer, you automatically become part of the KIDAN Tribe - our community of loyal members who enjoy a world of rewards, exclusive access, and lifestyle privileges. Your membership unlocks our tiered reward system, with each level offering unique benefits that deepen your connection to KIDAN. From early product access to curated lifestyle perks, every tier brings you closer to the heart of KIDAN.',
                'display_title' => "KIDAN TRIBE\nTIERS",
                'reward_title' => 'Reward',
                'items' => [
                    [
                        'title' => 'KIDAN Tribe Member',
                        'description' => 'Silver Card',
                    ],
                    [
                        'title' => 'KIDAN Tribe Insider',
                        'description' => 'Gold Card',
                    ],
                    [
                        'title' => 'KIDAN Tribe Mentor',
                        'description' => 'Black Card',
                    ],
                    [
                        'title' => 'KIDAN Tribe Royal',
                        'description' => 'Maroon VIP Card',
                    ],
                ],
                'closing_body' => 'Each tier reflects your journey with us and offers elevated experiences designed to celebrate your style, loyalty, and presence within the KIDAN lifestyle ecosystem. Shop. Earn. Grow with us.',
            ],
            [
                'tab_label' => 'Partnership & Affiliate',
                'layout' => 'partnership_cta',
                'intro_title' => 'KIDAN Welcomes',
                'intro_body' => 'Partners and affiliates who share our vision for modern African luxury, community empowerment, and regenerative living. Our ecosystem brings together designers, creators, retailers, influencers, marketers, entrepreneurs, and distributors who want to grow with us and contribute to a connected, sustainable African future.',
                'content_title' => 'Through KIDAN Partnerships & Affiliates, you can:',
                'items' => [
                    ['title' => 'Collaborate on exclusive products and cultural experiences.'],
                    ['title' => 'Distribute or resell KIDAN lifestyle goods.'],
                    ['title' => 'Earn commissions through content, referrals, and affiliate sales.'],
                    ['title' => 'Host KIDAN pop-ups, delivery points, or retail activations.'],
                    ['title' => 'Showcase your brand or craft within the KIDAN ecosystem.'],
                    ['title' => 'Partner on marketing, storytelling, and community initiatives'],
                ],
                'closing_body' => 'Whether you are a designer, influencer, marketer, retailer, creator, or business owner, KIDAN offers a platform where visibility, growth, and meaningful impact come together. Join us and become part of a global lifestyle ecosystem that celebrates culture, craftsmanship, and contemporary African living. Partner with KIDAN.',
                'button_text' => 'Join Us',
                'button_link' => '#',
            ],
            [
                'tab_label' => 'Kidan Youth',
                'layout' => 'youth_program',
                'intro_title' => 'The KIDAN Youth',
                'intro_body' => 'Program is designed to empower young Africans with real skills, real experience, and real opportunities. Rooted in culture, craftsmanship, innovation, and regenerative living, the program goes beyond education, it provides hands-on training, business support, and pathways to success.',
                'image' => $youthImageId,
                'items' => [
                    [
                        'title' => 'Craftsmanship & Maker Training',
                        'description' => 'Skills in fashion, product design, home goods, beauty formulation, and artisan craft.',
                    ],
                    [
                        'title' => 'Internships & Real Work Experience',
                        'description' => 'Placements within KIDAN and partner brands to learn, build, and grow.',
                    ],
                    [
                        'title' => 'Business Review & Mentorship',
                        'description' => 'Guidance on branding, pricing, product development, and strategy.',
                    ],
                    [
                        'title' => 'Start-up Support & Launch Assistance',
                        'description' => 'Helping youth-owned businesses access markets, visibility, and growth opportunities.',
                    ],
                    [
                        'title' => 'Community Projects & Cultural Innovation Labs',
                        'description' => 'Spaces to create, collaborate, and shape future-forward African ideas.',
                    ],
                ],
                'closing_body' => 'The KIDAN Youth Program exists to lift young creators, innovators, and entrepreneurs into the spotlight, giving them the tools and support to build sustainable businesses and meaningful careers.',
                'statement_lines' => [
                    ['text' => "We don't just train youth."],
                    ['text' => 'We help them rise.'],
                    ['text' => 'We help them create.'],
                    ['text' => 'We help them launch.'],
                ],
                'footer_text' => "KIDAN - empowering the youth to build Africa's beautiful future.",
            ],
            [
                'tab_label' => 'Press & Events',
                'layout' => 'press_events',
                'intro_title' => 'Press',
                'intro_body' => 'We collaborate with media platforms, journalists, creators, and cultural storytellers to share the KIDAN vision with the world. Our press features spotlight our work in design, lifestyle, regenerative living, and African innovation, amplifying the voices and stories that shape our universe.',
                'content_title' => 'Virtual Pop-Ups',
                'content_body' => 'KIDAN hosts virtual pop-up experiences that allow customers around the world to explore our collections, designers, and lifestyle products from anywhere. These digital activations offer early access to new drops, limited editions, and behind-the-scenes previews of the KIDAN universe.',
                'items' => [
                    [
                        'meta' => 'Video',
                        'submeta' => '11 November 2025.',
                        'title' => 'Nigerian x India Fusion The New Wave',
                        'description' => '@Kidanstores/YT',
                        'image' => $pressVideoImageId,
                    ],
                    [
                        'meta' => 'Video',
                        'submeta' => '11 November 2025.',
                        'title' => 'Nigerian x India Fusion The New Wave',
                        'description' => '@Kidanstores/YT',
                        'image' => $pressVideoImageId,
                    ],
                    [
                        'meta' => 'Video',
                        'submeta' => '11 November 2025.',
                        'title' => 'Nigerian x India Fusion The New Wave',
                        'description' => '@Kidanstores/YT',
                        'image' => $pressVideoImageId,
                    ],
                    [
                        'meta' => 'Video',
                        'submeta' => '11 November 2025.',
                        'title' => 'Nigerian x India Fusion The New Wave',
                        'description' => '@Kidanstores/YT',
                        'image' => $pressVideoImageId,
                    ],
                ],
                'button_text' => 'View More',
                'button_link' => '#',
                'extra_title' => 'Events',
                'extra_body' => 'From intimate brand moments to large-scale cultural activations, KIDAN events are designed to connect people, celebrate creativity, and build community. We curate experiences that blend fashion, craft, design, wellness, hospitality, and culture, both online and in physical spaces.',
                'items_secondary' => [
                    [
                        'meta' => 'Magazine',
                        'submeta' => '11 November 2025.',
                        'title' => 'Nigerian x India Fusion The New Wave',
                        'description' => 'By Imulo Yimika',
                        'image' => $pressEventImageId,
                    ],
                    [
                        'meta' => 'Magazine',
                        'submeta' => '11 November 2025.',
                        'title' => 'Nigerian x India Fusion The New Wave',
                        'description' => 'By Imulo Yimika',
                        'image' => $pressEventImageId,
                    ],
                    [
                        'meta' => 'Magazine',
                        'submeta' => '11 November 2025.',
                        'title' => 'Nigerian x India Fusion The New Wave',
                        'description' => 'By Imulo Yimika',
                        'image' => $pressEventImageId,
                    ],
                    [
                        'meta' => 'Magazine',
                        'submeta' => '11 November 2025.',
                        'title' => 'Nigerian x India Fusion The New Wave',
                        'description' => 'By Imulo Yimika',
                        'image' => $pressEventImageId,
                    ],
                ],
                'extra_button_text' => 'View More',
                'extra_button_link' => '#',
            ],
        ];

        DB::table('page_sections')
            ->where('id', $tabsSection->id)
            ->update([
                'settings_json' => json_encode($settings),
                'updated_at' => now(),
            ]);
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
