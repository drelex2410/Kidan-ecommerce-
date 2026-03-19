<?php

namespace App\Http\Controllers\Api\V1\Content;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Content\PageResource;
use App\Services\Content\CmsPageService;
use Illuminate\Http\JsonResponse;

class PageController extends Controller
{
    public function show(string $slug, CmsPageService $cmsPageService): JsonResponse
    {
        $page = $cmsPageService->findPublishedBySlug($slug);

        if (!$page) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Page not found!',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new PageResource($page),
        ]);
    }
}
