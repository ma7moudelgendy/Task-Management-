<?php

use App\Http\Controllers\Api\V1\ProjectController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('projects', ProjectController::class);

    Route::prefix('projects/{project}')->group(function () {
        Route::get('members', [ProjectController::class, 'members']);
        Route::post('members', [ProjectController::class, 'addMember']);
        Route::patch('members/{user}', [ProjectController::class, 'updateMemberRole']);
        Route::delete('members/{user}', [ProjectController::class, 'removeMember']);
    });
});

