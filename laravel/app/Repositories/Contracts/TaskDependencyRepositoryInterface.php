<?php

namespace App\Repositories\Contracts;

use App\Models\Task;
use Illuminate\Database\Eloquent\Collection;

interface TaskDependencyRepositoryInterface
{
    public function addDependency(int $taskId, int $dependsOnTaskId): void;

    public function removeDependency(int $taskId, int $dependsOnTaskId): bool;

    public function hasDependency(int $taskId, int $dependsOnTaskId): bool;

    /**
     * @return Collection<int, Task>
     */
    public function getDependencies(Task $task): Collection;

    /**
     * @return Collection<int, Task>
     */
    public function getDependents(Task $task): Collection;

    /**
     * @return array<int>
     */
    public function getPrerequisiteIds(int $taskId): array;

    public function hasUnfinishedDependencies(int $taskId): bool;
}

