<?php

namespace App\Repositories\Contracts;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TaskRepositoryInterface
{
    public function create(array $data): Task;

    public function findById(int $id, array $with = []): ?Task;

    public function update(Task $task, array $data): bool;

    public function delete(Task $task): bool;

    public function paginateForUser(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function paginateForProject(Project $project, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function syncAssignees(Task $task, array $assigneeIds, int $assignedBy): void;
}

