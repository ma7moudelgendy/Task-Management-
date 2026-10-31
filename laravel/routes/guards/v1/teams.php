<?php

use App\Http\Controllers\Api\V1\TeamController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('teams', TeamController::class);

    Route::prefix('teams/{team}')->group(function () {
        Route::get('members', [TeamController::class, 'members']);
        Route::post('members', [TeamController::class, 'addMember']);
        Route::patch('members/{user}', [TeamController::class, 'updateMemberRole']);
        Route::delete('members/{user}', [TeamController::class, 'removeMember']);
    });
});

