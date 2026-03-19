<?php

namespace App\Http\Controllers\Api\V1\Content;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Content\BlogSearchRequest;
use App\Services\Content\JournalService;

class BlogSearchController extends Controller
{
    public function __invoke(BlogSearchRequest $request, JournalService $journalService): array
    {
        $validated = $request->validated();
        $searchKeyword = $validated['searchKeyword'] ?? null;
        $categorySlug = $validated['category_slug'] ?? null;
        $perPage = max(1, min((int) ($validated['per_page'] ?? ($searchKeyword || $categorySlug ? 12 : 18)), 24));

        return $journalService->feed(
            $categorySlug,
            $searchKeyword,
            (int) ($validated['page'] ?? 1),
            $perPage
        );
    }
}
