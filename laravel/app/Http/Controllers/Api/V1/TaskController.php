<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Task\FilterTaskDTO;
use App\DTOs\Task\StoreTaskDTO;
use App\DTOs\Task\SyncAssigneesDTO;
use App\DTOs\Task\UpdateTaskDTO;
use App\Http\Requests\Api\Task\FilterTaskRequest;
use App\Http\Requests\Api\Task\StoreTaskRequest;
use App\Http\Requests\Api\Task\SyncAssigneesRequest;
use App\Http\Requests\Api\Task\UpdateTaskRequest;
use App\Http\Resources\Api\TaskResource;
use App\Models\Project;
use App\Models\Task;
use App\Services\Task\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends ApiController
{
    public function __construct(
        protected TaskService $taskService,
    ) {}

    /**
     * Display a listing of accessible tasks across user's projects.
     */
    public function index(FilterTaskRequest $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $dto = FilterTaskDTO::fromRequest($request);
            $tasks = $this->taskService->getUserTasks($request->user(), $dto);

            return $this->jsonResponse(
                data: TaskResource::collection($tasks)->response()->getData(true),
                msg: 'Tasks retrieved successfully.'
            );
        });
    }

    /**
     * Display a listing of tasks for a specific project.
     */
    public function projectTasks(Project $project, FilterTaskRequest $request): JsonResponse
    {
        return $this->handleApi(function () use ($project, $request) {
            $this->authorize('view', $project);

            $dto = FilterTaskDTO::fromRequest($request);
            $tasks = $this->taskService->getProjectTasks($project, $dto);

            return $this->jsonResponse(
                data: TaskResource::collection($tasks)->response()->getData(true),
                msg: 'Project tasks retrieved successfully.'
            );
        });
    }

    /**
     * Store a newly created task in a project.
     */
    public function store(StoreTaskRequest $request, Project $project): JsonResponse
    {
        return $this->handleApi(function () use ($request, $project) {
            $this->authorize('create', [Task::class, $project]);

            $dto = StoreTaskDTO::fromRequest($request);
            $task = $this->taskService->createTask($project, $request->user(), $dto);

            return $this->jsonResponse(
                data: TaskResource::make($task),
                msg: 'Task created successfully.',
                code: 201
            );
        });
    }

    /**
     * Display the specified task with relations.
     */
    public function show(Task $task): JsonResponse
    {
        return $this->handleApi(function () use ($task) {
            $this->authorize('view', $task);

            $task->load(['assignees', 'creator', 'project:id,name,team_id', 'subtasks']);

            return $this->jsonResponse(
                data: TaskResource::make($task),
                msg: 'Task retrieved successfully.'
            );
        });
    }

    /**
     * Update the specified task.
     */
    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        return $this->handleApi(function () use ($request, $task) {
            $this->authorize('update', $task);

            $dto = UpdateTaskDTO::fromRequest($request);
            $updatedTask = $this->taskService->updateTask($task, $dto, $request->user());

            return $this->jsonResponse(
                data: TaskResource::make($updatedTask),
                msg: 'Task updated successfully.'
            );
        });
    }

    /**
     * Soft delete the specified task.
     */
    public function destroy(Task $task, Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($task, $request) {
            $this->authorize('delete', $task);

            $this->taskService->deleteTask($task, $request->user());

            return $this->jsonResponse(
                msg: 'Task deleted successfully.'
            );
        });
    }

    /**
     * Atomically synchronize assignees on a task.
     */
    public function syncAssignees(SyncAssigneesRequest $request, Task $task): JsonResponse
    {
        return $this->handleApi(function () use ($request, $task) {
            $this->authorize('assign', $task);

            $dto = SyncAssigneesDTO::fromRequest($request);
            $syncedTask = $this->taskService->syncAssignees($task, $dto, $request->user());

            return $this->jsonResponse(
                data: TaskResource::make($syncedTask),
                msg: 'Task assignees synchronized successfully.'
            );
        });
    }
}

