<?php

use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\TaskRecurrenceController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // Notifications
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::post('notifications/{id}/read', [NotificationController::class, 'markAsRead']);

    // Task Recurrence Rules
    Route::get('tasks/{task}/recurrence', [TaskRecurrenceController::class, 'show']);
    Route::post('tasks/{task}/recurrence', [TaskRecurrenceController::class, 'store']);
});
