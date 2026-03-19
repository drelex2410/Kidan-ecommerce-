<?php

namespace Tests\Feature\Api\V1;

use App\Services\Bootstrap\LocaleService;
use Tests\TestCase;

class LocaleEndpointTest extends TestCase
{
    public function test_locale_endpoint_returns_translation_payload(): void
    {
        $this->app->instance(LocaleService::class, new class extends LocaleService {
            public function forLanguage(string $lang): array
            {
                return [
                    'success' => true,
                    'message' => 'Locale loaded.',
                    'locale' => $lang,
                    'translations' => (object) [
                        'welcome' => 'Welcome',
                    ],
                ];
            }
        });

        $response = $this->getJson('/api/v1/locale/en');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('locale', 'en')
            ->assertJsonPath('translations.welcome', 'Welcome');
    }

    public function test_locale_endpoint_fails_gracefully_for_unsupported_locale(): void
    {
        $this->app->instance(LocaleService::class, new class extends LocaleService {
            public function forLanguage(string $lang): array
            {
                return [
                    'success' => false,
                    'message' => 'Unsupported locale.',
                    'locale' => $lang,
                    'translations' => (object) [],
                ];
            }
        });

        $response = $this->getJson('/api/v1/locale/xx');

        $response->assertOk()
            ->assertJsonPath('success', false)
            ->assertJsonPath('locale', 'xx');
    }
}
