<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageSection extends Model
{
    protected $fillable = [
        'page_id',
        'section_key',
        'type',
        'title',
        'subtitle',
        'content',
        'button_text',
        'button_link',
        'image',
        'image_2',
        'settings_json',
        'sort_order',
        'is_visible',
    ];

    protected $casts = [
        'settings_json' => 'array',
        'is_visible' => 'boolean',
        'sort_order' => 'integer',
    ];

    public const TYPES = [
        'about_hero_split' => 'About Hero Split Section',
        'editorial_intro' => 'Editorial Rich Intro Section',
        'tabs_content' => 'Horizontal Tabs Content Section',
        'image_content_panel' => 'Image + Content Panel Section',
        'vision_mission_split' => 'Vision Mission Split Section',
        'editorial_quote' => 'Editorial Quote Section',
        'hero' => 'Hero Section',
        'rich_text' => 'Rich Text Section',
        'image_text_split' => 'Image + Text Split Section',
        'multi_column_features' => 'Multi-Column Features Section',
        'vision_mission' => 'Vision / Mission Section',
        'quote' => 'Quote Section',
        'cta_banner' => 'CTA Banner Section',
        'image_gallery' => 'Image Gallery Section',
        'spacer' => 'Spacer / Divider Section',
        'journal_editorial' => 'Journal Editorial Section',
    ];

    public function page()
    {
        return $this->belongsTo(Page::class);
    }

    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }
}
