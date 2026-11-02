<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\NotificationResource;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends ApiController
{
    public function __construct(
        protected NotificationService $notificationService,
    ) {}

    /**
     * Display a listing of user notifications.
     */
    public function index(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $user = $request->user();
            $unreadOnly = $request->boolean('unread_only');
            $perPage = (int) $request->input('per_page', 15);

            $notifications = $this->notificationService->getNotifications($user, $perPage, $unreadOnly);

            return $this->jsonResponse(
                data: NotificationResource::collection($notifications)->response()->getData(true),
                msg: 'Notifications retrieved successfully.'
            );
        });
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        return $this->handleApi(function () use ($request, $id) {
            $user = $request->user();
            $notification = $this->notificationService->markAsRead($user, $id);

            return $this->jsonResponse(
                data: NotificationResource::make($notification),
                msg: 'Notification marked as read.'
            );
        });
    }

    /**
     * Mark all notifications as read for current user.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $user = $request->user();
            $count = $this->notificationService->markAllAsRead($user);

            return $this->jsonResponse(
                data: ['marked_as_read_count' => $count],
                msg: 'All notifications marked as read.'
            );
        });
    }
}
