<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pages') || ! Schema::hasTable('page_sections')) {
            return;
        }

        $page = Page::where('slug', 'about-us')->first();
        if (! $page) {
            return;
        }

        $sections = DB::table('page_sections')
            ->where('page_id', $page->id)
            ->orderBy('sort_order')
            ->get();

        $tabsIndex = $sections->search(function ($section) {
            return $section->type === 'tabs_content';
        });

        if ($tabsIndex === false) {
            return;
        }

        $scopedTypes = [
            'image_content_panel',
            'vision_mission_split',
            'editorial_quote',
        ];

        foreach ($sections as $index => $section) {
            if ($index <= $tabsIndex || ! in_array($section->type, $scopedTypes, true)) {
                continue;
            }

            $settings = json_decode($section->settings_json ?? '[]', true) ?: [];
            $settings['tab_visibility'] = 'previous_tab_default_only';

            DB::table('page_sections')
                ->where('id', $section->id)
                ->update([
                    'settings_json' => json_encode($settings),
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
    }
};
