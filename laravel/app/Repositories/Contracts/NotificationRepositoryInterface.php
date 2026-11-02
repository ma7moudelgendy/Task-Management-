<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Notifications\DatabaseNotification;

interface NotificationRepositoryInterface
{
    /**
     * Get paginated notifications for a user.
     */
    public function paginateForUser(User $user, int $perPage = 15, bool $unreadOnly = false): LengthAwarePaginator;

    /**
     * Find a notification belonging to a user by ID.
     */
    public function findForUser(User $user, string $id): ?DatabaseNotification;

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(DatabaseNotification $notification): bool;

    /**
     * Mark all unread notifications for a user as read.
     */
    public function markAllAsRead(User $user): int;
}
