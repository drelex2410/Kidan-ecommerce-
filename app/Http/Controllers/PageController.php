<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\PageSection;
use App\Models\PageTranslation;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;


class PageController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $lang = env('DEFAULT_LANGUAGE');
        $sectionTypes = PageSection::TYPES;

        return view('backend.website_settings.pages.create', compact('lang', 'sectionTypes'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $this->validatePageRequest($request);

        $page = new Page;
        $page->title = $validated['title'];
        $sanitizedSlug = $this->sanitizeSlug($validated['slug']);

        if (Page::where('slug', $sanitizedSlug)->first() == null) {
            $page->slug             = $sanitizedSlug;
            $page->type             = "custom_page";
            $page->content          = $validated['content'] ?? null;
            $page->meta_title       = $validated['meta_title'] ?? null;
            $page->meta_description = $validated['meta_description'] ?? null;
            $page->keywords         = $validated['keywords'] ?? null;
            $page->meta_image       = $validated['meta_image'] ?? null;
            $page->is_published     = (bool) ($validated['is_published'] ?? true);
            $page->save();

            $page_translation           = PageTranslation::firstOrNew(['lang' => env('DEFAULT_LANGUAGE'), 'page_id' => $page->id]);
            $page_translation->title    = $validated['title'];
            $page_translation->content  = $validated['content'] ?? '';
            $page_translation->save();

            $this->syncSections($page, $validated['sections'] ?? []);

            flash(translate('New page has been created successfully'))->success();
            return redirect()->route('website.pages');
        }

        flash(translate('Slug has been used already'))->warning();
        return back();
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id)
    {
        $lang = $request->lang;
        $page_name = $request->page;
        $sectionTypes = PageSection::TYPES;
        $page = Page::with('sections')->where('slug', $id)->first();
        if ($page != null) {
            if ($page_name == 'home') {
                return view('backend.website_settings.pages.home_page_edit', compact('page', 'lang'));
            } else {
                return view('backend.website_settings.pages.edit', compact('page', 'lang', 'sectionTypes'));
            }
        }
        abort(404);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validated = $this->validatePageRequest($request, $id);
        $page = Page::findOrFail($id);
        $sanitizedSlug = $this->sanitizeSlug($validated['slug']);

        if (Page::where('id', '!=', $id)->where('slug', $sanitizedSlug)->first() == null) {
            if ($page->type == 'custom_page') {
                $page->slug           = $sanitizedSlug;
            }

            if (($validated['lang'] ?? env("DEFAULT_LANGUAGE")) == env("DEFAULT_LANGUAGE")) {
                $page->title          = $validated['title'];
                $page->content        = $validated['content'] ?? null;
            }

            $page->meta_title       = $validated['meta_title'] ?? null;
            $page->meta_description = $validated['meta_description'] ?? null;
            $page->keywords         = $validated['keywords'] ?? null;
            $page->meta_image       = $validated['meta_image'] ?? null;
            $page->is_published     = (bool) ($validated['is_published'] ?? true);
            $page->save();

            $page_translation           = PageTranslation::firstOrNew(['lang' => $validated['lang'] ?? env('DEFAULT_LANGUAGE'), 'page_id' => $page->id]);
            $page_translation->title    = $validated['title'];
            $page_translation->content  = $validated['content'] ?? '';
            $page_translation->save();

            if (($validated['lang'] ?? env('DEFAULT_LANGUAGE')) == env('DEFAULT_LANGUAGE')) {
                $this->syncSections($page, $validated['sections'] ?? []);
            }

            flash(translate('Page has been updated successfully'))->success();
            return redirect()->route('website.pages');
        }

        flash(translate('Slug has been used already'))->warning();
        return back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $page = Page::findOrFail($id);
        foreach ($page->page_translations as $key => $page_translation) {
            $page_translation->delete();
        }

        if (Page::destroy($id)) {
            flash(translate('Page has been deleted successfully'))->success();
            return redirect()->back();
        }
        return back();
    }

    public function show_custom_page($slug)
    {
        $page = Page::with('visibleSections')->published()->where('slug', $slug)->first();
        if ($page != null) {
            return view('frontend.custom_page', compact('page'));
        }
        abort(404);
    }

    private function validatePageRequest(Request $request, ?int $pageId = null): array
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255',
            'lang' => 'nullable|string|max:20',
            'content' => 'nullable|string',
            'meta_title' => 'nullable|string',
            'meta_description' => 'nullable|string',
            'keywords' => 'nullable|string',
            'meta_image' => 'nullable',
            'is_published' => 'nullable|boolean',
            'sections' => 'nullable|array',
            'sections.*.type' => 'required_with:sections|string|in:' . implode(',', array_keys(PageSection::TYPES)),
            'sections.*.title' => 'nullable|string|max:255',
            'sections.*.subtitle' => 'nullable|string|max:255',
            'sections.*.content' => 'nullable|string',
            'sections.*.button_text' => 'nullable|string|max:255',
            'sections.*.button_link' => 'nullable|string|max:255',
            'sections.*.image' => 'nullable',
            'sections.*.image_2' => 'nullable',
            'sections.*.sort_order' => 'nullable|integer|min:0',
            'sections.*.is_visible' => 'nullable|boolean',
            'sections.*.settings' => 'nullable|array',
            'sections.*.settings.tabs' => 'nullable|array',
            'sections.*.settings.bullets' => 'nullable|array',
            'sections.*.settings.max_width' => 'nullable|integer|min:0',
            'sections.*.settings.title_max_width' => 'nullable|integer|min:0',
            'sections.*.settings.default_tab' => 'nullable',
            'sections.*.settings.tab_visibility' => 'nullable|string|in:always,previous_tab_default_only',
            'sections.*.settings.text_align' => 'nullable|string|in:left,center,right',
            'sections.*.settings.alignment' => 'nullable|string|in:left,center,right',
            'sections.*.settings.image_position' => 'nullable|string|in:left,right',
            'sections.*.settings.background_style' => 'nullable|string|max:50',
            'sections.*.settings.panel_theme' => 'nullable|string|max:50',
            'sections.*.settings.image_alt' => 'nullable|string|max:255',
            'sections.*.settings.vision_title' => 'nullable|string|max:255',
            'sections.*.settings.vision_text' => 'nullable|string',
            'sections.*.settings.mission_title' => 'nullable|string|max:255',
            'sections.*.settings.mission_text' => 'nullable|string',
            'sections.*.settings.quote_text' => 'nullable|string',
            'sections.*.settings.author' => 'nullable|string|max:255',
            'sections.*.settings.spacing_size' => 'nullable|string|in:sm,md,lg,xl',
            'sections.*.settings.line_toggle' => 'nullable|boolean',
            'sections.*.settings.tabs.*.tab_label' => 'nullable|string|max:255',
            'sections.*.settings.tabs.*.layout' => 'nullable|string|max:100',
            'sections.*.settings.tabs.*.intro_title' => 'nullable|string|max:255',
            'sections.*.settings.tabs.*.intro_body' => 'nullable|string',
            'sections.*.settings.tabs.*.content_title' => 'nullable|string|max:255',
            'sections.*.settings.tabs.*.content_body' => 'nullable|string',
            'sections.*.settings.tabs.*.extra_title' => 'nullable|string|max:255',
            'sections.*.settings.tabs.*.extra_body' => 'nullable|string',
            'sections.*.settings.tabs.*.display_title' => 'nullable|string|max:255',
            'sections.*.settings.tabs.*.reward_title' => 'nullable|string|max:255',
            'sections.*.settings.tabs.*.closing_body' => 'nullable|string',
            'sections.*.settings.tabs.*.footer_text' => 'nullable|string',
            'sections.*.settings.tabs.*.button_text' => 'nullable|string|max:255',
            'sections.*.settings.tabs.*.button_link' => 'nullable|string|max:255',
            'sections.*.settings.tabs.*.extra_button_text' => 'nullable|string|max:255',
            'sections.*.settings.tabs.*.extra_button_link' => 'nullable|string|max:255',
            'sections.*.settings.tabs.*.image' => 'nullable',
            'sections.*.settings.tabs.*.image_2' => 'nullable',
            'sections.*.settings.tabs.*.items' => 'nullable|array',
            'sections.*.settings.tabs.*.items_secondary' => 'nullable|array',
            'sections.*.settings.tabs.*.statement_lines' => 'nullable|array',
            'sections.*.settings.tabs.*.items.*.title' => 'nullable|string|max:255',
            'sections.*.settings.tabs.*.items.*.description' => 'nullable|string',
            'sections.*.settings.tabs.*.items.*.meta' => 'nullable|string|max:255',
            'sections.*.settings.tabs.*.items.*.submeta' => 'nullable|string|max:255',
            'sections.*.settings.tabs.*.items.*.button_text' => 'nullable|string|max:255',
            'sections.*.settings.tabs.*.items.*.button_link' => 'nullable|string|max:255',
            'sections.*.settings.tabs.*.items.*.image' => 'nullable',
            'sections.*.settings.tabs.*.items_secondary.*.title' => 'nullable|string|max:255',
            'sections.*.settings.tabs.*.items_secondary.*.description' => 'nullable|string',
            'sections.*.settings.tabs.*.items_secondary.*.meta' => 'nullable|string|max:255',
            'sections.*.settings.tabs.*.items_secondary.*.submeta' => 'nullable|string|max:255',
            'sections.*.settings.tabs.*.items_secondary.*.button_text' => 'nullable|string|max:255',
            'sections.*.settings.tabs.*.items_secondary.*.button_link' => 'nullable|string|max:255',
            'sections.*.settings.tabs.*.items_secondary.*.image' => 'nullable',
            'sections.*.settings.tabs.*.statement_lines.*.text' => 'nullable|string|max:255',
            'sections.*.settings.bullets.*.text' => 'nullable|string|max:255',
            'sections.*.settings.items.*.title' => 'nullable|string|max:255',
            'sections.*.settings.items.*.description' => 'nullable|string',
            'sections.*.settings.product_source_type' => ['nullable', Rule::in(['category', 'brand'])],
            'sections.*.settings.product_category_id' => 'nullable|exists:categories,id',
            'sections.*.settings.product_brand_id' => 'nullable|exists:brands,id',
            'sections.*.settings.related_products_limit' => 'nullable|integer|min:1|max:12',
            'sections.*.settings.youtube_urls' => 'nullable|array',
            'sections.*.settings.youtube_urls.*' => [
                'nullable',
                'url',
                function ($attribute, $value, $fail) {
                    if ($value && ! $this->extractYoutubeId($value)) {
                        $fail(translate('Please enter a valid YouTube URL.'));
                    }
                },
            ],
        ]);
    }

    private function sanitizeSlug(string $slug): string
    {
        return preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $slug));
    }

    private function syncSections(Page $page, array $sections): void
    {
        $page->sections()->delete();

        foreach (collect($sections)->sortBy('sort_order')->values() as $index => $section) {
            $settings = $this->normalizeSectionSettings($section);

            $page->sections()->create([
                'section_key' => (string) Str::uuid(),
                'type' => $section['type'],
                'title' => Arr::get($section, 'title'),
                'subtitle' => Arr::get($section, 'subtitle'),
                'content' => Arr::get($section, 'content'),
                'button_text' => Arr::get($section, 'button_text'),
                'button_link' => Arr::get($section, 'button_link'),
                'image' => Arr::get($section, 'image'),
                'image_2' => Arr::get($section, 'image_2'),
                'settings_json' => $settings,
                'sort_order' => (int) Arr::get($section, 'sort_order', $index + 1),
                'is_visible' => (bool) Arr::get($section, 'is_visible', true),
            ]);
        }
    }

    private function normalizeSectionSettings(array $section): array
    {
        $settings = Arr::get($section, 'settings', []);
        $defaultTabKey = Arr::get($settings, 'default_tab');

        $featureItems = [];
        foreach (Arr::get($settings, 'items', []) as $item) {
            if (! Arr::get($item, 'title') && ! Arr::get($item, 'description') && ! Arr::get($item, 'image')) {
                continue;
            }

            $featureItems[] = [
                'title' => Arr::get($item, 'title'),
                'description' => Arr::get($item, 'description'),
                'image' => Arr::get($item, 'image'),
            ];
        }

        $tabs = [];
        $tabKeyMap = [];
        foreach (Arr::get($settings, 'tabs', []) as $tabKey => $tab) {
            if (
                ! Arr::get($tab, 'tab_label') &&
                ! Arr::get($tab, 'intro_title') &&
                ! Arr::get($tab, 'intro_body') &&
                ! Arr::get($tab, 'content_title') &&
                ! Arr::get($tab, 'content_body')
            ) {
                continue;
            }

            $tabKeyMap[(string) $tabKey] = count($tabs);
            $tabs[] = [
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
                'image' => Arr::get($tab, 'image'),
                'image_2' => Arr::get($tab, 'image_2'),
                'items' => $this->normalizeTabCollection(Arr::get($tab, 'items', [])),
                'items_secondary' => $this->normalizeTabCollection(Arr::get($tab, 'items_secondary', [])),
                'statement_lines' => $this->normalizeTabLines(Arr::get($tab, 'statement_lines', [])),
            ];
        }

        $defaultTabIndex = $tabKeyMap[(string) $defaultTabKey] ?? 0;

        $bullets = [];
        foreach (Arr::get($settings, 'bullets', []) as $bullet) {
            $text = Arr::get($bullet, 'text', Arr::get($bullet, 'title'));
            if (! $text) {
                continue;
            }

            $bullets[] = [
                'text' => $text,
            ];
        }

        $productSourceType = Arr::get($settings, 'product_source_type');

        return [
            'heading' => Arr::get($settings, 'heading', Arr::get($section, 'title')),
            'subheading' => Arr::get($settings, 'subheading', Arr::get($section, 'subtitle')),
            'alignment' => Arr::get($settings, 'alignment', 'left'),
            'image_position' => Arr::get($settings, 'image_position', 'left'),
            'background_style' => Arr::get($settings, 'background_style'),
            'panel_theme' => Arr::get($settings, 'panel_theme', Arr::get($settings, 'background_style')),
            'vision_title' => Arr::get($settings, 'vision_title'),
            'vision_text' => Arr::get($settings, 'vision_text'),
            'mission_title' => Arr::get($settings, 'mission_title'),
            'mission_text' => Arr::get($settings, 'mission_text'),
            'quote_text' => Arr::get($settings, 'quote_text'),
            'author' => Arr::get($settings, 'author'),
            'gallery_images' => Arr::get($settings, 'gallery_images'),
            'spacing_size' => Arr::get($settings, 'spacing_size', 'md'),
            'line_toggle' => (bool) Arr::get($settings, 'line_toggle', false),
            'max_width' => Arr::get($settings, 'max_width'),
            'text_align' => Arr::get($settings, 'text_align', 'center'),
            'title_max_width' => Arr::get($settings, 'title_max_width'),
            'image_alt' => Arr::get($settings, 'image_alt'),
            'default_tab' => (int) $defaultTabIndex,
            'tab_visibility' => Arr::get($settings, 'tab_visibility', 'always'),
            'items' => $featureItems,
            'tabs' => $tabs,
            'bullets' => $bullets,
            'product_source_type' => $productSourceType,
            'product_category_id' => $productSourceType === 'category' ? Arr::get($settings, 'product_category_id') : null,
            'product_brand_id' => $productSourceType === 'brand' ? Arr::get($settings, 'product_brand_id') : null,
            'related_products_limit' => Arr::get($settings, 'related_products_limit', 4),
            'youtube_urls' => collect(Arr::get($settings, 'youtube_urls', []))
                ->map(fn ($url) => is_string($url) ? trim($url) : null)
                ->filter()
                ->values()
                ->all(),
        ];
    }

    private function normalizeTabCollection(array $items): array
    {
        $normalized = [];

        foreach ($items as $item) {
            if (
                ! Arr::get($item, 'title') &&
                ! Arr::get($item, 'description') &&
                ! Arr::get($item, 'meta') &&
                ! Arr::get($item, 'submeta') &&
                ! Arr::get($item, 'image')
            ) {
                continue;
            }

            $normalized[] = [
                'title' => Arr::get($item, 'title'),
                'description' => Arr::get($item, 'description'),
                'meta' => Arr::get($item, 'meta'),
                'submeta' => Arr::get($item, 'submeta'),
                'button_text' => Arr::get($item, 'button_text'),
                'button_link' => Arr::get($item, 'button_link'),
                'image' => Arr::get($item, 'image'),
            ];
        }

        return $normalized;
    }

    private function normalizeTabLines(array $items): array
    {
        $normalized = [];

        foreach ($items as $item) {
            $text = Arr::get($item, 'text');
            if (! $text) {
                continue;
            }

            $normalized[] = [
                'text' => $text,
            ];
        }

        return $normalized;
    }

    private function extractYoutubeId(string $url): ?string
    {
        $parts = parse_url($url);
        $host = strtolower($parts['host'] ?? '');
        $path = trim($parts['path'] ?? '', '/');

        if (in_array($host, ['youtu.be'], true) && $path !== '') {
            return $path;
        }

        parse_str($parts['query'] ?? '', $query);

        if (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtube-nocookie.com', 'www.youtube-nocookie.com'], true)) {
            if (!empty($query['v'])) {
                return $query['v'];
            }

            if (str_starts_with($path, 'embed/')) {
                return trim(substr($path, 6));
            }

            if (str_starts_with($path, 'shorts/')) {
                return trim(substr($path, 7));
            }
        }

        return null;
    }
}
