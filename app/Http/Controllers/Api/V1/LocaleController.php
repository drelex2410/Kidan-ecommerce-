<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Bootstrap\LocaleService;
use Illuminate\Http\JsonResponse;

class LocaleController extends Controller
{
    public function __invoke(string $lang, LocaleService $localeService): JsonResponse
    {
        return response()->json($localeService->forLanguage($lang));
    }
}
