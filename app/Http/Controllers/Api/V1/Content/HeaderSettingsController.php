<?php

namespace App\Http\Controllers\Api\V1\Content;

use App\Http\Controllers\Controller;
use App\Services\Content\HeaderSettingsService;

class HeaderSettingsController extends Controller
{
    public function __invoke(HeaderSettingsService $headerSettingsService): array
    {
        return $headerSettingsService->get();
    }
}
