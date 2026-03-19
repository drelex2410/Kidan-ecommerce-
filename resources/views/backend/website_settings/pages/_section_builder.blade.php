@php
    $isDefaultLang = ($lang ?? env('DEFAULT_LANGUAGE')) === env('DEFAULT_LANGUAGE');
    $sectionsForBuilder = $sectionsForBuilder ?? [];
@endphp

<div class="card">
    <div class="card-header">
        <h6 class="fw-600 mb-0">{{ translate('Page Details') }}</h6>
    </div>
    <div class="card-body">
        <div class="form-group row">
            <label class="col-sm-2 col-from-label">{{ translate('Title') }} <span class="text-danger">*</span></label>
            <div class="col-sm-10">
                <input type="text" class="form-control" placeholder="Title" name="title" value="{{ old('title', $page->getTranslation('title', $lang ?? env('DEFAULT_LANGUAGE')) ?? ($page->title ?? '')) }}" required>
            </div>
        </div>

        <div class="form-group row">
            <label class="col-sm-2 col-from-label">{{ translate('Link') }} <span class="text-danger">*</span></label>
            <div class="col-sm-10">
                <div class="input-group">
                    @if (($page->type ?? 'custom_page') === 'custom_page')
                        <div class="input-group-prepend"><span class="input-group-text">{{ ($page->slug ?? '') === 'journal' ? route('home') . '/' : route('home') . '/page/' }}</span></div>
                        <input type="text" class="form-control" placeholder="{{ translate('Slug') }}" name="slug" value="{{ old('slug', $page->slug ?? '') }}" required>
                    @else
                        <input class="form-control" value="{{ route('home') }}/page/{{ $page->slug ?? '' }}" disabled>
                        <input type="hidden" name="slug" value="{{ $page->slug ?? '' }}">
                    @endif
                </div>
                @if (($page->slug ?? '') === 'about-us')
                    <small class="form-text text-muted">{{ translate('This page is served publicly from') }} `/about` {{ translate('and also remains available through the CMS page endpoint.') }}</small>
                @elseif (($page->slug ?? '') === 'journal')
                    <small class="form-text text-muted">{{ translate('This page is served publicly from') }} `/journal` {{ translate('and shares its frontend layout with the Journal landing experience.') }}</small>
                @else
                    <small class="form-text text-muted">{{ translate('Use character, number, hypen only') }}</small>
                @endif
            </div>
        </div>

        <div class="form-group row">
            <label class="col-sm-2 col-from-label">{{ translate('Published') }}</label>
            <div class="col-sm-10">
                <label class="aiz-switch aiz-switch-success mb-0">
                    <input type="hidden" name="is_published" value="0">
                    <input type="checkbox" name="is_published" value="1" {{ old('is_published', isset($page) ? (int) ($page->is_published ?? 1) : 1) ? 'checked' : '' }}>
                    <span></span>
                </label>
            </div>
        </div>
    </div>
</div>

@if ($isDefaultLang)
    <div class="card mt-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h6 class="fw-600 mb-0">{{ translate('Page Builder Sections') }}</h6>
                <small class="text-muted">{{ translate('Build the page with reusable responsive blocks.') }}</small>
            </div>
            <button
                type="button"
                class="btn btn-primary btn-sm"
                id="add-page-section"
                data-default-section-type="{{ ($page->slug ?? '') === 'journal' ? 'journal_editorial' : 'about_hero_split' }}"
            >
                {{ translate('Add Section') }}
            </button>
        </div>
        <div class="card-body">
            <div id="page-sections-container">
                @forelse ($sectionsForBuilder as $index => $sectionData)
                    @include('backend.website_settings.pages._section_card', ['index' => $index, 'sectionData' => $sectionData])
                @empty
                @endforelse
            </div>

            <div id="no-sections-placeholder" class="alert alert-soft-secondary mb-0 {{ count($sectionsForBuilder) ? 'd-none' : '' }}">
                {{ translate('No modular sections added yet. Add your first section to start building this page.') }}
            </div>
        </div>
    </div>
@else
    <div class="alert alert-soft-info mt-3">
        {{ translate('Section content currently uses the default language version. This tab keeps translated page title and the legacy HTML fallback available.') }}
    </div>
@endif

@if ($isDefaultLang)
    <style>
        .page-section-accordion-item {
            border: 1px solid rgba(23, 17, 15, 0.08);
            border-radius: .75rem;
            overflow: hidden;
        }

        .page-section-card-header {
            background: #fff;
            border-bottom: 0;
        }

        .section-accordion-toggle {
            min-width: 0;
            text-decoration: none !important;
        }

        .section-accordion-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            border-radius: 999px;
            background: #f3f5f7;
            color: #52606d;
            font-size: 1rem;
            transition: transform .2s ease, background-color .2s ease, color .2s ease;
            flex-shrink: 0;
        }

        .page-section-card.is-open .section-accordion-icon {
            transform: rotate(180deg);
            background: #e8f1ff;
            color: #0f5fd7;
        }

        .section-card-heading-group {
            min-width: 0;
        }

        .section-card-summary {
            max-width: 42rem;
            line-height: 1.45;
            white-space: normal;
            word-break: break-word;
        }

        .page-section-collapse {
            border-top: 1px solid rgba(23, 17, 15, 0.06);
        }
    </style>
@endif

<div class="card mt-3">
    <div class="card-header">
        <h6 class="fw-600 mb-0">{{ translate('Legacy HTML Fallback') }}</h6>
        <small class="text-muted">{{ translate('This content is only used when a page has no visible modular sections.') }}</small>
    </div>
    <div class="card-body">
        <div class="form-group mb-0">
            <label>{{ translate('Fallback Content') }} <span class="text-danger">@if (! $isDefaultLang)<i class="las la-language" title="{{ translate('Translatable') }}"></i>@endif</span></label>
            <textarea class="aiz-text-editor form-control" placeholder="Content.." data-min-height="260" name="content">{{ old('content', $page->getTranslation('content', $lang ?? env('DEFAULT_LANGUAGE')) ?? ($page->content ?? '')) }}</textarea>
        </div>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header">
        <h6 class="fw-600 mb-0">{{ translate('SEO Fields') }}</h6>
    </div>
    <div class="card-body">
        <div class="form-group row">
            <label class="col-sm-2 col-from-label">{{ translate('Meta Title') }}</label>
            <div class="col-sm-10">
                <input type="text" class="form-control" placeholder="Title" name="meta_title" value="{{ old('meta_title', $page->meta_title ?? '') }}">
            </div>
        </div>

        <div class="form-group row">
            <label class="col-sm-2 col-from-label">{{ translate('Meta Description') }}</label>
            <div class="col-sm-10">
                <textarea class="resize-off form-control" placeholder="Description" name="meta_description">{{ old('meta_description', $page->meta_description ?? '') }}</textarea>
            </div>
        </div>

        <div class="form-group row">
            <label class="col-sm-2 col-from-label">{{ translate('Keywords') }}</label>
            <div class="col-sm-10">
                <textarea class="resize-off form-control" placeholder="Keyword, Keyword" name="keywords">{{ old('keywords', $page->keywords ?? '') }}</textarea>
                <small class="text-muted">{{ translate('Separate with coma') }}</small>
            </div>
        </div>

        <div class="form-group row">
            <label class="col-sm-2 col-from-label">{{ translate('Meta Image') }}</label>
            <div class="col-sm-10">
                <div class="input-group" data-toggle="aizuploader" data-type="image">
                    <div class="input-group-prepend">
                        <div class="input-group-text bg-soft-secondary font-weight-medium">{{ translate('Browse') }}</div>
                    </div>
                    <div class="form-control file-amount">{{ translate('Choose File') }}</div>
                    <input type="hidden" name="meta_image" class="selected-files" value="{{ old('meta_image', $page->meta_image ?? '') }}">
                </div>
                <div class="file-preview"></div>
            </div>
        </div>

        <div class="text-right">
            <button type="submit" class="btn btn-primary">{{ $submitLabel ?? translate('Save Page') }}</button>
        </div>
    </div>
</div>

@if ($isDefaultLang)
    @push('page_builder_scripts')
    <script type="text/template" id="page-section-template">
        @include('backend.website_settings.pages._section_card', ['index' => '__INDEX__', 'sectionData' => []])
    </script>

    <script>
        (function() {
            let sectionCounter = Date.now();

            function nextIndex() {
                sectionCounter += 1;
                return String(sectionCounter);
            }

            function stripMarkup(value) {
                return String(value || '')
                    .replace(/<[^>]*>/g, ' ')
                    .replace(/\s+/g, ' ')
                    .trim();
            }

            function truncateText(value, limit = 110) {
                if (!value || value.length <= limit) {
                    return value;
                }

                return `${value.slice(0, limit).trim()}...`;
            }

            function refreshSectionSummary(card) {
                const summaryNode = card.querySelector('.section-card-summary');
                if (!summaryNode) {
                    return;
                }

                const type = card.querySelector('.section-type-select')?.value;
                const headingValue = card.querySelector('[data-common-field="title"] input')?.value?.trim();
                const subtitleValue = card.querySelector('[data-common-field="subtitle"] input')?.value?.trim();
                const contentValue = stripMarkup(card.querySelector('[data-common-field="content"] textarea')?.value);

                let summary = headingValue || subtitleValue || contentValue;

                if (type === 'tabs_content') {
                    const tabCount = card.querySelectorAll('.tab-item').length;
                    summary = tabCount
                        ? `${tabCount} {{ translate('tab') }}${tabCount > 1 ? 's' : ''} {{ translate('configured') }}`
                        : '{{ translate('No tabs added yet') }}';
                }

                if ((type === 'editorial_quote' || type === 'quote') && !summary) {
                    summary = stripMarkup(card.querySelector('[name*="[settings][quote_text]"]')?.value);
                }

                if ((type === 'vision_mission_split' || type === 'vision_mission') && !summary) {
                    const visionTitle = card.querySelector('[name*="[settings][vision_title]"]')?.value?.trim();
                    const missionTitle = card.querySelector('[name*="[settings][mission_title]"]')?.value?.trim();
                    summary = [visionTitle, missionTitle].filter(Boolean).join(' / ');
                }

                if (type === 'multi_column_features' && !summary) {
                    const featureCount = card.querySelectorAll('.feature-item').length;
                    summary = featureCount
                        ? `${featureCount} {{ translate('feature item') }}${featureCount > 1 ? 's' : ''}`
                        : '{{ translate('No feature items yet') }}';
                }

                if (!summary) {
                    summary = '{{ translate('No content summary yet') }}';
                }

                summaryNode.textContent = truncateText(summary);
            }

            function setSectionOpen(card, shouldOpen) {
                const collapse = card.querySelector('.page-section-collapse');
                const toggle = card.querySelector('[data-section-toggle]');

                if (!collapse || !toggle) {
                    return;
                }

                collapse.classList.toggle('show', shouldOpen);
                card.classList.toggle('is-open', shouldOpen);
                toggle.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
            }

            function openSection(card) {
                document.querySelectorAll('.page-section-card').forEach((currentCard) => {
                    setSectionOpen(currentCard, currentCard === card);
                });
            }

            function applySectionType(card) {
                const type = card.querySelector('.section-type-select').value;
                const title = card.querySelector('.section-type-select').selectedOptions[0].textContent;
                const fieldMap = {
                    about_hero_split: ["title", "subtitle", "image", "image_2"],
                    editorial_intro: ["content"],
                    tabs_content: ["title"],
                    image_content_panel: ["title", "subtitle", "content", "image"],
                    vision_mission_split: ["image"],
                    editorial_quote: [],
                    hero: ["title", "subtitle", "button", "image"],
                    rich_text: ["title", "content"],
                    image_text_split: ["title", "subtitle", "content", "button", "image"],
                    multi_column_features: ["title", "subtitle"],
                    vision_mission: ["image"],
                    quote: [],
                    cta_banner: ["title", "subtitle", "button", "image"],
                    image_gallery: ["title"],
                    spacer: [],
                    journal_editorial: ["title", "content", "image"],
                };
                card.querySelector('.section-card-title').textContent = title;

                card.querySelectorAll('[data-type-panel]').forEach((panel) => {
                    panel.style.display = panel.getAttribute('data-type-panel') === type ? '' : 'none';
                });

                const visibleFields = fieldMap[type] || ["title", "subtitle", "content", "button", "image", "image_2"];
                card.querySelectorAll('[data-common-field]').forEach((field) => {
                    field.style.display = visibleFields.includes(field.getAttribute('data-common-field')) ? '' : 'none';
                });

                refreshSectionSummary(card);
            }

            function updateSectionOrdering() {
                const cards = Array.from(document.querySelectorAll('.page-section-card'));
                const placeholder = document.getElementById('no-sections-placeholder');

                cards.forEach((card, index) => {
                    card.querySelector('.section-sort-order').value = index + 1;
                    card.querySelector('.section-order-label').textContent = index + 1;
                });

                if (placeholder) {
                    placeholder.classList.toggle('d-none', cards.length > 0);
                }

                const currentlyOpen = cards.find((card) => card.classList.contains('is-open'));
                if (!currentlyOpen && cards[0]) {
                    setSectionOpen(cards[0], true);
                }
            }

            function buildFeatureItemHtml(sectionIndex, itemIndex) {
                return `
                    <div class="border rounded p-3 mb-2 feature-item" data-feature-index="${itemIndex}">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong>{{ translate('Feature Item') }}</strong>
                            <button type="button" class="btn btn-soft-danger btn-sm remove-feature-item">{{ translate('Remove') }}</button>
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Item Title') }}</label>
                            <input type="text" class="form-control" name="sections[${sectionIndex}][settings][items][${itemIndex}][title]">
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Item Description') }}</label>
                            <textarea class="form-control" rows="3" name="sections[${sectionIndex}][settings][items][${itemIndex}][description]"></textarea>
                        </div>
                        <div class="form-group mb-0">
                            <label>{{ translate('Icon / Image') }}</label>
                            <div class="input-group" data-toggle="aizuploader" data-type="image" data-multiple="false">
                                <div class="input-group-prepend">
                                    <div class="input-group-text bg-soft-secondary font-weight-medium">{{ translate('Browse') }}</div>
                                </div>
                                <div class="form-control file-amount">{{ translate('Choose File') }}</div>
                                <input type="hidden" name="sections[${sectionIndex}][settings][items][${itemIndex}][image]" class="selected-files">
                            </div>
                            <div class="file-preview box sm"></div>
                        </div>
                    </div>`;
            }

            function buildTabItemHtml(sectionIndex, itemIndex, isDefault) {
                return `
                    <div class="border rounded p-3 mb-2 tab-item" data-tab-index="${itemIndex}">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong>{{ translate('Tab Item') }}</strong>
                            <div class="d-flex align-items-center" style="gap: .75rem;">
                                <label class="mb-0 d-flex align-items-center" style="gap: .35rem;">
                                    <input type="radio" name="sections[${sectionIndex}][settings][default_tab]" value="${itemIndex}" ${isDefault ? 'checked' : ''}>
                                    <span>{{ translate('Default') }}</span>
                                </label>
                                <button type="button" class="btn btn-soft-danger btn-sm remove-tab-item">{{ translate('Remove') }}</button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Tab Label') }}</label>
                            <input type="text" class="form-control" name="sections[${sectionIndex}][settings][tabs][${itemIndex}][tab_label]">
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Tab Layout') }}</label>
                            <select class="form-control" name="sections[${sectionIndex}][settings][tabs][${itemIndex}][layout]">
                                <option value="basic">{{ translate('Basic Content') }}</option>
                                <option value="career_showcase">{{ translate('Career Showcase') }}</option>
                                <option value="tribe_rewards">{{ translate('Tribe Rewards') }}</option>
                                <option value="partnership_cta">{{ translate('Partnership CTA') }}</option>
                                <option value="youth_program">{{ translate('Youth Program') }}</option>
                                <option value="press_events">{{ translate('Press & Events') }}</option>
                            </select>
                            <small class="form-text text-muted">{{ translate('Keep About Us on Basic Content. Use the richer layouts for the visual tabs.') }}</small>
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Intro Title') }}</label>
                            <input type="text" class="form-control" name="sections[${sectionIndex}][settings][tabs][${itemIndex}][intro_title]">
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Intro Body') }}</label>
                            <textarea class="form-control" rows="4" name="sections[${sectionIndex}][settings][tabs][${itemIndex}][intro_body]"></textarea>
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Content Title') }}</label>
                            <input type="text" class="form-control" name="sections[${sectionIndex}][settings][tabs][${itemIndex}][content_title]">
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Content Body') }}</label>
                            <textarea class="form-control" rows="4" name="sections[${sectionIndex}][settings][tabs][${itemIndex}][content_body]"></textarea>
                        </div>
                        <div class="form-row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>{{ translate('Extra Section Title') }}</label>
                                    <input type="text" class="form-control" name="sections[${sectionIndex}][settings][tabs][${itemIndex}][extra_title]">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>{{ translate('Display Title / Graphic Text') }}</label>
                                    <textarea class="form-control" rows="2" name="sections[${sectionIndex}][settings][tabs][${itemIndex}][display_title]"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Extra Section Body') }}</label>
                            <textarea class="form-control" rows="4" name="sections[${sectionIndex}][settings][tabs][${itemIndex}][extra_body]"></textarea>
                        </div>
                        <div class="form-row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>{{ translate('Reward Title / Accent Text') }}</label>
                                    <input type="text" class="form-control" name="sections[${sectionIndex}][settings][tabs][${itemIndex}][reward_title]">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>{{ translate('Footer Text') }}</label>
                                    <input type="text" class="form-control" name="sections[${sectionIndex}][settings][tabs][${itemIndex}][footer_text]">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Closing Body') }}</label>
                            <textarea class="form-control" rows="4" name="sections[${sectionIndex}][settings][tabs][${itemIndex}][closing_body]"></textarea>
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
                                        <input type="hidden" name="sections[${sectionIndex}][settings][tabs][${itemIndex}][image]" class="selected-files">
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
                                        <input type="hidden" name="sections[${sectionIndex}][settings][tabs][${itemIndex}][image_2]" class="selected-files">
                                    </div>
                                    <div class="file-preview box sm"></div>
                                </div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>{{ translate('Primary Button Text') }}</label>
                                    <input type="text" class="form-control" name="sections[${sectionIndex}][settings][tabs][${itemIndex}][button_text]">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>{{ translate('Primary Button Link') }}</label>
                                    <input type="text" class="form-control" name="sections[${sectionIndex}][settings][tabs][${itemIndex}][button_link]">
                                </div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>{{ translate('Secondary Button Text') }}</label>
                                    <input type="text" class="form-control" name="sections[${sectionIndex}][settings][tabs][${itemIndex}][extra_button_text]">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-0">
                                    <label>{{ translate('Secondary Button Link') }}</label>
                                    <input type="text" class="form-control" name="sections[${sectionIndex}][settings][tabs][${itemIndex}][extra_button_link]">
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
                            <div class="tab-collection-items"></div>
                        </div>

                        <div class="border-top pt-3 mt-3 tab-repeat-group" data-collection="items_secondary">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <strong>{{ translate('Secondary Items') }}</strong>
                                    <small class="d-block text-muted">{{ translate('Used for the extra list or card collection in layouts like Career and Press & Events.') }}</small>
                                </div>
                                <button type="button" class="btn btn-soft-primary btn-sm add-tab-collection-item" data-collection="items_secondary">{{ translate('Add Secondary Item') }}</button>
                            </div>
                            <div class="tab-collection-items"></div>
                        </div>

                        <div class="border-top pt-3 mt-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <strong>{{ translate('Statement Lines') }}</strong>
                                    <small class="d-block text-muted">{{ translate('Used for stacked bold lines in layouts like Kidan Youth.') }}</small>
                                </div>
                                <button type="button" class="btn btn-soft-primary btn-sm add-tab-statement-line">{{ translate('Add Line') }}</button>
                            </div>
                            <div class="tab-statement-lines"></div>
                        </div>
                    </div>`;
            }

            function buildTabCollectionItemHtml(sectionIndex, tabIndex, collectionName, itemIndex) {
                const itemLabel = collectionName === 'items_secondary'
                    ? '{{ translate('Secondary Item') }}'
                    : '{{ translate('Primary Item') }}';

                return `
                    <div class="border rounded p-3 mb-2 tab-collection-item" data-collection-item-index="${itemIndex}">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong>${itemLabel}</strong>
                            <button type="button" class="btn btn-soft-danger btn-sm remove-tab-collection-item">{{ translate('Remove') }}</button>
                        </div>
                        <div class="form-row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>{{ translate('Item Title') }}</label>
                                    <input type="text" class="form-control" name="sections[${sectionIndex}][settings][tabs][${tabIndex}][${collectionName}][${itemIndex}][title]">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>{{ translate('Meta Label') }}</label>
                                    <input type="text" class="form-control" name="sections[${sectionIndex}][settings][tabs][${tabIndex}][${collectionName}][${itemIndex}][meta]">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Item Description') }}</label>
                            <textarea class="form-control" rows="3" name="sections[${sectionIndex}][settings][tabs][${tabIndex}][${collectionName}][${itemIndex}][description]"></textarea>
                        </div>
                        <div class="form-row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>{{ translate('Sub Meta') }}</label>
                                    <input type="text" class="form-control" name="sections[${sectionIndex}][settings][tabs][${tabIndex}][${collectionName}][${itemIndex}][submeta]">
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
                                        <input type="hidden" name="sections[${sectionIndex}][settings][tabs][${tabIndex}][${collectionName}][${itemIndex}][image]" class="selected-files">
                                    </div>
                                    <div class="file-preview box sm"></div>
                                </div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>{{ translate('Item Button Text') }}</label>
                                    <input type="text" class="form-control" name="sections[${sectionIndex}][settings][tabs][${tabIndex}][${collectionName}][${itemIndex}][button_text]">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-0">
                                    <label>{{ translate('Item Button Link') }}</label>
                                    <input type="text" class="form-control" name="sections[${sectionIndex}][settings][tabs][${tabIndex}][${collectionName}][${itemIndex}][button_link]">
                                </div>
                            </div>
                        </div>
                    </div>`;
            }

            function buildTabStatementLineHtml(sectionIndex, tabIndex, itemIndex) {
                return `
                    <div class="border rounded p-3 mb-2 tab-statement-line" data-line-index="${itemIndex}">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong>{{ translate('Statement Line') }}</strong>
                            <button type="button" class="btn btn-soft-danger btn-sm remove-tab-statement-line">{{ translate('Remove') }}</button>
                        </div>
                        <div class="form-group mb-0">
                            <label>{{ translate('Line Text') }}</label>
                            <input type="text" class="form-control" name="sections[${sectionIndex}][settings][tabs][${tabIndex}][statement_lines][${itemIndex}][text]">
                        </div>
                    </div>`;
            }

            function buildBulletItemHtml(sectionIndex, itemIndex) {
                return `
                    <div class="border rounded p-3 mb-2 bullet-item" data-bullet-index="${itemIndex}">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong>{{ translate('Bullet') }}</strong>
                            <button type="button" class="btn btn-soft-danger btn-sm remove-bullet-item">{{ translate('Remove') }}</button>
                        </div>
                        <div class="form-group mb-0">
                            <label>{{ translate('Bullet Text') }}</label>
                            <input type="text" class="form-control" name="sections[${sectionIndex}][settings][bullets][${itemIndex}][text]">
                        </div>
                    </div>`;
            }

            function buildJournalYoutubeRowHtml(sectionIndex, itemIndex) {
                return `
                    <div class="input-group mb-2 journal-youtube-row">
                        <input
                            type="url"
                            class="form-control"
                            name="sections[${sectionIndex}][settings][youtube_urls][${itemIndex}]"
                            placeholder="https://www.youtube.com/watch?v=..."
                        >
                        <div class="input-group-append">
                            <button type="button" class="btn btn-soft-danger remove-journal-youtube-row">{{ translate('Remove') }}</button>
                        </div>
                    </div>`;
            }

            function toggleJournalProductSource(card) {
                const type = card.querySelector('.journal-product-source-select')?.value || '';
                const categoryGroup = card.querySelector('.journal-product-source-group--category');
                const brandGroup = card.querySelector('.journal-product-source-group--brand');

                if (categoryGroup) {
                    categoryGroup.style.display = type === 'category' ? '' : 'none';
                }

                if (brandGroup) {
                    brandGroup.style.display = type === 'brand' ? '' : 'none';
                }
            }

            function ensureDefaultTab(card) {
                const radios = card.querySelectorAll('.tab-item input[type="radio"]');
                if (!radios.length) {
                    return;
                }

                const checked = Array.from(radios).some((radio) => radio.checked);
                if (!checked) {
                    radios[0].checked = true;
                }
            }

            function bindFeatureRepeater(card) {
                const addButton = card.querySelector('.add-feature-item');
                if (!addButton) {
                    return;
                }

                addButton.addEventListener('click', function() {
                    const wrapper = card.querySelector('.feature-items');
                    const sectionIndex = card.getAttribute('data-section-index');
                    const itemIndex = nextIndex();
                    wrapper.insertAdjacentHTML('beforeend', buildFeatureItemHtml(sectionIndex, itemIndex));
                    refreshSectionSummary(card);
                });
            }

            function bindTabRepeater(card) {
                const addButton = card.querySelector('.add-tab-item');
                if (!addButton) {
                    return;
                }

                addButton.addEventListener('click', function() {
                    const wrapper = card.querySelector('.tab-items');
                    const sectionIndex = card.getAttribute('data-section-index');
                    const itemIndex = nextIndex();
                    const isDefault = !wrapper.querySelector('input[type="radio"]:checked');
                    wrapper.insertAdjacentHTML('beforeend', buildTabItemHtml(sectionIndex, itemIndex, isDefault));
                    ensureDefaultTab(card);
                    refreshSectionSummary(card);
                });
            }

            function bindBulletRepeater(card) {
                const addButton = card.querySelector('.add-bullet-item');
                if (!addButton) {
                    return;
                }

                addButton.addEventListener('click', function() {
                    const wrapper = card.querySelector('.bullet-items');
                    const sectionIndex = card.getAttribute('data-section-index');
                    const itemIndex = nextIndex();
                    wrapper.insertAdjacentHTML('beforeend', buildBulletItemHtml(sectionIndex, itemIndex));
                    refreshSectionSummary(card);
                });
            }

            function bindCard(card) {
                const accordionToggle = card.querySelector('[data-section-toggle]');
                if (accordionToggle) {
                    accordionToggle.addEventListener('click', function() {
                        const isOpen = card.classList.contains('is-open');
                        if (isOpen) {
                            setSectionOpen(card, false);
                            return;
                        }

                        openSection(card);
                    });
                }

                card.querySelector('.section-type-select').addEventListener('change', function() {
                    applySectionType(card);
                });

                card.querySelector('.section-visible-toggle').addEventListener('change', function() {
                    card.querySelector('.section-visible-input').value = this.checked ? 1 : 0;
                });

                const lineToggle = card.querySelector('.section-line-toggle');
                if (lineToggle) {
                    lineToggle.addEventListener('change', function() {
                        card.querySelector('.section-line-input').value = this.checked ? 1 : 0;
                    });
                }

                const journalSourceSelect = card.querySelector('.journal-product-source-select');
                if (journalSourceSelect) {
                    journalSourceSelect.addEventListener('change', function() {
                        toggleJournalProductSource(card);
                    });
                }

                card.querySelector('.remove-section').addEventListener('click', function() {
                    card.remove();
                    updateSectionOrdering();
                });

                card.querySelector('.move-section-up').addEventListener('click', function() {
                    const prev = card.previousElementSibling;
                    if (prev) {
                        prev.before(card);
                        updateSectionOrdering();
                    }
                });

                card.querySelector('.move-section-down').addEventListener('click', function() {
                    const next = card.nextElementSibling;
                    if (next) {
                        next.after(card);
                        updateSectionOrdering();
                    }
                });

                card.addEventListener('click', function(event) {
                    const removeFeatureButton = event.target.closest('.remove-feature-item');
                    if (removeFeatureButton) {
                        removeFeatureButton.closest('.feature-item').remove();
                        refreshSectionSummary(card);
                        return;
                    }

                    const removeTabButton = event.target.closest('.remove-tab-item');
                    if (removeTabButton) {
                        removeTabButton.closest('.tab-item').remove();
                        ensureDefaultTab(card);
                        refreshSectionSummary(card);
                        return;
                    }

                    const removeBulletButton = event.target.closest('.remove-bullet-item');
                    if (removeBulletButton) {
                        removeBulletButton.closest('.bullet-item').remove();
                        return;
                    }

                    const addJournalYoutubeButton = event.target.closest('.add-journal-youtube-row');
                    if (addJournalYoutubeButton) {
                        const wrapper = card.querySelector('.journal-youtube-url-list');
                        const sectionIndex = card.getAttribute('data-section-index');
                        const itemIndex = nextIndex();
                        wrapper.insertAdjacentHTML('beforeend', buildJournalYoutubeRowHtml(sectionIndex, itemIndex));
                        return;
                    }

                    const removeJournalYoutubeButton = event.target.closest('.remove-journal-youtube-row');
                    if (removeJournalYoutubeButton) {
                        const rows = card.querySelectorAll('.journal-youtube-row');
                        if (rows.length <= 1) {
                            removeJournalYoutubeButton.closest('.journal-youtube-row').querySelector('input').value = '';
                        } else {
                            removeJournalYoutubeButton.closest('.journal-youtube-row').remove();
                        }
                        return;
                    }

                    const addCollectionButton = event.target.closest('.add-tab-collection-item');
                    if (addCollectionButton) {
                        const tabItem = addCollectionButton.closest('.tab-item');
                        const wrapper = addCollectionButton.closest('.tab-repeat-group').querySelector('.tab-collection-items');
                        const sectionIndex = card.getAttribute('data-section-index');
                        const tabIndex = tabItem.getAttribute('data-tab-index');
                        const collectionName = addCollectionButton.getAttribute('data-collection');
                        const itemIndex = nextIndex();

                        wrapper.insertAdjacentHTML('beforeend', buildTabCollectionItemHtml(sectionIndex, tabIndex, collectionName, itemIndex));
                        refreshSectionSummary(card);
                        return;
                    }

                    const removeCollectionButton = event.target.closest('.remove-tab-collection-item');
                    if (removeCollectionButton) {
                        removeCollectionButton.closest('.tab-collection-item').remove();
                        return;
                    }

                    const addLineButton = event.target.closest('.add-tab-statement-line');
                    if (addLineButton) {
                        const tabItem = addLineButton.closest('.tab-item');
                        const wrapper = tabItem.querySelector('.tab-statement-lines');
                        const sectionIndex = card.getAttribute('data-section-index');
                        const tabIndex = tabItem.getAttribute('data-tab-index');
                        const itemIndex = nextIndex();

                        wrapper.insertAdjacentHTML('beforeend', buildTabStatementLineHtml(sectionIndex, tabIndex, itemIndex));
                        refreshSectionSummary(card);
                        return;
                    }

                    const removeLineButton = event.target.closest('.remove-tab-statement-line');
                    if (removeLineButton) {
                        removeLineButton.closest('.tab-statement-line').remove();
                    }
                });

                bindFeatureRepeater(card);
                bindTabRepeater(card);
                bindBulletRepeater(card);
                ensureDefaultTab(card);
                setSectionOpen(card, card.querySelector('.page-section-collapse')?.classList.contains('show'));
                card.addEventListener('input', function() {
                    refreshSectionSummary(card);
                });
                card.addEventListener('change', function() {
                    refreshSectionSummary(card);
                });
                applySectionType(card);
                toggleJournalProductSource(card);
                refreshSectionSummary(card);
            }

            function createSection(type = 'about_hero_split') {
                const container = document.getElementById('page-sections-container');
                const template = document.getElementById('page-section-template').innerHTML;
                const index = nextIndex();
                const html = template.replaceAll('__INDEX__', index);

                container.insertAdjacentHTML('beforeend', html);
                const card = container.lastElementChild;
                card.setAttribute('data-section-index', index);
                card.querySelector('.section-type-select').value = type;
                bindCard(card);
                updateSectionOrdering();
                openSection(card);
            }

            function initPageBuilder() {
                const addButton = document.getElementById('add-page-section');
                if (!addButton) {
                    return;
                }

                document.querySelectorAll('.page-section-card').forEach(bindCard);
                updateSectionOrdering();

                addButton.addEventListener('click', function() {
                    createSection(addButton.getAttribute('data-default-section-type') || 'about_hero_split');
                });
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initPageBuilder);
            } else {
                initPageBuilder();
            }
        })();
    </script>
    @endpush
@endif
