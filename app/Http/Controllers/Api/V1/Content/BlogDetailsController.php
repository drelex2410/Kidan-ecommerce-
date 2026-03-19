<?php

namespace App\Http\Controllers\Api\V1\Content;

use App\Http\Controllers\Controller;
use App\Services\Content\JournalService;
use Illuminate\Http\JsonResponse;

class BlogDetailsController extends Controller
{
    public function show(string $slug, JournalService $journalService): JsonResponse
    {
        $payload = $journalService->detail($slug);

        return response()->json($payload, $payload['success'] ? 200 : 404);
    }
}
