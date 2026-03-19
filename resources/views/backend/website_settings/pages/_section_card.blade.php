@php
    $sectionData = $sectionData ?? [];
    $index = $index ?? '__INDEX__';
    $settings = $sectionData['settings'] ?? [];
    $featureItems = $settings['items'] ?? [];
    $tabs = $settings['tabs'] ?? [];
    $bullets = $settings['bullets'] ?? [];
    $journalYoutubeUrls = $settings['youtube_urls'] ?? [''];
    if (empty($journalYoutubeUrls)) {
        $journalYoutubeUrls = [''];
    }
    $currentType = $sectionData['type'] ?? 'about_hero_split';
    $defaultTabKey = (string) ($settings['default_tab'] ?? 0);
    $collapseId = 'page-section-body-' . $index;
    $isInitiallyOpen = is_numeric($index) && (int) $index === 0;
    $summaryText = trim((string) ($sectionData['title'] ?? $sectionData['subtitle'] ?? ''));
    if ($summaryText === '' && !empty($sectionData['content'])) {
        $summaryText = \Illuminate\Support\Str::limit(trim(strip_tags($sectionData['content'])), 110);
    }
    $tabLayouts = [
        'basic' => 'Basic Content',
        'career_showcase' => 'Career Showcase',
        'tribe_rewards' => 'Tribe Rewards',
        'partnership_cta' => 'Partnership CTA',
        'youth_program' => 'Youth Program',
        'press_events' => 'Press & Events',
    ];
@endphp

<div class="card mb-3 page-section-card page-section-accordion-item" data-section-index="{{ $index }}" data-section-body-id="{{ $collapseId }}">
    <div class="card-header page-section-card-header">
        <div class="d-flex flex-wrap justify-content-between align-items-start" style="gap: .75rem;">
            <button
                type="button"
                class="btn btn-link text-reset text-left p-0 flex-grow-1 section-accordion-toggle"
                data-section-toggle
                aria-expanded="{{ $isInitiallyOpen ? 'true' : 'false' }}"
            >
                <div class="d-flex align-items-start" style="gap: .75rem;">
                    <span class="section-accordion-icon" aria-hidden="true">
                        <i class="las la-angle-down"></i>
                    </span>
                    <div class="section-card-heading-group">
                        <div class="d-flex flex-wrap align-items-center" style="gap: .5rem;">
                            <h6 class="mb-0 fw-600 section-card-title">
                                {{ \App\Models\PageSection::TYPES[$currentType] ?? translate('Page Section') }}
                            </h6>
                            <small class="text-muted">#<span class="section-order-label">{{ is_numeric($index) ? $index + 1 : 1 }}</span></small>
                        </div>
                        <small class="text-muted d-block mt-1 section-card-summary">{{ $summaryText ?: translate('No content summary yet') }}</small>
                    </div>
                </div>
            </button>
            <div class="d-flex align-items-center" style="gap: .5rem;">
                <label class="mb-0 d-flex align-items-center" style="gap: .35rem;">
                    <input type="checkbox" class="section-visible-toggle" {{ !isset($sectionData['is_visible']) || $sectionData['is_visible'] ? 'checked' : '' }}>
                    <span>{{ translate('Visible') }}</span>
                </label>
                <button type="button" class="btn btn-soft-secondary btn-sm move-section-up">{{ translate('Up') }}</button>
                <button type="button" class="btn btn-soft-secondary btn-sm move-section-down">{{ translate('Down') }}</button>
                <button type="button" class="btn btn-soft-danger btn-sm remove-section">{{ translate('Delete') }}</button>
            </div>
        </div>
    </div>

    <div id="{{ $collapseId }}" class="page-section-collapse collapse {{ $isInitiallyOpen ? 'show' : '' }}">
        <div class="card-body">
            <input type="hidden" class="section-sort-order" name="sections[{{ $index }}][sort_order]" value="{{ $sectionData['sort_order'] ?? 0 }}">
            <input type="hidden" class="section-visible-input" name="sections[{{ $index }}][is_visible]" value="{{ !isset($sectionData['is_visible']) || $sectionData['is_visible'] ? 1 : 0 }}">

        <div class="form-group">
            <label>{{ translate('Section Type') }}</label>
            <select class="form-control section-type-select" name="sections[{{ $index }}][type]">
                @foreach (\App\Models\PageSection::TYPES as $value => $label)
                    <option value="{{ $value }}" {{ $currentType === $value ? 'selected' : '' }}>{{ translate($label) }}</option>
                @endforeach
            </select>
        </div>

        <div class="section-fields section-field-rich">
            <div class="form-group" data-common-field="title">
                <label>{{ translate('Title / Heading') }}</label>
                <input type="text" class="form-control section-heading-input" name="sections[{{ $index }}][title]" value="{{ $sectionData['title'] ?? '' }}">
            </div>

            <div class="form-group" data-common-field="subtitle">
                <label>{{ translate('Subtitle / Intro') }}</label>
                <input type="text" class="form-control" name="sections[{{ $index }}][subtitle]" value="{{ $sectionData['subtitle'] ?? '' }}">
            </div>

            <div class="form-group" data-common-field="content">
                <label>{{ translate('Body / Rich Text / HTML') }}</label>
                <textarea class="form-control" rows="5" name="sections[{{ $index }}][content]">{{ $sectionData['content'] ?? '' }}</textarea>
                <small class="form-text text-muted">{{ translate('Use this for paragraph copy. HTML tags such as strong, br, and links are supported when needed.') }}</small>
            </div>

            <div class="form-row">
                <div class="col-md-6" data-common-field="button">
                    <div class="form-group">
                        <label>{{ translate('Button Text') }}</label>
                        <input type="text" class="form-control" name="sections[{{ $index }}][button_text]" value="{{ $sectionData['button_text'] ?? '' }}">
                    </div>
                </div>
                <div class="col-md-6" data-common-field="button">
                    <div class="form-group">
                        <label>{{ translate('Button Link') }}</label>
                        <input type="text" class="form-control" name="sections[{{ $index }}][button_link]" value="{{ $sectionData['button_link'] ?? '' }}">
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="col-md-6" data-common-field="image">
                    <div class="form-group">
                        <label>{{ translate('Primary Image') }}</label>
                        <div class="input-group" data-toggle="aizuploader" data-type="image" data-multiple="false">
                            <div class="input-group-prepend">
                                <div class="input-group-text bg-soft-secondary font-weight-medium">{{ translate('Browse') }}</div>
                            </div>
                            <div class="form-control file-amount">{{ translate('Choose File') }}</div>
                            <input type="hidden" name="sections[{{ $index }}][image]" class="selected-files" value="{{ $sectionData['image'] ?? '' }}">
                        </div>
                        <div class="file-preview box sm"></div>
                    </div>
                </div>
                <div class="col-md-6" data-common-field="image_2">
                    <div class="form-group">
                        <label>{{ translate('Secondary Image / Badge Logo') }}</label>
                        <div class="input-group" data-toggle="aizuploader" data-type="image" data-multiple="false">
                            <div class="input-group-prepend">
                                <div class="input-group-text bg-soft-secondary font-weight-medium">{{ translate('Browse') }}</div>
                            </div>
                            <div class="form-control file-amount">{{ translate('Choose File') }}</div>
                            <input type="hidden" name="sections[{{ $index }}][image_2]" class="selected-files" value="{{ $sectionData['image_2'] ?? '' }}">
                        </div>
                        <div class="file-preview box sm"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="type-specific-fields">
            <div class="type-panel" data-type-panel="about_hero_split">
                <div class="alert alert-soft-info mb-3">
                    {{ translate('Uses the title, subtitle, primary image, and secondary image fields above. The secondary image is rendered as the overlapping hero badge/logo.') }}
                </div>
                <div class="form-row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>{{ translate('Title Alignment') }}</label>
                            <select class="form-control" name="sections[{{ $index }}][settings][alignment]">
                                @foreach (['left' => 'Left', 'center' => 'Center', 'right' => 'Right'] as $value => $label)
                                    <option value="{{ $value }}" {{ ($settings['alignment'] ?? 'left') === $value ? 'selected' : '' }}>{{ translate($label) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>{{ translate('Title Max Width (px)') }}</label>
                            <input type="number" min="0" class="form-control" name="sections[{{ $index }}][settings][title_max_width]" value="{{ $settings['title_max_width'] ?? '' }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>{{ translate('Hero Image Alt Text') }}</label>
                            <input type="text" class="form-control" name="sections[{{ $index }}][settings][image_alt]" value="{{ $settings['image_alt'] ?? '' }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="type-panel" data-type-panel="editorial_intro">
                <div class="alert alert-soft-info mb-3">
                    {{ translate('Uses the body field above for centered editorial copy. Add strong tags where you want emphasis.') }}
                </div>
                <div class="form-row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>{{ translate('Text Max Width (px)') }}</label>
                            <input type="number" min="0" class="form-control" name="sections[{{ $index }}][settings][max_width]" value="{{ $settings['max_width'] ?? '' }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>{{ translate('Text Align') }}</label>
                            <select class="form-control" name="sections[{{ $index }}][settings][text_align]">
                                @foreach (['left' => 'Left', 'center' => 'Center', 'right' => 'Right'] as $value => $label)
                                    <option value="{{ $value }}" {{ ($settings['text_align'] ?? 'center') === $value ? 'selected' : '' }}>{{ translate($label) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="type-panel" data-type-panel="tabs_content">
                <div class="alert alert-soft-info mb-3">
                    {{ translate('Uses a real in-page tab switcher. Choose a layout per tab, then fill in the text, media, and repeater content used by that tab design.') }}
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="mb-0">{{ translate('Tabs') }}</label>
                    <button type="button" class="btn btn-soft-primary btn-sm add-tab-item">{{ translate('Add Tab') }}</button>
                </div>
                <div class="tab-items">
                    @foreach ($tabs as $tabIndex => $tab)
                        @php
                            $tabItems = $tab['items'] ?? [];
                            $tabSecondaryItems = $tab['items_secondary'] ?? [];
                            $tabStatementLines = $tab['statement_lines'] ?? [];
                        @endphp
                        <div class="border rounded p-3 mb-2 tab-item" data-tab-index="{{ $tabIndex }}">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <strong>{{ translate('Tab Item') }}</strong>
                                <div class="d-flex align-items-center" style="gap: .75rem;">
                                    <label class="mb-0 d-flex align-items-center" style="gap: .35rem;">
                                        <input type="radio" name="sections[{{ $index }}][settings][default_tab]" value="{{ $tabIndex }}" {{ $defaultTabKey === (string) $tabIndex ? 'checked' : '' }}>
                                        <span>{{ translate('Default') }}</span>
                                    </label>
                                    <button type="button" class="btn btn-soft-danger btn-sm remove-tab-item">{{ translate('Remove') }}</button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>{{ translate('Tab Label') }}</label>
                                <input type="text" class="form-control" name="sections[{{ $index }}][settings][tabs][{{ $tabIndex }}][tab_label]" value="{{ $tab['tab_label'] ?? '' }}">
                            </div>
                            <div class="form-group">
                                <label>{{ translate('Tab Layout') }}</label>
                                <select class="form-control" name="sections[{{ $index }}][settings][tabs][{{ $tabIndex }}][layout]">
                                    @foreach ($tabLayouts as $layoutValue => $layoutLabel)
                                        <option value="{{ $layoutValue }}" {{ ($tab['layout'] ?? 'basic') === $layoutValue ? 'selected' : '' }}>{{ translate($layoutLabel) }}</option>
                                    @endforeach
                                </select>
                                <small class="form-text text-muted">{{ translate('Keep About Us on Basic Content. Use the richer layouts for the visual tabs.') }}</small>
                            </div>
                            <div class="form-group">
                                <label>{{ translate('Intro Title') }}</label>
                                <input type="text" class="form-control" name="sections[{{ $index }}][settings][tabs][{{ $tabIndex }}][intro_title]" value="{{ $tab['intro_title'] ?? '' }}">
                            </div>
                            <div class="form-group">
                                <label>{{ translate('Intro Body') }}</label>
                                <textarea class="form-control" rows="4" name="sections[{{ $index }}][settings][tabs][{{ $tabIndex }}][intro_body]">{{ $tab['intro_body'] ?? '' }}</textarea>
                            </div>
                            <div class="form-group">
                                <label>{{ translate('Content Title') }}</label>
                                <input type="text" class="form-control" name="sections[{{ $index }}][settings][tabs][{{ $tabIndex }}][content_title]" value="{{ $tab['content_title'] ?? '' }}">
                            </div>
                            <div class="form-group">
                                <label>{{ translate('Content Body') }}</label>
                                <textarea class="form-control" rows="4" name="sections[{{ $index }}][settings][tabs][{{ $tabIndex }}][content_body]">{{ $tab['content_body'] ?? '' }}</textarea>
                            </div>
                            <div class="form-row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{ translate('Extra Section Title') }}</label>
                                        <input type="text" class="form-control" name="sections[{{ $index }}][settings][tabs][{{ $tabIndex }}][extra_title]" value="{{ $tab['extra_title'] ?? '' }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{ translate('Display Title / Graphic Text') }}</label>
                                        <textarea class="form-control" rows="2" name="sections[{{ $index }}][settings][tabs][{{ $tabIndex }}][display_title]">{{ $tab['display_title'] ?? '' }}</textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>{{ translate('Extra Section Body') }}</label>
                                <textarea class="form-control" rows="4" name="sections[{{ $index }}][settings][tabs][{{ $tabIndex }}][extra_body]">{{ $tab['extra_body'] ?? '' }}</textarea>
                            </div>
                            <div class="form-row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{ translate('Reward Title / Accent Text') }}</label>
                                        <input type="text" class="form-control" name="sections[{{ $index }}][settings][tabs][{{ $tabIndex }}][reward_title]" value="{{ $tab['reward_title'] ?? '' }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{ translate('Footer Text') }}</label>
                                        <input type="text" class="form-control" name="sections[{{ $index }}][settings][tabs][{{ $tabIndex }}][footer_text]" value="{{ $tab['footer_text'] ?? '' }}">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>{{ translate('Closing Body') }}</label>
                                <textarea class="form-control" rows="4" name="sections[{{ $index }}][settings][tabs][{{ $tabIndex }}][closing_body]">{{ $tab['closing_body'] ?? '' }}</textarea>
                            </div>
                            <div class="form-row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{ translate('Primary Tab Image') }}</label>
                                        <div class="input-group" data-toggle="aizuploader" data-type="image" data-multiple="false">
                                            <div class="input-group-prepend">
                                                <div class="input-group-text bg-soft-secondary font-weight-medium">{{ translate('Browse') }}</div>
                                            </div>
                                            <div class="form-control file-amount">{{ translate('Choose File') }}</div>
                                            <input type="hidden" name="sections[{{ $index }}][settings][tabs][{{ $tabIndex }}][image]" class="selected-files" value="{{ $tab['image'] ?? '' }}">
                                        </div>
                                        <div class="file-preview box sm"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{ translate('Secondary Tab Image') }}</label>
                                        <div class="input-group" data-toggle="aizuploader" data-type="image" data-multiple="false">
                                            <div class="input-group-prepend">
                                                <div class="input-group-text bg-soft-secondary font-weight-medium">{{ translate('Browse') }}</div>
                                            </div>
                                            <div class="form-control file-amount">{{ translate('Choose File') }}</div>
                                            <input type="hidden" name="sections[{{ $index }}][settings][tabs][{{ $tabIndex }}][image_2]" class="selected-files" value="{{ $tab['image_2'] ?? '' }}">
                                        </div>
                                        <div class="file-preview box sm"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{ translate('Primary Button Text') }}</label>
                                        <input type="text" class="form-control" name="sections[{{ $index }}][settings][tabs][{{ $tabIndex }}][button_text]" value="{{ $tab['button_text'] ?? '' }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{ translate('Primary Button Link') }}</label>
                                        <input type="text" class="form-control" name="sections[{{ $index }}][settings][tabs][{{ $tabIndex }}][button_link]" value="{{ $tab['button_link'] ?? '' }}">
                                    </div>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{ translate('Secondary Button Text') }}</label>
                                        <input type="text" class="form-control" name="sections[{{ $index }}][settings][tabs][{{ $tabIndex }}][extra_button_text]" value="{{ $tab['extra_button_text'] ?? '' }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-0">
                                        <label>{{ translate('Secondary Button Link') }}</label>
                                        <input type="text" class="form-control" name="sections[{{ $index }}][settings][tabs][{{ $tabIndex }}][extra_button_link]" value="{{ $tab['extra_button_link'] ?? '' }}">
                                    </div>
                                </div>
                            </div>

                            <div class="border-top pt-3 mt-3 tab-repeat-group" data-collection="items">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <strong>{{ translate('Primary Items') }}</strong>
                                        <small class="d-block text-muted">{{ translate('Used for team members, tribe tiers, youth features, press cards, or bullet-style entries depending on the chosen layout.') }}</small>
                                    </div>
                                    <button type="button" class="btn btn-soft-primary btn-sm add-tab-collection-item" data-collection="items">{{ translate('Add Primary Item') }}</button>
                                </div>
                                <div class="tab-collection-items">
                                    @foreach ($tabItems as $itemIndex => $item)
                                        <div class="border rounded p-3 mb-2 tab-collection-item" data-collection-item-index="{{ $itemIndex }}">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <strong>{{ translate('Primary Item') }}</strong>
                                                <button type="button" class="btn btn-soft-danger btn-sm remove-tab-collection-item">{{ translate('Remove') }}</button>
                                            </div>
                                            <div class="form-row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>{{ translate('Item Title') }}</label>
                                                        <input type="text" class="form-control" name="sections[{{ $index }}][settings][tabs][{{ $tabIndex }}][items][{{ $itemIndex }}][title]" value="{{ $item['title'] ?? '' }}">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>{{ translate('Meta Label') }}</label>
                                                        <input type="text" class="form-control" name="sections[{{ $index }}][settings][tabs][{{ $tabIndex }}][items][{{ $itemIndex }}][meta]" value="{{ $item['meta'] ?? '' }}">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label>{{ translate('Item Description') }}</label>
                                                <textarea class="form-control" rows="3" name="sections[{{ $index }}][settings][tabs][{{ $tabIndex }}][items][{{ $itemIndex }}][description]">{{ $item['description'] ?? '' }}</textarea>
                                            </div>
                                            <div class="form-row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>{{ translate('Sub Meta') }}</label>
                                                        <input type="text" class="form-control" name="sections[{{ $index }}][settings][tabs][{{ $tabIndex }}][items][{{ $itemIndex }}][submeta]" value="{{ $item['submeta'] ?? '' }}">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>{{ translate('Item Image') }}</label>
                                                        <div class="input-group" data-toggle="aizuploader" data-type="image" data-multiple="false">
                                                            <div class="input-group-prepend">
                                                                <div class="input-group-text bg-soft-secondary font-weight-medium">{{ translate('Browse') }}</div>
                                                            </div>
                                                            <div class="form-control file-amount">{{ translate('Choose File') }}</div>
                                                            <input type="hidden" name="sections[{{ $index }}][settings][tabs][{{ $tabIndex }}][items][{{ $itemIndex }}][image]" class="selected-files" value="{{ $item['image'] ?? '' }}">
                                                        </div>
                                                        <div class="file-preview box sm"></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>{{ translate('Item Button Text') }}</label>
                                                        <input type="text" class="form-control" name="sections[{{ $index }}][settings][tabs][{{ $tabIndex }}][items][{{ $itemIndex }}][button_text]" value="{{ $item['button_text'] ?? '' }}">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group mb-0">
                                                        <label>{{ translate('Item Button Link') }}</label>
                                                        <input type="text" class="form-control" name="sections[{{ $index }}][settings][tabs][{{ $tabIndex }}][items][{{ $itemIndex }}][button_link]" value="{{ $item['button_link'] ?? '' }}">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="border-top pt-3 mt-3 tab-repeat-group" data-collection="items_secondary">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <strong>{{ translate('Secondary Items') }}</strong>
                                        <small class="d-block text-muted">{{ translate('Used for the extra list or card collection in layouts like Career and Press & Events.') }}</small>
                                    </div>
                                    <button type="button" class="btn btn-soft-primary btn-sm add-tab-collection-item" data-collection="items_secondary">{{ translate('Add Secondary Item') }}</button>
                                </div>
                                <div class="tab-collection-items">
                                    @foreach ($tabSecondaryItems as $itemIndex => $item)
                                        <div class="border rounded p-3 mb-2 tab-collection-item" data-collection-item-index="{{ $itemIndex }}">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <strong>{{ translate('Secondary Item') }}</strong>
                                                <button type="button" class="btn btn-soft-danger btn-sm remove-tab-collection-item">{{ translate('Remove') }}</button>
                                            </div>
                                            <div class="form-row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>{{ translate('Item Title') }}</label>
                                                        <input type="text" class="form-control" name="sections[{{ $index }}][settings][tabs][{{ $tabIndex }}][items_secondary][{{ $itemIndex }}][title]" value="{{ $item['title'] ?? '' }}">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>{{ translate('Meta Label') }}</label>
                                                        <input type="text" class="form-control" name="sections[{{ $index }}][settings][tabs][{{ $tabIndex }}][items_secondary][{{ $itemIndex }}][meta]" value="{{ $item['meta'] ?? '' }}">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label>{{ translate('Item Description') }}</label>
                                                <textarea class="form-control" rows="3" name="sections[{{ $index }}][settings][tabs][{{ $tabIndex }}][items_secondary][{{ $itemIndex }}][description]">{{ $item['description'] ?? '' }}</textarea>
                                            </div>
                                            <div class="form-row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>{{ translate('Sub Meta') }}</label>
                                                        <input type="text" class="form-control" name="sections[{{ $index }}][settings][tabs][{{ $tabIndex }}][items_secondary][{{ $itemIndex }}][submeta]" value="{{ $item['submeta'] ?? '' }}">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>{{ translate('Item Image') }}</label>
                                                        <div class="input-group" data-toggle="aizuploader" data-type="image" data-multiple="false">
                                                            <div class="input-group-prepend">
                                                                <div class="input-group-text bg-soft-secondary font-weight-medium">{{ translate('Browse') }}</div>
                                                            </div>
                                                            <div class="form-control file-amount">{{ translate('Choose File') }}</div>
                                                            <input type="hidden" name="sections[{{ $index }}][settings][tabs][{{ $tabIndex }}][items_secondary][{{ $itemIndex }}][image]" class="selected-files" value="{{ $item['image'] ?? '' }}">
                                                        </div>
                                                        <div class="file-preview box sm"></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>{{ translate('Item Button Text') }}</label>
                                                        <input type="text" class="form-control" name="sections[{{ $index }}][settings][tabs][{{ $tabIndex }}][items_secondary][{{ $itemIndex }}][button_text]" value="{{ $item['button_text'] ?? '' }}">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group mb-0">
                                                        <label>{{ translate('Item Button Link') }}</label>
                                                        <input type="text" class="form-control" name="sections[{{ $index }}][settings][tabs][{{ $tabIndex }}][items_secondary][{{ $itemIndex }}][button_link]" value="{{ $item['button_link'] ?? '' }}">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="border-top pt-3 mt-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <strong>{{ translate('Statement Lines') }}</strong>
                                        <small class="d-block text-muted">{{ translate('Used for stacked bold lines in layouts like Kidan Youth.') }}</small>
                                    </div>
                                    <button type="button" class="btn btn-soft-primary btn-sm add-tab-statement-line">{{ translate('Add Line') }}</button>
                                </div>
                                <div class="tab-statement-lines">
                                    @foreach ($tabStatementLines as $lineIndex => $line)
                                        <div class="border rounded p-3 mb-2 tab-statement-line" data-line-index="{{ $lineIndex }}">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <strong>{{ translate('Statement Line') }}</strong>
                                                <button type="button" class="btn btn-soft-danger btn-sm remove-tab-statement-line">{{ translate('Remove') }}</button>
                                            </div>
                                            <div class="form-group mb-0">
                                                <label>{{ translate('Line Text') }}</label>
                                                <input type="text" class="form-control" name="sections[{{ $index }}][settings][tabs][{{ $tabIndex }}][statement_lines][{{ $lineIndex }}][text]" value="{{ $line['text'] ?? '' }}">
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="type-panel" data-type-panel="image_content_panel">
                <div class="alert alert-soft-info mb-3">
                    {{ translate('Uses the primary image, title, subtitle, and body fields above. Add bullet points below and choose the panel theme.') }}
                </div>
                <div class="form-group">
                    <label>{{ translate('Tab Visibility') }}</label>
                    <select class="form-control" name="sections[{{ $index }}][settings][tab_visibility]">
                        <option value="always" {{ ($settings['tab_visibility'] ?? 'always') === 'always' ? 'selected' : '' }}>{{ translate('Always show') }}</option>
                        <option value="previous_tab_default_only" {{ ($settings['tab_visibility'] ?? 'always') === 'previous_tab_default_only' ? 'selected' : '' }}>{{ translate('Only show when the previous tabs section is on its default tab') }}</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>{{ translate('Panel Theme') }}</label>
                    <select class="form-control" name="sections[{{ $index }}][settings][panel_theme]">
                        @foreach (['mocha' => 'Mocha Brown', 'ink' => 'Ink', 'sand' => 'Soft Sand'] as $value => $label)
                            <option value="{{ $value }}" {{ ($settings['panel_theme'] ?? 'mocha') === $value ? 'selected' : '' }}>{{ translate($label) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="mb-0">{{ translate('Bullet Items') }}</label>
                    <button type="button" class="btn btn-soft-primary btn-sm add-bullet-item">{{ translate('Add Bullet') }}</button>
                </div>
                <div class="bullet-items">
                    @foreach ($bullets as $bulletIndex => $bullet)
                        <div class="border rounded p-3 mb-2 bullet-item" data-bullet-index="{{ $bulletIndex }}">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <strong>{{ translate('Bullet') }}</strong>
                                <button type="button" class="btn btn-soft-danger btn-sm remove-bullet-item">{{ translate('Remove') }}</button>
                            </div>
                            <div class="form-group mb-0">
                                <label>{{ translate('Bullet Text') }}</label>
                                <input type="text" class="form-control" name="sections[{{ $index }}][settings][bullets][{{ $bulletIndex }}][text]" value="{{ $bullet['text'] ?? '' }}">
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="type-panel" data-type-panel="vision_mission_split">
                <div class="alert alert-soft-info mb-3">
                    {{ translate('Uses the primary image above for the right-side visual and the fields below for the vision and mission copy.') }}
                </div>
                <div class="form-group">
                    <label>{{ translate('Tab Visibility') }}</label>
                    <select class="form-control" name="sections[{{ $index }}][settings][tab_visibility]">
                        <option value="always" {{ ($settings['tab_visibility'] ?? 'always') === 'always' ? 'selected' : '' }}>{{ translate('Always show') }}</option>
                        <option value="previous_tab_default_only" {{ ($settings['tab_visibility'] ?? 'always') === 'previous_tab_default_only' ? 'selected' : '' }}>{{ translate('Only show when the previous tabs section is on its default tab') }}</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>{{ translate('Background Theme') }}</label>
                    <select class="form-control" name="sections[{{ $index }}][settings][background_style]">
                        @foreach (['sand' => 'Sand', 'stone' => 'Stone', 'light' => 'Light'] as $value => $label)
                            <option value="{{ $value }}" {{ ($settings['background_style'] ?? 'sand') === $value ? 'selected' : '' }}>{{ translate($label) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>{{ translate('Vision Title') }}</label>
                            <input type="text" class="form-control" name="sections[{{ $index }}][settings][vision_title]" value="{{ $settings['vision_title'] ?? '' }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>{{ translate('Mission Title') }}</label>
                            <input type="text" class="form-control" name="sections[{{ $index }}][settings][mission_title]" value="{{ $settings['mission_title'] ?? '' }}">
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>{{ translate('Vision Text') }}</label>
                            <textarea class="form-control" rows="4" name="sections[{{ $index }}][settings][vision_text]">{{ $settings['vision_text'] ?? '' }}</textarea>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>{{ translate('Mission Text') }}</label>
                            <textarea class="form-control" rows="4" name="sections[{{ $index }}][settings][mission_text]">{{ $settings['mission_text'] ?? '' }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="type-panel" data-type-panel="editorial_quote">
                <div class="alert alert-soft-info mb-3">
                    {{ translate('Uses the fields below to render a centered editorial quote block with generous spacing.') }}
                </div>
                <div class="form-group">
                    <label>{{ translate('Tab Visibility') }}</label>
                    <select class="form-control" name="sections[{{ $index }}][settings][tab_visibility]">
                        <option value="always" {{ ($settings['tab_visibility'] ?? 'always') === 'always' ? 'selected' : '' }}>{{ translate('Always show') }}</option>
                        <option value="previous_tab_default_only" {{ ($settings['tab_visibility'] ?? 'always') === 'previous_tab_default_only' ? 'selected' : '' }}>{{ translate('Only show when the previous tabs section is on its default tab') }}</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>{{ translate('Quote Text') }}</label>
                    <textarea class="form-control" rows="4" name="sections[{{ $index }}][settings][quote_text]">{{ $settings['quote_text'] ?? '' }}</textarea>
                </div>
                <div class="form-group mb-0">
                    <label>{{ translate('Author') }}</label>
                    <input type="text" class="form-control" name="sections[{{ $index }}][settings][author]" value="{{ $settings['author'] ?? '' }}">
                </div>
            </div>

            <div class="type-panel" data-type-panel="hero">
                <div class="form-row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>{{ translate('Heading') }}</label>
                            <input type="text" class="form-control" name="sections[{{ $index }}][settings][heading]" value="{{ $settings['heading'] ?? '' }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>{{ translate('Alignment') }}</label>
                            <select class="form-control" name="sections[{{ $index }}][settings][alignment]">
                                @foreach (['left' => 'Left', 'center' => 'Center', 'right' => 'Right'] as $value => $label)
                                    <option value="{{ $value }}" {{ ($settings['alignment'] ?? 'left') === $value ? 'selected' : '' }}>{{ translate($label) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-group mb-0">
                    <label>{{ translate('Subheading') }}</label>
                    <textarea class="form-control" rows="3" name="sections[{{ $index }}][settings][subheading]">{{ $settings['subheading'] ?? '' }}</textarea>
                </div>
            </div>

            <div class="type-panel" data-type-panel="rich_text">
                <div class="alert alert-soft-info mb-0">{{ translate('Rich text sections use the title and body fields above.') }}</div>
            </div>

            <div class="type-panel" data-type-panel="image_text_split">
                <div class="alert alert-soft-info mb-3">
                    {{ translate('Uses the primary image, title, subtitle, and body fields above for the split layout.') }}
                </div>
                <div class="form-group mb-0">
                    <label>{{ translate('Image Position') }}</label>
                    <select class="form-control" name="sections[{{ $index }}][settings][image_position]">
                        @foreach (['left' => 'Image Left', 'right' => 'Image Right'] as $value => $label)
                            <option value="{{ $value }}" {{ ($settings['image_position'] ?? 'left') === $value ? 'selected' : '' }}>{{ translate($label) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="type-panel" data-type-panel="multi_column_features">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="mb-0">{{ translate('Feature Items') }}</label>
                    <button type="button" class="btn btn-soft-primary btn-sm add-feature-item">{{ translate('Add Item') }}</button>
                </div>
                <div class="feature-items">
                    @foreach ($featureItems as $featureIndex => $item)
                        <div class="border rounded p-3 mb-2 feature-item" data-feature-index="{{ $featureIndex }}">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <strong>{{ translate('Feature Item') }}</strong>
                                <button type="button" class="btn btn-soft-danger btn-sm remove-feature-item">{{ translate('Remove') }}</button>
                            </div>
                            <div class="form-group">
                                <label>{{ translate('Item Title') }}</label>
                                <input type="text" class="form-control" name="sections[{{ $index }}][settings][items][{{ $featureIndex }}][title]" value="{{ $item['title'] ?? '' }}">
                            </div>
                            <div class="form-group">
                                <label>{{ translate('Item Description') }}</label>
                                <textarea class="form-control" rows="3" name="sections[{{ $index }}][settings][items][{{ $featureIndex }}][description]">{{ $item['description'] ?? '' }}</textarea>
                            </div>
                            <div class="form-group mb-0">
                                <label>{{ translate('Icon / Image') }}</label>
                                <div class="input-group" data-toggle="aizuploader" data-type="image" data-multiple="false">
                                    <div class="input-group-prepend">
                                        <div class="input-group-text bg-soft-secondary font-weight-medium">{{ translate('Browse') }}</div>
                                    </div>
                                    <div class="form-control file-amount">{{ translate('Choose File') }}</div>
                                    <input type="hidden" name="sections[{{ $index }}][settings][items][{{ $featureIndex }}][image]" class="selected-files" value="{{ $item['image'] ?? '' }}">
                                </div>
                                <div class="file-preview box sm"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="type-panel" data-type-panel="vision_mission">
                <div class="form-row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>{{ translate('Vision Title') }}</label>
                            <input type="text" class="form-control" name="sections[{{ $index }}][settings][vision_title]" value="{{ $settings['vision_title'] ?? '' }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>{{ translate('Mission Title') }}</label>
                            <input type="text" class="form-control" name="sections[{{ $index }}][settings][mission_title]" value="{{ $settings['mission_title'] ?? '' }}">
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>{{ translate('Vision Text') }}</label>
                            <textarea class="form-control" rows="4" name="sections[{{ $index }}][settings][vision_text]">{{ $settings['vision_text'] ?? '' }}</textarea>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>{{ translate('Mission Text') }}</label>
                            <textarea class="form-control" rows="4" name="sections[{{ $index }}][settings][mission_text]">{{ $settings['mission_text'] ?? '' }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="type-panel" data-type-panel="quote">
                <div class="form-group">
                    <label>{{ translate('Quote Text') }}</label>
                    <textarea class="form-control" rows="4" name="sections[{{ $index }}][settings][quote_text]">{{ $settings['quote_text'] ?? '' }}</textarea>
                </div>
                <div class="form-group mb-0">
                    <label>{{ translate('Author') }}</label>
                    <input type="text" class="form-control" name="sections[{{ $index }}][settings][author]" value="{{ $settings['author'] ?? '' }}">
                </div>
            </div>

            <div class="type-panel" data-type-panel="cta_banner">
                <div class="form-group mb-0">
                    <label>{{ translate('Background Style') }}</label>
                    <select class="form-control" name="sections[{{ $index }}][settings][background_style]">
                        @foreach (['sand' => 'Sand', 'dark' => 'Dark', 'light' => 'Light'] as $value => $label)
                            <option value="{{ $value }}" {{ ($settings['background_style'] ?? 'sand') === $value ? 'selected' : '' }}>{{ translate($label) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="type-panel" data-type-panel="image_gallery">
                <div class="form-group mb-0">
                    <label>{{ translate('Gallery Images') }}</label>
                    <div class="input-group" data-toggle="aizuploader" data-type="image" data-multiple="true">
                        <div class="input-group-prepend">
                            <div class="input-group-text bg-soft-secondary font-weight-medium">{{ translate('Browse') }}</div>
                        </div>
                        <div class="form-control file-amount">{{ translate('Choose Files') }}</div>
                        <input type="hidden" name="sections[{{ $index }}][settings][gallery_images]" class="selected-files" value="{{ $settings['gallery_images'] ?? '' }}">
                    </div>
                    <div class="file-preview box sm"></div>
                </div>
            </div>

            <div class="type-panel" data-type-panel="spacer">
                <div class="form-row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>{{ translate('Spacing Size') }}</label>
                            <select class="form-control" name="sections[{{ $index }}][settings][spacing_size]">
                                @foreach (['sm' => 'Small', 'md' => 'Medium', 'lg' => 'Large', 'xl' => 'Extra Large'] as $value => $label)
                                    <option value="{{ $value }}" {{ ($settings['spacing_size'] ?? 'md') === $value ? 'selected' : '' }}>{{ translate($label) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6 d-flex align-items-center">
                        <div class="form-group mb-0 mt-4">
                            <label class="mb-0 d-flex align-items-center" style="gap: .35rem;">
                                <input type="hidden" class="section-line-input" name="sections[{{ $index }}][settings][line_toggle]" value="{{ !empty($settings['line_toggle']) ? 1 : 0 }}">
                                <input type="checkbox" class="section-line-toggle" {{ !empty($settings['line_toggle']) ? 'checked' : '' }}>
                                <span>{{ translate('Show Divider Line') }}</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="type-panel" data-type-panel="journal_editorial">
                <div class="alert alert-soft-info mb-3">
                    {{ translate('This section powers the special editorial, product, and video blocks shown on the Journal landing pages (/journal and /all-blogs).') }}
                </div>
                <div class="form-row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>{{ translate('Product Source Type') }}</label>
                            <select class="form-control journal-product-source-select" name="sections[{{ $index }}][settings][product_source_type]">
                                <option value="">{{ translate('None / Random Products') }}</option>
                                <option value="category" {{ ($settings['product_source_type'] ?? '') === 'category' ? 'selected' : '' }}>{{ translate('Category') }}</option>
                                <option value="brand" {{ ($settings['product_source_type'] ?? '') === 'brand' ? 'selected' : '' }}>{{ translate('Brand') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>{{ translate('Products Count') }}</label>
                            <input
                                type="number"
                                min="1"
                                max="12"
                                class="form-control"
                                name="sections[{{ $index }}][settings][related_products_limit]"
                                value="{{ $settings['related_products_limit'] ?? 4 }}"
                            >
                        </div>
                    </div>
                </div>
                <div class="form-row journal-product-source-group journal-product-source-group--category">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>{{ translate('Selected Category') }}</label>
                            <select class="form-control aiz-selectpicker" name="sections[{{ $index }}][settings][product_category_id]" data-live-search="true">
                                <option value="">{{ translate('Choose One') }}</option>
                                @foreach (\App\Models\Category::orderBy('name')->get() as $category)
                                    <option value="{{ $category->id }}" {{ (string) ($settings['product_category_id'] ?? '') === (string) $category->id ? 'selected' : '' }}>
                                        {{ $category->getTranslation('name') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-row journal-product-source-group journal-product-source-group--brand">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>{{ translate('Selected Brand') }}</label>
                            <select class="form-control aiz-selectpicker" name="sections[{{ $index }}][settings][product_brand_id]" data-live-search="true">
                                <option value="">{{ translate('Choose One') }}</option>
                                @foreach (\App\Models\Brand::orderBy('name')->get() as $brand)
                                    <option value="{{ $brand->id }}" {{ (string) ($settings['product_brand_id'] ?? '') === (string) $brand->id ? 'selected' : '' }}>
                                        {{ $brand->getTranslation('name') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>{{ translate('YouTube URLs') }}</label>
                    <div class="journal-youtube-url-list">
                        @foreach ($journalYoutubeUrls as $youtubeIndex => $youtubeUrl)
                            <div class="input-group mb-2 journal-youtube-row">
                                <input
                                    type="url"
                                    class="form-control"
                                    name="sections[{{ $index }}][settings][youtube_urls][{{ $youtubeIndex }}]"
                                    value="{{ $youtubeUrl }}"
                                    placeholder="https://www.youtube.com/watch?v=..."
                                >
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-soft-danger remove-journal-youtube-row">{{ translate('Remove') }}</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <button type="button" class="btn btn-soft-primary btn-sm add-journal-youtube-row">{{ translate('Add YouTube URL') }}</button>
                </div>
            </div>
        </div>
    </div>
</div>
