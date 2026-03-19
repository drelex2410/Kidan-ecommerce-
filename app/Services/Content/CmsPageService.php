<?php

namespace App\Services\Content;

use App\Models\Page;

class CmsPageService
{
    public function findPublishedBySlug(string $slug): ?Page
    {
        return Page::query()
            ->with('visibleSections')
            ->published()
            ->where('slug', $slug)
            ->first();
    }
}
