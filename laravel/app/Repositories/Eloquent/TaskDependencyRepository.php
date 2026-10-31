<?php

namespace App\Repositories\Eloquent;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\TaskDependency;
use App\Repositories\Contracts\TaskDependencyRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class TaskDependencyRepository implements TaskDependencyRepositoryInterface
{
    public function addDependency(int $taskId, int $dependsOnTaskId): void
    {
        TaskDependency::firstOrCreate([
            'task_id' => $taskId,
            'depends_on_task_id' => $dependsOnTaskId,
        ]);
    }

    public function removeDependency(int $taskId, int $dependsOnTaskId): bool
    {
        return (bool) TaskDependency::where('task_id', $taskId)
            ->where('depends_on_task_id', $dependsOnTaskId)
            ->delete();
    }

    public function hasDependency(int $taskId, int $dependsOnTaskId): bool
    {
        return TaskDependency::where('task_id', $taskId)
            ->where('depends_on_task_id', $dependsOnTaskId)
            ->exists();
    }

    /**
     * @return Collection<int, Task>
     */
    public function getDependencies(Task $task): Collection
    {
        return $task->dependencies()->get();
    }

    /**
     * @return Collection<int, Task>
     */
    public function getDependents(Task $task): Collection
    {
        return $task->dependents()->get();
    }

    /**
     * @return array<int>
     */
    public function getPrerequisiteIds(int $taskId): array
    {
        return TaskDependency::where('task_id', $taskId)
            ->pluck('depends_on_task_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function hasUnfinishedDependencies(int $taskId): bool
    {
        return Task::whereIn('id', function ($query) use ($taskId) {
            $query->select('depends_on_task_id')
                ->from('task_dependencies')
                ->where('task_id', $taskId);
        })->where('status', '!=', TaskStatus::DONE->value)->exists();
    }
}

