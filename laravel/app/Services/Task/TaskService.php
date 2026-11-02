<?php

namespace App\Services\Task;

use App\DTOs\Task\FilterTaskDTO;
use App\DTOs\Task\StoreTaskDTO;
use App\DTOs\Task\SyncAssigneesDTO;
use App\DTOs\Task\UpdateTaskDTO;
use App\Enums\TaskStatus;
use App\Events\TaskAssigned;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Repositories\Contracts\TaskRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TaskService
{
    public function __construct(
        protected TaskRepositoryInterface $taskRepository,
    ) {}

    /**
     * Get tasks accessible to the user across projects.
     */
    public function getUserTasks(User $user, FilterTaskDTO $filters): LengthAwarePaginator
    {
        return $this->taskRepository->paginateForUser($user, $filters->toArray(), $filters->perPage);
    }

    /**
     * Get tasks for a specific project.
     */
    public function getProjectTasks(Project $project, FilterTaskDTO $filters): LengthAwarePaginator
    {
        return $this->taskRepository->paginateForProject($project, $filters->toArray(), $filters->perPage);
    }

    /**
     * Create a new task within a project.
     *
     * @throws ValidationException
     */
    public function createTask(Project $project, User $creator, StoreTaskDTO $dto): Task
    {
        return DB::transaction(function () use ($project, $creator, $dto) {
            // Validate parent task belongs to the same project
            if ($dto->parentTaskId) {
                $parentTask = $this->taskRepository->findById($dto->parentTaskId);
                if (! $parentTask || (int) $parentTask->project_id !== (int) $project->id) {
                    throw ValidationException::withMessages([
                        'parent_task_id' => ['The parent task must belong to the same project.'],
                    ]);
                }
            }

            // Validate all assignees are active members of this project
            if (! empty($dto->assigneeIds)) {
                $validMembersCount = $project->members()
                    ->whereIn('project_members.user_id', $dto->assigneeIds)
                    ->count();

                if ($validMembersCount !== count(array_unique($dto->assigneeIds))) {
                    throw ValidationException::withMessages([
                        'assignee_ids' => ['All assignees must be active members of this project.'],
                    ]);
                }
            }

            $taskData = array_merge($dto->toArray(), [
                'project_id' => $project->id,
                'created_by' => $creator->id,
                'status' => TaskStatus::TODO->value,
            ]);

            $task = $this->taskRepository->create($taskData);

            if (! empty($dto->assigneeIds)) {
                $this->taskRepository->syncAssignees($task, $dto->assigneeIds, $creator->id);
                $assignees = User::whereIn('id', $dto->assigneeIds)->get();
                foreach ($assignees as $assignee) {
                    TaskAssigned::dispatch($task, $assignee, $creator);
                }
            }

            ActivityLog::create([
                'actor_id' => $creator->id,
                'team_id' => $project->team_id,
                'project_id' => $project->id,
                'subject_type' => Task::class,
                'subject_id' => $task->id,
                'action' => 'created_task',
                'metadata' => [
                    'title' => $task->title,
                    'priority' => $task->priority->value,
                ],
                'created_at' => now(),
            ]);

            return $task->load(['assignees', 'creator', 'project:id,name,team_id', 'subtasks']);
        });
    }

    /**
     * Update an existing task.
     *
     * @throws ValidationException
     */
    public function updateTask(Task $task, UpdateTaskDTO $dto, User $actor): Task
    {
        return DB::transaction(function () use ($task, $dto, $actor) {
            // Validate circular parenting and project scoping if parent_task_id is being updated
            if ($dto->hasParentTaskId && $dto->parentTaskId !== null) {
                if ($task->wouldCauseCycle($dto->parentTaskId)) {
                    throw ValidationException::withMessages([
                        'parent_task_id' => ['The parent task cannot be the task itself or create a circular dependency.'],
                    ]);
                }

                $parentTask = $this->taskRepository->findById($dto->parentTaskId);
                if (! $parentTask || (int) $parentTask->project_id !== (int) $task->project_id) {
                    throw ValidationException::withMessages([
                        'parent_task_id' => ['The parent task must belong to the same project.'],
                    ]);
                }
            }

            $updateData = $dto->toArray();

            // Handle completion timestamp transitions
            if ($dto->status !== null) {
                if ($dto->status === TaskStatus::DONE && $task->status !== TaskStatus::DONE) {
                    $updateData['completed_at'] = now();
                } elseif ($dto->status !== TaskStatus::DONE && $task->status === TaskStatus::DONE) {
                    $updateData['completed_at'] = null;
                }
            }

            $this->taskRepository->update($task, $updateData);

            ActivityLog::create([
                'actor_id' => $actor->id,
                'team_id' => $task->project?->team_id,
                'project_id' => $task->project_id,
                'subject_type' => Task::class,
                'subject_id' => $task->id,
                'action' => 'updated_task',
                'metadata' => $updateData,
                'created_at' => now(),
            ]);

            return $task->fresh(['assignees', 'creator', 'project:id,name,team_id', 'subtasks']);
        });
    }

    /**
     * Delete a task.
     */
    public function deleteTask(Task $task, User $actor): void
    {
        $this->taskRepository->delete($task);

        ActivityLog::create([
            'actor_id' => $actor->id,
            'team_id' => $task->project?->team_id,
            'project_id' => $task->project_id,
            'subject_type' => Task::class,
            'subject_id' => $task->id,
            'action' => 'deleted_task',
            'metadata' => ['title' => $task->title],
            'created_at' => now(),
        ]);
    }

    /**
     * Atomically sync assignees on a task.
     *
     * @throws ValidationException
     */
    public function syncAssignees(Task $task, SyncAssigneesDTO $dto, User $assigner): Task
    {
        return DB::transaction(function () use ($task, $dto, $assigner) {
            $project = $task->project;

            if (! empty($dto->assigneeIds)) {
                $validMembersCount = $project->members()
                    ->whereIn('project_members.user_id', $dto->assigneeIds)
                    ->count();

                if ($validMembersCount !== count(array_unique($dto->assigneeIds))) {
                    throw ValidationException::withMessages([
                        'assignee_ids' => ['All assignees must be active members of this project.'],
                    ]);
                }
            }

            $previousAssigneeIds = $task->assignees()->pluck('users.id')->all();

            $this->taskRepository->syncAssignees($task, $dto->assigneeIds, $assigner->id);

            $newlyAssignedIds = array_diff($dto->assigneeIds, $previousAssigneeIds);
            if (! empty($newlyAssignedIds)) {
                $newAssignees = User::whereIn('id', $newlyAssignedIds)->get();
                foreach ($newAssignees as $assignee) {
                    TaskAssigned::dispatch($task, $assignee, $assigner);
                }
            }

            ActivityLog::create([
                'actor_id' => $assigner->id,
                'team_id' => $project->team_id,
                'project_id' => $project->id,
                'subject_type' => Task::class,
                'subject_id' => $task->id,
                'action' => 'synced_task_assignees',
                'metadata' => ['assignee_ids' => $dto->assigneeIds],
                'created_at' => now(),
            ]);

            return $task->fresh(['assignees', 'creator', 'project:id,name,team_id', 'subtasks']);
        });
    }
}

