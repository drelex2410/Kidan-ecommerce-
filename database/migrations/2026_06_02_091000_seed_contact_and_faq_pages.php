<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pages') || ! Schema::hasTable('page_sections')) {
            return;
        }

        $contactPageId = $this->upsertPage('contact-us', 'Contact Us');
        $faqPageId = $this->upsertPage('faq', 'Frequently Asked Questions');

        $this->seedContactSections($contactPageId);
        $this->seedFaqSections($faqPageId);
        $this->ensureFooterLinks();
    }

    public function down(): void
    {
        if (! Schema::hasTable('pages') || ! Schema::hasTable('page_sections')) {
            return;
        }

        $pageIds = DB::table('pages')
            ->whereIn('slug', ['contact-us', 'faq'])
            ->pluck('id');

        DB::table('page_sections')->whereIn('page_id', $pageIds)->delete();
    }

    private function upsertPage(string $slug, string $title): int
    {
        $now = now();
        $existing = DB::table('pages')->where('slug', $slug)->first();

        if ($existing) {
            DB::table('pages')->where('id', $existing->id)->update([
                'title' => $title,
                'meta_title' => $title,
                'is_published' => 1,
                'updated_at' => $now,
            ]);

            $pageId = (int) $existing->id;
        } else {
            $pageId = DB::table('pages')->insertGetId([
                'type' => 'custom_page',
                'title' => $title,
                'slug' => $slug,
                'content' => null,
                'meta_title' => $title,
                'is_published' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (Schema::hasTable('page_translations')) {
            DB::table('page_translations')->updateOrInsert(
                ['page_id' => $pageId, 'lang' => env('DEFAULT_LANGUAGE', 'en')],
                ['title' => $title, 'content' => '', 'updated_at' => $now, 'created_at' => $now]
            );
        }

        return $pageId;
    }

    private function seedContactSections(int $pageId): void
    {
        if (DB::table('page_sections')->where('page_id', $pageId)->where('type', 'contact_hero_form')->exists()) {
            return;
        }

        DB::table('page_sections')->where('page_id', $pageId)->delete();

        $this->insertSection($pageId, 1, 'contact_hero_form', [
            'title' => 'Contact Us',
            'subtitle' => 'Whether you have questions about your order, partnership opportunities, designer collaborations, or our lifestyle ecosystem, the KIDAN team is ready to assist.',
            'settings_json' => [
                'heading' => 'Contact Us',
                'subheading' => 'Whether you have questions about your order, partnership opportunities, designer collaborations, or our lifestyle ecosystem, the KIDAN team is ready to assist.',
                'fallback_image_url' => '/assets/img/about_hero.jpg',
                'contact_intro' => 'Reach out to us.',
                'location_label' => 'Location',
                'location_text' => 'No. 10 New Yidi Road Ilorin, Kwara State Nigeria.',
                'mail_label' => 'Mail',
                'mail_text' => 'support@kidanstore.com',
                'phone_label' => 'Phone',
                'phone_text' => '07071827096',
            ],
        ]);

        $this->insertSection($pageId, 2, 'contact_topic_buttons', [
            'title' => 'Talk to us about something specific',
            'subtitle' => 'We are here to support you, guide you, and connect you with the KIDAN experience.',
            'settings_json' => [
                'items' => collect([
                    'Partnerships & Collaborations',
                    'Press & Media',
                    'Wholesale & Retail Distribution',
                    'General Inquiries',
                    'KIDAN Tribe & Membership',
                ])->map(fn ($title) => ['title' => $title])->all(),
            ],
        ]);

        $stores = [
            'No. 10 New Yidi Road Ilorin, Kwara State Nigeria.',
            'Store 121 Post office Demilade Shopping Complex, Ilorin, Nigeria.',
            'Store 121 Post office Demilade Shopping Complex, Ikeja, Nigeria.',
            'Store 121 Post office Demilade Shopping Complex, Victoria Island, Nigeria.',
        ];

        $this->insertSection($pageId, 3, 'contact_store_grid', [
            'title' => 'Find Our Stores',
            'settings_json' => [
                'items' => collect($stores)->map(fn ($address) => [
                    'title' => $address,
                    'meta' => 'Address',
                    'button_text' => 'GET DIRECTIONS',
                    'button_link' => 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($address),
                ])->all(),
            ],
        ]);
    }

    private function seedFaqSections(int $pageId): void
    {
        if (DB::table('page_sections')->where('page_id', $pageId)->where('type', 'faq_list')->exists()) {
            return;
        }

        DB::table('page_sections')->where('page_id', $pageId)->delete();

        $items = [
            ['What is KIDANSTORE?', '<p>KIDANSTORE is a contemporary African lifestyle and fashion ecosystem offering curated fashion, beauty, home essentials, and luxury lifestyle products. We combine culture, craftsmanship, and modern living to deliver a refined shopping experience.</p>'],
            ['Where is KIDANSTORE based?', '<p>We operate digitally across Africa with growing physical touchpoints, delivery partners, and pop-up experiences in key cities.</p>'],
            ['How do I place an order?', '<p>Simply browse our collections, add items to your cart, and checkout using your preferred payment option.</p><p>You will receive an order confirmation immediately after purchase.</p>'],
            ['What payment methods do you accept?', '<p>We accept:</p><ul><li>Debit and credit cards</li><li>Bank transfers</li><li>Mobile payments</li><li>Paystack, Flutterwave, Stripe and Paypal</li><li>Select wallet options</li><li>KIDAN gift cards (coming soon)</li><li>Instalments/BNPL options may be available depending on your location</li></ul>'],
            ['How long does delivery take?', '<p>Delivery times vary by city and country, but most orders are fulfilled within:</p><ul><li>1-3 days within major cities</li><li>3-7 days nationwide</li><li>7-14 days for regional/international delivery</li></ul><p>You will receive tracking updates throughout the process.</p>'],
            ['What are KIDAN Delivery Points?', '<p>KIDAN Delivery Points are approved partner locations where customers can:</p><ul><li>Pick up orders</li><li>Drop off returns</li><li>Access faster, more connected local delivery</li></ul><p>This helps reduce delays and creates a community-powered delivery network.</p>'],
            ['Can I return or exchange items?', '<p>Yes. KIDANSTORE offers returns or exchanges within 7 days of receiving your order, as long as the item is unused, unwashed, and in original packaging.</p><p>Certain products like beauty or intimate items may not be eligible.</p>'],
            ['Do you ship internationally?', '<p>Yes. We ship to selected countries. International customers will see shipping options at checkout.</p>'],
            ['How do I join the KIDAN Tribe?', '<p>All customers automatically join the KIDAN Tribe loyalty program.</p><p>Your membership tier grows as you shop, unlocking exclusive benefits, early access, rewards, and lifestyle perks.</p>'],
            ['What are the KIDAN Tribe tiers?', '<ul><li>KIDAN Tribe Member - Silver Card</li><li>KIDAN Tribe Insider - Gold Card</li><li>KIDAN Tribe Mentor - Black Card</li><li>KIDAN Tribe Royal - Maroon VIP Card</li></ul><p>Each tier offers increasing rewards, benefits, and exclusive access.</p>'],
            ['How do I become a KIDAN Designer Partner?', '<p>Designers, brands, and creators can join KIDAN to distribute their fashion, beauty, home, or lifestyle products.</p><p>We also support emerging designers with mentorship and visibility.</p><p>Contact: <strong>Business@kidanstore.com</strong></p>'],
            ['How do I partner as an influencer or marketer?', '<p>Influencers, creators, and marketers can join the KIDAN Affiliates Program to earn commissions, access campaigns, and collaborate on brand storytelling.</p><p>Contact: <strong>Business@kidanstore.com</strong></p>'],
            ['What is the KIDAN Youth Program?', '<p>A development program for young Africans offering hands-on craftsmanship training, internships, business support, creative labs, and startup assistance.</p>'],
            ['How can I contact KIDANSTORE?', '<p>For general inquiries: <strong>hello@kidanglobal.com</strong></p><p>For support: <strong>support@kidanstore.com</strong></p><p>For press, partnerships, or wholesale, visit our Contact Page.</p>'],
            ['Where can I follow KIDAN?', '<p>Instagram: <strong>@kidanstore</strong></p><p>TikTok: <strong>@kidanstore</strong></p><p>Website: <strong>www.kidanstore.com</strong></p>'],
        ];

        $this->insertSection($pageId, 1, 'faq_list', [
            'title' => 'Frequently Asked Questions',
            'subtitle' => 'You have questions ? we have answers.',
            'settings_json' => [
                'items' => collect($items)->map(fn ($item) => [
                    'title' => $item[0],
                    'description' => $item[1],
                ])->all(),
            ],
        ]);
    }

    private function insertSection(int $pageId, int $sortOrder, string $type, array $data): void
    {
        DB::table('page_sections')->insert([
            'page_id' => $pageId,
            'section_key' => (string) Str::uuid(),
            'type' => $type,
            'title' => $data['title'] ?? null,
            'subtitle' => $data['subtitle'] ?? null,
            'content' => $data['content'] ?? null,
            'button_text' => $data['button_text'] ?? null,
            'button_link' => $data['button_link'] ?? null,
            'image' => $data['image'] ?? null,
            'image_2' => $data['image_2'] ?? null,
            'settings_json' => json_encode($data['settings_json'] ?? []),
            'sort_order' => $sortOrder,
            'is_visible' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function ensureFooterLinks(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $labels = $this->settingJsonArray('footer_link_two_labels');
        $links = $this->settingJsonArray('footer_link_two_links');
        $pairs = array_combine($labels ?: [], $links ?: []) ?: [];

        $pairs['Help & FAQ'] = '/faq';
        $pairs['Contact Us'] = '/contact-us';

        $this->setSetting('footer_link_two_labels', json_encode(array_keys($pairs)));
        $this->setSetting('footer_link_two_links', json_encode(array_values($pairs)));
    }

    private function settingJsonArray(string $type): array
    {
        $value = DB::table('settings')->where('type', $type)->value('value');
        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? array_values($decoded) : [];
    }

    private function setSetting(string $type, string $value): void
    {
        DB::table('settings')->updateOrInsert(
            ['type' => $type],
            ['value' => $value, 'updated_at' => now(), 'created_at' => now()]
        );
    }
};
