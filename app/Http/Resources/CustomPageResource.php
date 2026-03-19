<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class CustomPageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => (integer) $this->id,
            'type' => $this->type,
            'title' => $this->getTranslation('title'),
            'slug' => $this->slug,
            'content' => $this->getTranslation('content'),
            'path' => $this->frontend_path,
            'is_published' => (bool) ($this->is_published ?? true),
            'metaTitle' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'keywords' => $this->keywords,
            'meta_image' => api_asset($this->meta_image),
            'sections' => $this->visibleSections->map(function ($section) {
                $settings = $section->settings_json ?: [];
                $galleryImages = array_values(array_filter(array_map('trim', explode(',', (string) Arr::get($settings, 'gallery_images', '')))));
                $featureItems = Arr::get($settings, 'items', []);
                $tabs = Arr::get($settings, 'tabs', []);
                $bullets = Arr::get($settings, 'bullets', []);

                return [
                    'id' => (int) $section->id,
                    'section_key' => $section->section_key,
                    'type' => $section->type,
                    'sort_order' => (int) $section->sort_order,
                    'is_visible' => (bool) $section->is_visible,
                    'data' => [
                        'title' => $section->title,
                        'subtitle' => $section->subtitle,
                        'heading' => Arr::get($settings, 'heading', $section->title),
                        'subheading' => Arr::get($settings, 'subheading', $section->subtitle),
                        'content' => $section->content,
                        'button_text' => $section->button_text,
                        'button_link' => $section->button_link,
                        'image' => $section->image ? api_asset($section->image) : null,
                        'image_2' => $section->image_2 ? api_asset($section->image_2) : null,
                        'badge_image' => $section->image_2 ? api_asset($section->image_2) : null,
                        'alignment' => Arr::get($settings, 'alignment', 'left'),
                        'image_position' => Arr::get($settings, 'image_position', 'left'),
                        'background_style' => Arr::get($settings, 'background_style'),
                        'panel_theme' => Arr::get($settings, 'panel_theme'),
                        'line_toggle' => (bool) Arr::get($settings, 'line_toggle', false),
                        'spacing_size' => Arr::get($settings, 'spacing_size', 'md'),
                        'max_width' => Arr::get($settings, 'max_width'),
                        'text_align' => Arr::get($settings, 'text_align', 'center'),
                        'title_max_width' => Arr::get($settings, 'title_max_width'),
                        'image_alt' => Arr::get($settings, 'image_alt'),
                        'default_tab' => (int) Arr::get($settings, 'default_tab', 0),
                        'tab_visibility' => Arr::get($settings, 'tab_visibility', 'always'),
                        'items' => collect($featureItems)->map(function ($item) {
                            return [
                                'title' => Arr::get($item, 'title'),
                                'description' => Arr::get($item, 'description'),
                                'image' => Arr::get($item, 'image') ? api_asset(Arr::get($item, 'image')) : null,
                            ];
                        })->values(),
                        'tabs' => collect($tabs)->map(function ($tab) {
                            return [
                                'tab_label' => Arr::get($tab, 'tab_label'),
                                'layout' => Arr::get($tab, 'layout', 'basic'),
                                'intro_title' => Arr::get($tab, 'intro_title'),
                                'intro_body' => Arr::get($tab, 'intro_body'),
                                'content_title' => Arr::get($tab, 'content_title'),
                                'content_body' => Arr::get($tab, 'content_body'),
                                'extra_title' => Arr::get($tab, 'extra_title'),
                                'extra_body' => Arr::get($tab, 'extra_body'),
                                'display_title' => Arr::get($tab, 'display_title'),
                                'reward_title' => Arr::get($tab, 'reward_title'),
                                'closing_body' => Arr::get($tab, 'closing_body'),
                                'footer_text' => Arr::get($tab, 'footer_text'),
                                'button_text' => Arr::get($tab, 'button_text'),
                                'button_link' => Arr::get($tab, 'button_link'),
                                'extra_button_text' => Arr::get($tab, 'extra_button_text'),
                                'extra_button_link' => Arr::get($tab, 'extra_button_link'),
                                'image' => Arr::get($tab, 'image') ? api_asset(Arr::get($tab, 'image')) : null,
                                'image_2' => Arr::get($tab, 'image_2') ? api_asset(Arr::get($tab, 'image_2')) : null,
                                'items' => collect(Arr::get($tab, 'items', []))->map(function ($item) {
                                    return [
                                        'title' => Arr::get($item, 'title'),
                                        'description' => Arr::get($item, 'description'),
                                        'meta' => Arr::get($item, 'meta'),
                                        'submeta' => Arr::get($item, 'submeta'),
                                        'button_text' => Arr::get($item, 'button_text'),
                                        'button_link' => Arr::get($item, 'button_link'),
                                        'image' => Arr::get($item, 'image') ? api_asset(Arr::get($item, 'image')) : null,
                                    ];
                                })->values(),
                                'items_secondary' => collect(Arr::get($tab, 'items_secondary', []))->map(function ($item) {
                                    return [
                                        'title' => Arr::get($item, 'title'),
                                        'description' => Arr::get($item, 'description'),
                                        'meta' => Arr::get($item, 'meta'),
                                        'submeta' => Arr::get($item, 'submeta'),
                                        'button_text' => Arr::get($item, 'button_text'),
                                        'button_link' => Arr::get($item, 'button_link'),
                                        'image' => Arr::get($item, 'image') ? api_asset(Arr::get($item, 'image')) : null,
                                    ];
                                })->values(),
                                'statement_lines' => collect(Arr::get($tab, 'statement_lines', []))->map(function ($line) {
                                    return [
                                        'text' => Arr::get($line, 'text'),
                                    ];
                                })->values(),
                            ];
                        })->values(),
                        'bullets' => collect($bullets)->map(function ($bullet) {
                            return [
                                'text' => Arr::get($bullet, 'text'),
                            ];
                        })->values(),
                        'vision_title' => Arr::get($settings, 'vision_title'),
                        'vision_text' => Arr::get($settings, 'vision_text'),
                        'mission_title' => Arr::get($settings, 'mission_title'),
                        'mission_text' => Arr::get($settings, 'mission_text'),
                        'quote_text' => Arr::get($settings, 'quote_text'),
                        'author' => Arr::get($settings, 'author'),
                        'product_source_type' => Arr::get($settings, 'product_source_type'),
                        'product_category_id' => Arr::get($settings, 'product_category_id'),
                        'product_brand_id' => Arr::get($settings, 'product_brand_id'),
                        'related_products_limit' => Arr::get($settings, 'related_products_limit'),
                        'youtube_urls' => collect(Arr::get($settings, 'youtube_urls', []))->values(),
                        'gallery_images' => collect($galleryImages)->map(function ($imageId) {
                            return api_asset($imageId);
                        })->values(),
                    ],
                ];
            })->values(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public function with($request)
    {
        return [
            'success' => true,
            'status' => 200
        ];
    }
}
