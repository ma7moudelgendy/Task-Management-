<?php

use App\Http\Controllers\Api\V1\TaskController;
use App\Http\Controllers\Api\V1\TaskWorkflowController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('tasks', [TaskController::class, 'index']);
    Route::get('tasks/{task}', [TaskController::class, 'show']);
    Route::patch('tasks/{task}', [TaskController::class, 'update']);
    Route::delete('tasks/{task}', [TaskController::class, 'destroy']);
    Route::put('tasks/{task}/assignees', [TaskController::class, 'syncAssignees']);

    // Task Workflow, Dependencies & History
    Route::post('tasks/{task}/status', [TaskWorkflowController::class, 'changeStatus']);
    Route::get('tasks/{task}/dependencies', [TaskWorkflowController::class, 'dependencies']);
    Route::post('tasks/{task}/dependencies', [TaskWorkflowController::class, 'addDependency']);
    Route::delete('tasks/{task}/dependencies/{dependency}', [TaskWorkflowController::class, 'removeDependency']);
    Route::get('tasks/{task}/history', [TaskWorkflowController::class, 'history']);

    Route::prefix('projects/{project}')->group(function () {
        Route::get('tasks', [TaskController::class, 'projectTasks']);
        Route::post('tasks', [TaskController::class, 'store']);
    });
});

