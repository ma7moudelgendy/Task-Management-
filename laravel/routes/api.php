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
