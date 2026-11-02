<?php

namespace App\Services\Task;

use App\DTOs\Task\ChangeTaskStatusDTO;
use App\Enums\TaskStatus;
use App\Events\TaskStatusChanged;
use App\Models\ActivityLog;
use App\Models\Task;
use App\Models\User;
use App\Repositories\Contracts\TaskRepositoryInterface;
use App\Repositories\Contracts\TaskStatusHistoryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TaskWorkflowService
{
    public function __construct(
        protected TaskRepositoryInterface $taskRepository,
        protected TaskStatusHistoryRepositoryInterface $statusHistoryRepository,
        protected DependencyService $dependencyService,
    ) {}

    /**
     * Change task status enforcing state machine transitions, dependency gates, and audit trails.
     *
     * @throws ValidationException
     */
    public function changeStatus(Task $task, ChangeTaskStatusDTO $dto, User $actor): Task
    {
        $oldStatus = $task->status;

        // If target status is identical to current, no transition needed
        if ($oldStatus === $dto->status) {
            return $task;
        }

        // Validate allowed state transitions
        if (! $oldStatus->canTransitionTo($dto->status)) {
            throw ValidationException::withMessages([
                'status' => ["Transition from {$oldStatus->value} to {$dto->status->value} is not allowed."],
            ]);
        }

        // Completion Gate 1: Check prerequisite dependencies
        if ($dto->status === TaskStatus::DONE && $this->dependencyService->hasUnfinishedDependencies($task)) {
            throw ValidationException::withMessages([
                'status' => ['Cannot complete task because prerequisite dependencies remain incomplete.'],
            ]);
        }

        // Completion Gate 2: Check subtasks completion
        if ($dto->status === TaskStatus::DONE) {
            $hasIncompleteSubtasks = $task->subtasks()
                ->whereNotIn('status', [TaskStatus::DONE->value, TaskStatus::CANCELLED->value])
                ->exists();

            if ($hasIncompleteSubtasks) {
                throw ValidationException::withMessages([
                    'status' => ['Cannot complete task because active subtasks remain incomplete.'],
                ]);
            }
        }

        return DB::transaction(function () use ($task, $dto, $actor, $oldStatus) {
            $task->status = $dto->status;
            $task->completed_at = ($dto->status === TaskStatus::DONE) ? now() : null;
            $task->save();

            // Record immutable audit history
            $this->statusHistoryRepository->create([
                'task_id' => $task->id,
                'changed_by' => $actor->id,
                'old_status' => $oldStatus->value,
                'new_status' => $dto->status->value,
                'reason' => $dto->reason,
                'created_at' => now(),
            ]);

            // Record activity log
            ActivityLog::create([
                'actor_id' => $actor->id,
                'team_id' => $task->project?->team_id,
                'project_id' => $task->project_id,
                'subject_type' => Task::class,
                'subject_id' => $task->id,
                'action' => 'status_changed',
                'metadata' => [
                    'old_status' => $oldStatus->value,
                    'new_status' => $dto->status->value,
                    'reason' => $dto->reason,
                ],
                'created_at' => now(),
            ]);

            TaskStatusChanged::dispatch($task, $oldStatus->value, $dto->status->value, $actor);

            return $task->fresh(['assignees', 'creator', 'project:id,name,team_id', 'subtasks']);
        });
    }

    /**
     * Get paginated status audit trail for a task.
     */
    public function getHistory(Task $task, int $perPage = 15): LengthAwarePaginator
    {
        return $this->statusHistoryRepository->paginateForTask($task, $perPage);
    }
}

