<div class="aiz-topbar border-bottom px-15px px-lg-25px d-flex align-items-stretch justify-content-between">
    <div class=" d-flex">
        <div class="aiz-topbar-nav-toggler d-flex align-items-center justify-content-start mr-2 mr-md-3"
            data-toggle="aiz-mobile-nav">
            <button
                class="btn btn-icon btn-outline-secondary border-gray-300 p-0 d-flex align-items-center justify-content-center">
                <span class="aiz-mobile-toggler d-inline-block">
                    <span></span>
                </span>
            </button>
        </div>
        <div class="aiz-topbar-logo-wrap d-xl-none d-flex align-items-center justify-content-start">
            @php
                $logo = get_setting('header_logo');
            @endphp
            <a href="{{ route('admin.dashboard') }}" class="d-block">
                @if ($logo != null)
                    <img src="{{ uploaded_asset($logo) }}" class="brand-icon" alt="{{ get_setting('site_name') }}">
                @else
                    <img src="{{ static_asset('assets/img/logo.png') }}" class="brand-icon"
                        alt="{{ get_setting('site_name') }}">
                @endif
            </a>
        </div>
    </div>
    <div class="d-flex justify-content-between align-items-stretch flex-grow-xl-1">
        <div class="d-none d-md-flex justify-content-around align-items-center align-items-stretch">
            <div class="aiz-topbar-item align-items-center">
                <a class="btn btn-outline-secondary border-gray-300 d-flex align-items-center px-3"
                    href="{{ route('cache.clear') }}">
                    <i class="las la-hdd opacity-60"></i>
                    <span class="fw-500 fs-13 ml-2 mr-0 opacity-60">{{ translate('Clear Cache') }}</span>
                </a>
            </div>

            <div class="aiz-topbar-item align-items-center ml-3">
                <a class="btn btn-outline-secondary border-gray-300 d-flex align-items-center px-3"
                    href="{{ route('home') }}" target="_blank">
                    <i class="las la-globe opacity-60"></i>
                    <span class="fw-500 fs-13 ml-2 mr-0 opacity-60">{{ translate('Browse Website') }}</span>
                </a>
            </div>
            <div class="aiz-topbar-item align-items-center ml-3">
                <div class="admin-product-search" data-admin-product-search>
                    <form class="admin-product-search__form" autocomplete="off">
                        <input
                            type="search"
                            class="form-control admin-product-search__input"
                            placeholder="{{ translate('Search products') }}"
                            aria-label="{{ translate('Search products') }}"
                        >
                        <button type="submit" class="btn btn-outline-secondary border-gray-300 admin-product-search__button">
                            <i class="las la-search opacity-60"></i>
                        </button>
                    </form>
                    <div class="admin-product-search__results d-none"></div>
                </div>
            </div>
            <div class="aiz-topbar-item align-items-center dropdown ml-3 mr-0 ">
                <a class="btn btn-outline-secondary border-gray-300 d-flex align-items-center px-3"
                    href="javascript:void(0);" data-toggle="dropdown">
                    <i class="las la-plus ts-08 opacity-60"></i>
                    <span class="fw-500 fs-13 ml-2 mr-0 opacity-60">{{ translate('Add New') }}</span>
                </a>
                <div class="dropdown-menu p-3">
                    <ul class="list-group list-group-raw text-capitalize">
                        <li class="list-group-item p-2">
                            <a href="{{ route('product.create') }}"
                                class="text-reset fs-14 opacity-60">{{ translate('Add new product') }}</a>
                        </li>
                        <li class="list-group-item p-2">
                            <a href="{{ route('coupon.create') }}"
                                class="text-reset fs-14 opacity-60">{{ translate('Add new coupon') }}</a>
                        </li>
                        <li class="list-group-item p-2">
                            <a href="{{ route('offers.create') }}"
                                class="text-reset fs-14 opacity-60">{{ translate('Add New Offer') }}</a>
                        </li>
                        <li class="list-group-item p-2">
                            <a href="{{ route('staffs.create') }}"
                                class="text-reset fs-14 opacity-60">{{ translate('Add New Staff') }}</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="d-flex justify-content-around align-items-center align-items-stretch">
            <div class="aiz-topbar-item ml-2 d-md-none">
                <div class="admin-product-search admin-product-search--mobile" data-admin-product-search>
                    <form class="admin-product-search__form" autocomplete="off">
                        <input
                            type="search"
                            class="form-control admin-product-search__input"
                            placeholder="{{ translate('Search products') }}"
                            aria-label="{{ translate('Search products') }}"
                        >
                        <button type="submit" class="btn btn-outline-secondary border-gray-300 admin-product-search__button">
                            <i class="las la-search opacity-60"></i>
                        </button>
                    </form>
                    <div class="admin-product-search__results d-none"></div>
                </div>
            </div>


            <div class="aiz-topbar-item ml-2">
                <div class="align-items-stretch d-flex dropdown">
                    <a class="dropdown-toggle no-arrow" data-toggle="dropdown" href="javascript:void(0);" role="button"
                        aria-haspopup="false" aria-expanded="false">
                        <span class="btn btn-icon p-0 d-flex justify-content-center align-items-center">
                            <span class="d-flex align-items-center position-relative">
                                <i class="las la-bell fs-24"></i>
                                @if (Auth::user()->unreadNotifications->count() > 0)
                                    <span
                                        class="badge badge-sm badge-dot badge-circle badge-primary position-absolute absolute-top-right"></span>
                                @endif
                            </span>
                        </span>
                    </a>

                    <div class="dropdown-menu dropdown-menu-right dropdown-menu-animated dropdown-menu-xl py-0">
                        <div class="notifications">
                            <ul class="nav nav-tabs nav-justified" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link text-dark active" data-toggle="tab" data-type="order"
                                        href="#orders-notifications" role="tab"
                                        id="orders-tab">{{ translate('Order Notifications') }}</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link text-dark" data-toggle="tab" data-type="seller"
                                        href="#sellers-notifications" role="tab"
                                        id="sellers-tab">{{ translate('Seller Notifications') }}</a>
                                </li>

                            </ul>
                            <div class="tab-content c-scrollbar-light overflow-auto"
                                style="height: 75vh; max-height: 400px; overflow-y: auto;">
                                <div class="tab-pane active" id="orders-notifications" role="tabpanel">
                                  
                                    <x-notification :notifications="auth()
                                        ->user()
                                        ->unreadNotifications()
                                        ->where('type', 'App\Notifications\DB\OrderNotification')
                                        ->take(20)
                                        ->get()" />
                                </div>
                                <div class="tab-pane" id="sellers-notifications" role="tabpanel">
                                    <x-notification :notifications="auth()
                                        ->user()
                                        ->unreadNotifications()
                                        ->where('type', 'like', '%seller%')
                                        ->take(20)
                                        ->get()" />
                                </div>
                            </div>
                        </div>

                        <div class="text-center border-top">
                            <a href="{{ route('notification.list') }}" class="text-reset d-block py-2">
                                {{ translate('View All Notifications') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>


            <!-- language -->
            @php
                if (Session::has('locale')) {
                    $locale = Session::get('locale', Config::get('app.locale'));
                } else {
                    $locale = env('DEFAULT_LANGUAGE');
                }
                $language = \App\Models\Language::where('code', $locale)->first();
            @endphp
            <div class="aiz-topbar-item ml-3 mr-0">
                <div class="align-items-center d-flex dropdown" id="lang-change">
                    <a class="dropdown-toggle no-arrow" data-toggle="dropdown" href="javascript:void(0);" role="button"
                        aria-haspopup="false" aria-expanded="false">
                        <span class="btn btn btn-outline-secondary border-gray-300 px-3 px-md-4">
                            <img src="{{ static_asset('assets/img/flags/' . $language->flag . '.png') }}"
                                height="11">
                            <span
                                class="fw-500 fs-13 ml-2 mr-0 opacity-60  d-none d-md-inline-block">{{ $language->name }}</span>
                        </span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-right dropdown-menu-xs">

                        @foreach (\App\Models\Language::where('status', 1)->get() as $key => $language)
                            <li>
                                <a href="javascript:void(0)" data-flag="{{ $language->code }}"
                                    class="dropdown-item @if ($locale == $language->code) active @endif">
                                    <img src="{{ static_asset('assets/img/flags/' . $language->flag . '.png') }}"
                                        class="mr-2">
                                    <span class="language">{{ $language->name }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <div class="aiz-topbar-item ml-3 mr-0">
                <div class="align-items-center d-flex dropdown">
                    <a class="dropdown-toggle no-arrow text-dark" data-toggle="dropdown" href="javascript:void(0);"
                        role="button" aria-haspopup="false" aria-expanded="false">
                        <span class="d-flex align-items-center">
                            <span class="d-none d-md-block">
                                <span class="d-block fw-500">{{ Auth::user()->name }}</span>
                                <span class="d-block small opacity-60">{{ Auth::user()->user_type }}</span>
                            </span>
                            <span class="avatar avatar-sm ml-md-2 mr-0">
                                <img src="{{ uploaded_asset(Auth::user()->avatar) }}"
                                    onerror="this.onerror=null;this.src='{{ static_asset('assets/img/avatar-place.png') }}';">
                            </span>
                        </span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right dropdown-menu-md">
                        <a href="{{ route('profile.index') }}" class="dropdown-item">
                            <i class="las la-user-circle"></i>
                            <span>{{ translate('Profile') }}</span>
                        </a>

                        <a href="{{ route('logout') }}" class="dropdown-item">
                            <i class="las la-sign-out-alt"></i>
                            <span>{{ translate('Logout') }}</span>
                        </a>
                    </div>
                </div>
            </div><!-- .aiz-topbar-item -->
        </div>
    </div>
</div><!-- .aiz-topbar -->

<style>
    .admin-product-search {
        position: relative;
        width: min(360px, 38vw);
    }

    .admin-product-search--mobile {
        width: min(240px, 70vw);
    }

    .admin-product-search__form {
        display: flex;
        align-items: stretch;
        gap: 8px;
    }

    .admin-product-search__input {
        min-width: 0;
    }

    .admin-product-search__button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 44px;
    }

    .admin-product-search__results {
        position: absolute;
        top: calc(100% + 8px);
        left: 0;
        right: 0;
        z-index: 1080;
        background: #fff;
        border: 1px solid #e3ebf6;
        border-radius: 12px;
        box-shadow: 0 14px 32px rgba(15, 23, 42, 0.12);
        overflow: hidden;
    }

    .admin-product-search__status,
    .admin-product-search__empty,
    .admin-product-search__item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 14px;
    }

    .admin-product-search__status,
    .admin-product-search__empty {
        color: #6c757d;
        font-size: 13px;
    }

    .admin-product-search__item {
        color: inherit;
        text-decoration: none;
        transition: background-color 0.15s ease;
    }

    .admin-product-search__item:hover {
        background: #f8fafc;
        color: inherit;
        text-decoration: none;
    }

    .admin-product-search__thumb {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        object-fit: cover;
        flex-shrink: 0;
        background: #f1f5f9;
    }

    .admin-product-search__meta {
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .admin-product-search__title,
    .admin-product-search__sub,
    .admin-product-search__categories {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .admin-product-search__title {
        font-size: 13px;
        font-weight: 600;
        color: #1f2937;
    }

    .admin-product-search__sub,
    .admin-product-search__categories {
        font-size: 12px;
        color: #6b7280;
    }

    @media (max-width: 767.98px) {
        .admin-product-search__results {
            right: auto;
            width: min(320px, 80vw);
        }
    }
</style>

<script>
    (function () {
        var endpoint = @json(route('product.search'));
        var placeholderImage = @json(static_asset('assets/img/placeholder.jpg'));
        var activeRequest = null;
        var activeSequence = 0;

        function escapeHtml(value) {
            return $('<div>').text(value ?? '').html();
        }

        function renderResults(container, state, items) {
            if (state === 'hidden') {
                container.addClass('d-none').empty();
                return;
            }

            container.removeClass('d-none');

            if (state === 'loading') {
                container.html('<div class="admin-product-search__status">{{ translate('Searching products...') }}</div>');
                return;
            }

            if (!items.length) {
                container.html('<div class="admin-product-search__empty">{{ translate('No products found') }}</div>');
                return;
            }

            container.html(items.map(function (item) {
                var categories = (item.category_names || []).join(', ');
                var sku = item.sku ? 'SKU: ' + item.sku : item.slug;
                return '' +
                    '<a class="admin-product-search__item" href="' + escapeHtml(item.edit_url) + '">' +
                        '<img class="admin-product-search__thumb" src="' + escapeHtml(item.thumbnail_url || placeholderImage) + '" alt="' + escapeHtml(item.name) + '" onerror="this.onerror=null;this.src=\'' + escapeHtml(placeholderImage) + '\';">' +
                        '<span class="admin-product-search__meta">' +
                            '<span class="admin-product-search__title">' + escapeHtml(item.name) + '</span>' +
                            '<span class="admin-product-search__sub">' + escapeHtml(sku) + '</span>' +
                            '<span class="admin-product-search__categories">' + escapeHtml(categories || '{{ translate('Uncategorized') }}') + '</span>' +
                        '</span>' +
                    '</a>';
            }).join(''));
        }

        function bindSearch(root) {
            var form = root.find('.admin-product-search__form');
            var input = root.find('.admin-product-search__input');
            var results = root.find('.admin-product-search__results');
            var debounceTimer = null;

            function clearResults() {
                if (activeRequest && activeRequest.readyState !== 4) {
                    activeRequest.abort();
                }
                activeSequence += 1;
                renderResults(results, 'hidden', []);
            }

            function performSearch() {
                var query = $.trim(input.val());

                if (!query.length) {
                    clearResults();
                    return;
                }

                if (activeRequest && activeRequest.readyState !== 4) {
                    activeRequest.abort();
                }

                var requestId = ++activeSequence;
                renderResults(results, 'loading', []);

                activeRequest = $.ajax({
                    url: endpoint,
                    method: 'GET',
                    dataType: 'json',
                    data: { q: query }
                }).done(function (response) {
                    if (requestId !== activeSequence) {
                        return;
                    }

                    renderResults(results, 'results', response.data || []);
                }).fail(function (xhr, status) {
                    if (status === 'abort' || requestId !== activeSequence) {
                        return;
                    }

                    renderResults(results, 'results', []);
                });
            }

            input.on('input', function () {
                clearTimeout(debounceTimer);

                if (!$.trim(input.val()).length) {
                    clearResults();
                    return;
                }

                debounceTimer = setTimeout(performSearch, 250);
            });

            form.on('submit', function (event) {
                event.preventDefault();
                clearTimeout(debounceTimer);
                performSearch();
            });

            root.find('.admin-product-search__button').on('click', function (event) {
                event.preventDefault();
                clearTimeout(debounceTimer);
                performSearch();
            });

            input.on('focus', function () {
                if (results.children().length) {
                    results.removeClass('d-none');
                }
            });

            $(document).on('click', function (event) {
                if (!root.is(event.target) && root.has(event.target).length === 0) {
                    results.addClass('d-none');
                }
            });
        }

        $(document).ready(function () {
            $('[data-admin-product-search]').each(function () {
                bindSearch($(this));
            });
        });
    })();
</script>
