<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Notifications\DatabaseNotification;

class NotificationService
{
    public function __construct(
        protected NotificationRepositoryInterface $notificationRepository,
    ) {}

    /**
     * Get paginated notifications for the user.
     */
    public function getNotifications(User $user, int $perPage = 15, bool $unreadOnly = false): LengthAwarePaginator
    {
        return $this->notificationRepository->paginateForUser($user, $perPage, $unreadOnly);
    }

    /**
     * Mark a single notification as read.
     *
     * @throws ModelNotFoundException
     */
    public function markAsRead(User $user, string $id): DatabaseNotification
    {
        $notification = $this->notificationRepository->findForUser($user, $id);

        if (! $notification) {
            throw new ModelNotFoundException('Notification not found.');
        }

        $this->notificationRepository->markAsRead($notification);

        return $notification->fresh();
    }

    /**
     * Mark all unread notifications as read.
     */
    public function markAllAsRead(User $user): int
    {
        return $this->notificationRepository->markAllAsRead($user);
    }
}
