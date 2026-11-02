<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'key' => 'success',
        'status' => 'ok',
        'version' => 'v1',
        'timestamp' => now()->toIso8601String(),
        'response_status' => [
            'error' => false,
            'validation_errors' => (object) [],
        ],
    ]);
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Guards V1
require __DIR__ . '/guards/v1/auth.php';
require __DIR__ . '/guards/v1/teams.php';
require __DIR__ . '/guards/v1/projects.php';
require __DIR__ . '/guards/v1/tasks.php';
require __DIR__ . '/guards/v1/collaboration.php';
require __DIR__ . '/guards/v1/notifications.php';
require __DIR__ . '/guards/v1/analytics.php';



