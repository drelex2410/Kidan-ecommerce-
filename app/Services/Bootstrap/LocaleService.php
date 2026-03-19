<?php

namespace App\Services\Bootstrap;

use App\Models\Language;
use App\Models\Translation;
use Illuminate\Support\Facades\Cache;

class LocaleService
{
    public function forLanguage(string $lang): array
    {
        $language = Language::query()
            ->where('code', $lang)
            ->where('status', 1)
            ->first();

        if (!$language) {
            return [
                'success' => false,
                'message' => 'Unsupported locale.',
                'locale' => $lang,
                'translations' => new \stdClass(),
            ];
        }

        $translations = Cache::remember("frontend-translations.{$lang}", 300, static function () use ($lang) {
            return Translation::query()
                ->where('lang', $lang)
                ->pluck('lang_value', 'lang_key')
                ->toArray();
        });

        return [
            'success' => true,
            'message' => 'Locale loaded.',
            'locale' => $lang,
            'translations' => (object) $translations,
        ];
    }
}
