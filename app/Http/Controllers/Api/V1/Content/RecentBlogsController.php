<?php

namespace App\Http\Controllers\Api\V1\Content;

use App\Http\Controllers\Controller;
use App\Services\Content\JournalService;
use Illuminate\Http\Request;

class RecentBlogsController extends Controller
{
    public function __invoke(Request $request, JournalService $journalService): array
    {
        $limit = max(1, min((int) $request->input('limit', 6), 12));

        return $journalService->recent($limit);
    }
}
