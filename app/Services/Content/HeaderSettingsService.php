<?php

namespace App\Services\Content;

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
                'header_menu' => $this->combineLabelsAndLinks(
                    get_setting('header_menu_labels'),
                    get_setting('header_menu_links')
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

        if (!is_array($decodedLabels) || !is_array($decodedLinks) || count($decodedLabels) !== count($decodedLinks)) {
            return [];
        }

        return array_combine($decodedLabels, $decodedLinks) ?: [];
    }
}
