<?php

namespace App\Http\Controllers\Api\V1\Content;

use App\Http\Controllers\Controller;
use App\Services\Content\HomeSectionService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Http\JsonResponse;

class HomeSectionController extends Controller
{
    public function show(string $section, HomeSectionService $homeSectionService): JsonResponse
    {
        try {
            $data = $homeSectionService->get($section);
        } catch (NotFoundHttpException $exception) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => $exception->getMessage(),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
