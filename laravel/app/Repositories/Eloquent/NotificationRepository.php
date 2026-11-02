<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Notifications\DatabaseNotification;

class NotificationRepository implements NotificationRepositoryInterface
{
    /**
     * Get paginated notifications for a user.
     */
    public function paginateForUser(User $user, int $perPage = 15, bool $unreadOnly = false): LengthAwarePaginator
    {
        $query = $user->notifications();

        if ($unreadOnly) {
            $query->whereNull('read_at');
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Find a notification belonging to a user by ID.
     */
    public function findForUser(User $user, string $id): ?DatabaseNotification
    {
        return $user->notifications()->where('id', $id)->first();
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(DatabaseNotification $notification): bool
    {
        $notification->markAsRead();

        return true;
    }

    /**
     * Mark all unread notifications for a user as read.
     */
    public function markAllAsRead(User $user): int
    {
        $unreadCount = $user->unreadNotifications()->count();
        $user->unreadNotifications()->update(['read_at' => now()]);

        return $unreadCount;
    }
}
