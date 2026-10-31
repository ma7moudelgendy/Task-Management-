<?php

namespace App\Repositories\Eloquent;

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\User;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProjectRepository implements ProjectRepositoryInterface
{
    public function create(array $data): Project
    {
        return Project::create($data);
    }

    public function findById(int $id): ?Project
    {
        return Project::withCount(['members', 'tasks'])->find($id);
    }

    public function update(Project $project, array $data): bool
    {
        return $project->update($data);
    }

    public function delete(Project $project): bool
    {
        return $project->delete();
    }

    public function paginateForUser(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $user->projects()
            ->withCount(['members', 'tasks'])
            ->when(! empty($filters['team_id']), fn ($q) => $q->where('projects.team_id', $filters['team_id']))
            ->when(! empty($filters['status']), fn ($q) => $q->where('projects.status', $filters['status']))
            ->orderBy('projects.created_at', 'desc')
            ->paginate($perPage);
    }

    public function attachMember(Project $project, int $userId, string $role, ?DateTimeInterface $joinedAt = null): void
    {
        $project->members()->attach($userId, [
            'role' => $role,
            'joined_at' => $joinedAt ?? now(),
        ]);
    }

    public function updateMemberRole(Project $project, int $userId, string $role): void
    {
        $project->members()->updateExistingPivot($userId, [
            'role' => $role,
        ]);
    }

    public function detachMember(Project $project, int $userId): void
    {
        $project->members()->detach($userId);
    }

    public function isMember(Project $project, int $userId): bool
    {
        return $project->members()->where('project_members.user_id', $userId)->exists();
    }

    public function countOwners(Project $project): int
    {
        return $project->members()->where('project_members.role', ProjectRole::OWNER->value)->count();
    }
}

