<?php

use App\Http\Controllers\Api\V1\AttachmentController;
use App\Http\Controllers\Api\V1\CommentController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // Task Comments
    Route::get('tasks/{task}/comments', [CommentController::class, 'index']);
    Route::post('tasks/{task}/comments', [CommentController::class, 'store']);
    Route::patch('comments/{comment}', [CommentController::class, 'update']);
    Route::delete('comments/{comment}', [CommentController::class, 'destroy']);

    // Task Attachments
    Route::get('tasks/{task}/attachments', [AttachmentController::class, 'index']);
    Route::post('tasks/{task}/attachments', [AttachmentController::class, 'upload']);
    Route::get('tasks/{task}/attachments/{attachment}/download', [AttachmentController::class, 'download']);
    Route::delete('tasks/{task}/attachments/{attachment}', [AttachmentController::class, 'destroy']);
});

