<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Task\AddDependencyDTO;
use App\DTOs\Task\ChangeTaskStatusDTO;
use App\Http\Requests\Api\Task\AddDependencyRequest;
use App\Http\Requests\Api\Task\ChangeTaskStatusRequest;
use App\Http\Resources\Api\TaskResource;
use App\Http\Resources\Api\TaskStatusHistoryResource;
use App\Models\Task;
use App\Services\Task\DependencyService;
use App\Services\Task\TaskWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskWorkflowController extends ApiController
{
    public function __construct(
        protected TaskWorkflowService $workflowService,
        protected DependencyService $dependencyService,
    ) {}

    /**
     * Transition task status with state machine checks and audit trail.
     */
    public function changeStatus(ChangeTaskStatusRequest $request, Task $task): JsonResponse
    {
        return $this->handleApi(function () use ($request, $task) {
            $this->authorize('changeStatus', $task);

            $dto = ChangeTaskStatusDTO::fromRequest($request);
            $updatedTask = $this->workflowService->changeStatus($task, $dto, $request->user());

            return $this->jsonResponse(
                data: TaskResource::make($updatedTask),
                msg: 'Task status updated successfully.'
            );
        });
    }

    /**
     * Add a prerequisite dependency to the task.
     */
    public function addDependency(AddDependencyRequest $request, Task $task): JsonResponse
    {
        return $this->handleApi(function () use ($request, $task) {
            $this->authorize('manageDependencies', $task);

            $dto = AddDependencyDTO::fromRequest($request);
            $this->dependencyService->addDependency($task, $dto->dependsOnTaskId, $request->user());

            return $this->jsonResponse(
                msg: 'Task dependency added successfully.',
                code: 201
            );
        });
    }

    /**
     * Remove a prerequisite dependency from the task.
     */
    public function removeDependency(Task $task, Task $dependency): JsonResponse
    {
        return $this->handleApi(function () use ($task, $dependency) {
            $this->authorize('manageDependencies', $task);

            $this->dependencyService->removeDependency($task, $dependency->id, request()->user());

            return $this->jsonResponse(
                msg: 'Task dependency removed successfully.'
            );
        });
    }

    /**
     * Get all prerequisites (dependencies) and dependents for a task.
     */
    public function dependencies(Task $task): JsonResponse
    {
        return $this->handleApi(function () use ($task) {
            $this->authorize('view', $task);

            $data = $this->dependencyService->getTaskDependencies($task);

            return $this->jsonResponse(
                data: [
                    'dependencies' => TaskResource::collection($data['dependencies']),
                    'dependents' => TaskResource::collection($data['dependents']),
                ],
                msg: 'Task dependencies retrieved successfully.'
            );
        });
    }

    /**
     * Get paginated status audit trail for a task.
     */
    public function history(Request $request, Task $task): JsonResponse
    {
        return $this->handleApi(function () use ($request, $task) {
            $this->authorize('view', $task);

            $perPage = (int) $request->input('per_page', 15);
            $history = $this->workflowService->getHistory($task, $perPage);

            return $this->jsonResponse(
                data: TaskStatusHistoryResource::collection($history)->response()->getData(true),
                msg: 'Task status history retrieved successfully.'
            );
        });
    }
}

