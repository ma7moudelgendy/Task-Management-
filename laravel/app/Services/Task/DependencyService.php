<?php

namespace App\Services\Task;

use App\Models\ActivityLog;
use App\Models\Task;
use App\Models\User;
use App\Repositories\Contracts\TaskDependencyRepositoryInterface;
use App\Repositories\Contracts\TaskRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DependencyService
{
    public function __construct(
        protected TaskDependencyRepositoryInterface $dependencyRepository,
        protected TaskRepositoryInterface $taskRepository,
    ) {}

    /**
     * Add a prerequisite dependency to a task.
     *
     * @throws ValidationException
     */
    public function addDependency(Task $task, int $prerequisiteTaskId, User $actor): void
    {
        if ((int) $task->id === (int) $prerequisiteTaskId) {
            throw ValidationException::withMessages([
                'depends_on_task_id' => ['A task cannot depend on itself.'],
            ]);
        }

        $prerequisite = $this->taskRepository->findById($prerequisiteTaskId);
        if (! $prerequisite) {
            throw ValidationException::withMessages([
                'depends_on_task_id' => ['The selected prerequisite task does not exist.'],
            ]);
        }

        if ((int) $prerequisite->project_id !== (int) $task->project_id) {
            throw ValidationException::withMessages([
                'depends_on_task_id' => ['Dependencies must belong to the same project.'],
            ]);
        }

        if ($this->dependencyRepository->hasDependency($task->id, $prerequisiteTaskId)) {
            throw ValidationException::withMessages([
                'depends_on_task_id' => ['This task already depends on the specified prerequisite task.'],
            ]);
        }

        if ($this->wouldCreateCycle($task->id, $prerequisiteTaskId)) {
            throw ValidationException::withMessages([
                'depends_on_task_id' => ['Adding this dependency would create a circular dependency loop.'],
            ]);
        }

        DB::transaction(function () use ($task, $prerequisiteTaskId, $actor) {
            $this->dependencyRepository->addDependency($task->id, $prerequisiteTaskId);

            ActivityLog::create([
                'actor_id' => $actor->id,
                'team_id' => $task->project?->team_id,
                'project_id' => $task->project_id,
                'subject_type' => Task::class,
                'subject_id' => $task->id,
                'action' => 'added_dependency',
                'metadata' => [
                    'depends_on_task_id' => $prerequisiteTaskId,
                ],
                'created_at' => now(),
            ]);
        });
    }

    /**
     * Remove a prerequisite dependency from a task.
     */
    public function removeDependency(Task $task, int $prerequisiteTaskId, User $actor): void
    {
        DB::transaction(function () use ($task, $prerequisiteTaskId, $actor) {
            $this->dependencyRepository->removeDependency($task->id, $prerequisiteTaskId);

            ActivityLog::create([
                'actor_id' => $actor->id,
                'team_id' => $task->project?->team_id,
                'project_id' => $task->project_id,
                'subject_type' => Task::class,
                'subject_id' => $task->id,
                'action' => 'removed_dependency',
                'metadata' => [
                    'depends_on_task_id' => $prerequisiteTaskId,
                ],
                'created_at' => now(),
            ]);
        });
    }

    /**
     * Check if a task has any unfinished prerequisite dependencies.
     */
    public function hasUnfinishedDependencies(Task $task): bool
    {
        return $this->dependencyRepository->hasUnfinishedDependencies($task->id);
    }

    /**
     * Check if adding an edge $blockedTaskId -> $prerequisiteTaskId would create a directed cycle.
     */
    public function wouldCreateCycle(int $blockedTaskId, int $prerequisiteTaskId): bool
    {
        if ($blockedTaskId === $prerequisiteTaskId) {
            return true;
        }

        $visited = [];
        $stack = [$prerequisiteTaskId];

        while (! empty($stack)) {
            $currentId = array_pop($stack);

            if ($currentId === $blockedTaskId) {
                return true;
            }

            if (isset($visited[$currentId])) {
                continue;
            }

            $visited[$currentId] = true;

            $nextPrerequisites = $this->dependencyRepository->getPrerequisiteIds($currentId);
            foreach ($nextPrerequisites as $nextId) {
                if ($nextId === $blockedTaskId) {
                    return true;
                }

                if (! isset($visited[$nextId])) {
                    $stack[] = $nextId;
                }
            }
        }

        return false;
    }

    /**
     * Get all dependencies (prerequisites) and dependents for a task.
     *
     * @return array{dependencies: Collection<int, Task>, dependents: Collection<int, Task>}
     */
    public function getTaskDependencies(Task $task): array
    {
        return [
            'dependencies' => $this->dependencyRepository->getDependencies($task),
            'dependents' => $this->dependencyRepository->getDependents($task),
        ];
    }
}

