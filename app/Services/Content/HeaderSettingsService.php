<?php

namespace App\Services\Content;

use App\Models\Page;
use Illuminate\Support\Facades\Cache;

class HeaderSettingsService
{
    public function __construct(
        private readonly ContentMedia $contentMedia
    ) {
    }

    public function get(): array
    {
        return Cache::remember('v1.header_setting', 86400, function (): array {
            return [
                'top_banner' => [
                    'img' => $this->contentMedia->asset(get_setting('topbar_banner')),
                    'link' => get_setting('topbar_banner_link'),
                ],
                'mobile_app_links' => [
                    'show_play_store' => get_setting('show_topbar_play_store_link') ?? 'off',
                    'play_store' => get_setting('topbar_play_store_link'),
                    'show_app_store' => get_setting('show_topbar_app_store_link') ?? 'off',
                    'app_store' => get_setting('topbar_app_store_link'),
                ],
                'show_language_switcher' => get_setting('show_language_switcher') ?? 'off',
                'helpline' => get_setting('topbar_helpline_number'),
                'header_menu' => $this->appendAboutPageLink(
                    $this->combineLabelsAndLinks(
                    get_setting('header_menu_labels'),
                    get_setting('header_menu_links')
                    )
                ),
            ];
        });
    }

    private function combineLabelsAndLinks(?string $labels, ?string $links): array
    {
        if ($labels === null || $links === null) {
            return [];
        }

        $decodedLabels = json_decode($labels, true);
        $decodedLinks = json_decode($links, true);

        if (!is_array($decodedLabels) || !is_array($decodedLinks)) {
            return [];
        }

        $menu = [];

        foreach ($decodedLabels as $index => $label) {
            $normalizedLabel = is_string($label) ? trim($label) : '';

            if ($normalizedLabel === '') {
                continue;
            }

            $link = $decodedLinks[$index] ?? '/';
            $normalizedLink = is_string($link) ? trim($link) : '/';

            $menu[$normalizedLabel] = $normalizedLink !== '' ? $normalizedLink : '/';
        }

        return $menu;
    }

    private function appendAboutPageLink(array $menu): array
    {
        if (array_key_exists('About Us', $menu)) {
            return $menu;
        }

        $aboutPage = Page::query()
            ->published()
            ->where('slug', 'about-us')
            ->first();

        if (!$aboutPage) {
            return $menu;
        }

        $withAbout = [];

        foreach ($menu as $label => $link) {
            $withAbout[$label] = $link;

            if ($label === 'Brands') {
                $withAbout['About Us'] = $aboutPage->frontend_path;
            }
        }

        if (!array_key_exists('About Us', $withAbout)) {
            $withAbout['About Us'] = $aboutPage->frontend_path;
        }

        return $withAbout;
    }
}
