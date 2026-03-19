<?php

namespace App\Services\Account;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;

class NotificationService
{
    public function unreadForUser(User $user, int $limit = 10): Collection
    {
        return $user->unreadNotifications()->latest()->limit($limit)->get();
    }

    public function paginatedForUser(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $user->notifications()->latest()->paginate($perPage);
    }

    public function markUnreadAsRead(User $user): void
    {
        $user->unreadNotifications->markAsRead();
    }
}
