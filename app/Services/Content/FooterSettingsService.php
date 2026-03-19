<?php

namespace App\Services\Content;

use Illuminate\Support\Facades\Cache;

class FooterSettingsService
{
    public function __construct(
        private readonly ContentMedia $contentMedia
    ) {
    }

    public function get(): array
    {
        return Cache::remember('v1.footer_setting', 86400, function (): array {
            return [
                'current_version' => get_setting('current_version'),
                'footer_logo' => $this->contentMedia->asset(get_setting('footer_logo')),
                'footer_link_one' => [
                    'title' => get_setting('footer_link_one_title'),
                    'menu' => $this->combineLabelsAndLinks(
                        get_setting('footer_link_one_labels'),
                        get_setting('footer_link_one_links')
                    ),
                ],
                'footer_link_two' => [
                    'title' => get_setting('footer_link_two_title'),
                    'menu' => $this->combineLabelsAndLinks(
                        get_setting('footer_link_two_labels'),
                        get_setting('footer_link_two_links')
                    ),
                ],
                'contact_info' => [
                    'contact_address' => get_setting('contact_address'),
                    'contact_email' => get_setting('contact_email'),
                    'contact_phone' => get_setting('contact_phone'),
                ],
                'mobile_app_links' => [
                    'play_store' => get_setting('play_store_link'),
                    'app_store' => get_setting('app_store_link'),
                ],
                'footer_menu' => $this->combineLabelsAndLinks(
                    get_setting('footer_menu_labels'),
                    get_setting('footer_menu_links')
                ),
                'copyright_text' => get_setting('frontend_copyright_text'),
                'social_link' => $this->decodeSocialLinks(get_setting('footer_social_link')),
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

    private function decodeSocialLinks(?string $value): array
    {
        $decoded = $value ? json_decode($value, true) : null;

        return is_array($decoded)
            ? $decoded
            : ['facebook-f' => null, 'twitter' => null, 'instagram' => null, 'youtube' => null, 'linkedin-in' => null];
    }
}
