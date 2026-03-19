<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Account\NotificationResource;
use App\Services\Account\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(private readonly NotificationService $notificationService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $paginator = $this->notificationService->paginatedForUser($user);

        return response()->json([
            'success' => true,
            'notifications' => NotificationResource::collection($this->notificationService->unreadForUser($user))->resolve(),
            'data' => $this->paginationPayload($paginator),
        ]);
    }

    public function all(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->notificationService->markUnreadAsRead($user);
        $paginator = $this->notificationService->paginatedForUser($user);

        return response()->json([
            'success' => true,
            'notifications' => NotificationResource::collection($this->notificationService->unreadForUser($user))->resolve(),
            'data' => $this->paginationPayload($paginator),
        ]);
    }

    private function paginationPayload($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'data' => NotificationResource::collection($paginator->items())->resolve(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }
}
