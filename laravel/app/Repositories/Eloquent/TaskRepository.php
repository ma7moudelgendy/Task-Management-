<?php

namespace App\Repositories\Eloquent;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Repositories\Contracts\TaskRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class TaskRepository implements TaskRepositoryInterface
{
    public function create(array $data): Task
    {
        return Task::create($data);
    }

    public function findById(int $id, array $with = []): ?Task
    {
        $query = Task::query();

        if (! empty($with)) {
            $query->with($with);
        }

        return $query->find($id);
    }

    public function update(Task $task, array $data): bool
    {
        return $task->update($data);
    }

    public function delete(Task $task): bool
    {
        return $task->delete();
    }

    public function paginateForUser(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Task::query()
            ->with(['assignees', 'creator', 'project:id,name,team_id']);

        if (! $user->isAdmin()) {
            $projectIds = $user->projects()->pluck('projects.id');
            $query->whereIn('tasks.project_id', $projectIds);
        }

        $this->applyFilters($query, $filters);

        return $query->paginate($perPage);
    }

    public function paginateForProject(Project $project, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Task::query()
            ->where('tasks.project_id', $project->id)
            ->with(['assignees', 'creator', 'project:id,name,team_id']);

        $this->applyFilters($query, $filters);

        return $query->paginate($perPage);
    }

    public function syncAssignees(Task $task, array $assigneeIds, int $assignedBy): void
    {
        $pivotData = [];
        $now = now();
        foreach ($assigneeIds as $userId) {
            $pivotData[$userId] = [
                'assigned_by' => $assignedBy,
                'assigned_at' => $now,
            ];
        }

        $task->assignees()->sync($pivotData);
    }

    protected function applyFilters(Builder|Relation $query, array $filters): void
    {
        if (! empty($filters['status'])) {
            $query->where('tasks.status', $filters['status']);
        }

        if (! empty($filters['priority'])) {
            $query->where('tasks.priority', $filters['priority']);
        }

        if (! empty($filters['assignee_id'])) {
            $query->whereHas('assignees', fn ($q) => $q->where('task_assignees.user_id', $filters['assignee_id']));
        }

        if (! empty($filters['due_before'])) {
            $query->where('tasks.due_at', '<=', $filters['due_before']);
        }

        if (! empty($filters['due_after'])) {
            $query->where('tasks.due_at', '>=', $filters['due_after']);
        }

        $allowedSorts = ['due_at', 'priority', 'created_at', 'position', 'title'];
        $sortBy = in_array($filters['sort_by'] ?? null, $allowedSorts, true) ? $filters['sort_by'] : 'created_at';
        $sortDir = strtolower($filters['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $query->orderBy("tasks.{$sortBy}", $sortDir);
    }
}

