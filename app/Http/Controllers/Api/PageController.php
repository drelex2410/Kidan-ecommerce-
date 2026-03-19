<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\CustomPageResource;
use App\Models\Page;

class PageController extends Controller
{
    public function show($slug)
    {
        $page = Page::with('visibleSections')->published()->where('slug', $slug)->first();

        if ($page) {
            return response()->json([
                'success' => true,
                'data' => new CustomPageResource($page),
            ]);
        } else {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => translate('Page not found!')
            ]);
        }
    }
}
