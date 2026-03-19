<?php

namespace Tests\Feature\Api\V1;

use App\Services\Bootstrap\BootstrapService;
use Tests\TestCase;

class BootstrapEndpointTest extends TestCase
{
    public function test_bootstrap_endpoint_returns_frontend_boot_payload(): void
    {
        $this->app->instance(BootstrapService::class, new class extends BootstrapService {
            public function __construct()
            {
            }

            public function build(): array
            {
                return [
                    'appName' => 'Kidan',
                    'appMetaTitle' => 'Kidan',
                    'appMetaDescription' => 'Storefront',
                    'general_settings' => [
                        'conversation_system' => 1,
                        'wallet_system' => 1,
                        'club_point' => 1,
                    ],
                    'authSettings' => [
                        'customer_login_with' => 'email',
                        'customer_otp_with' => 'disabled',
                    ],
                ];
            }
        });

        $response = $this->getJson('/api/v1/bootstrap');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.appName', 'Kidan')
            ->assertJsonPath('data.general_settings.wallet_system', 1)
            ->assertJsonPath('data.authSettings.customer_login_with', 'email');
    }
}
