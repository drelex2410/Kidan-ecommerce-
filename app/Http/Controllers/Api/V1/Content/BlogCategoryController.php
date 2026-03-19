<?php

namespace App\Http\Controllers\Api\V1\Content;

use App\Http\Controllers\Controller;
use App\Services\Content\JournalService;

class BlogCategoryController extends Controller
{
    public function __invoke(JournalService $journalService): array
    {
        return $journalService->categoriesPayload();
    }
}
