<?php

namespace App\Http\Controllers\Api\V1\Content;

use App\Http\Controllers\Controller;
use App\Services\Content\FooterSettingsService;

class FooterSettingsController extends Controller
{
    public function __invoke(FooterSettingsService $footerSettingsService): array
    {
        return $footerSettingsService->get();
    }
}
