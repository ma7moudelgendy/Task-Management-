<?php

namespace App\Repositories\Eloquent;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use App\Repositories\Contracts\TeamRepositoryInterface;
use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TeamRepository implements TeamRepositoryInterface
{
    public function create(array $data): Team
    {
        return Team::create($data);
    }

    public function findById(int $id): ?Team
    {
        return Team::withCount('members')->find($id);
    }

    public function findBySlug(string $slug): ?Team
    {
        return Team::withCount('members')->where('slug', $slug)->first();
    }

    public function update(Team $team, array $data): bool
    {
        return $team->update($data);
    }

    public function delete(Team $team): bool
    {
        return $team->delete();
    }

    public function paginateForUser(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $user->teams()
            ->withCount('members')
            ->orderByPivot('joined_at', 'desc')
            ->paginate($perPage);
    }

    public function attachMember(Team $team, int $userId, string $role, ?DateTimeInterface $joinedAt = null): void
    {
        $team->members()->attach($userId, [
            'role' => $role,
            'joined_at' => $joinedAt ?? now(),
        ]);
    }

    public function updateMemberRole(Team $team, int $userId, string $role): void
    {
        $team->members()->updateExistingPivot($userId, [
            'role' => $role,
        ]);
    }

    public function detachMember(Team $team, int $userId): void
    {
        $team->members()->detach($userId);
    }

    public function isMember(Team $team, int $userId): bool
    {
        return $team->members()->where('team_members.user_id', $userId)->exists();
    }

    public function countOwners(Team $team): int
    {
        return $team->members()->where('team_members.role', TeamRole::OWNER->value)->count();
    }

    public function slugExists(string $slug, ?int $ignoreTeamId = null): bool
    {
        return Team::where('slug', $slug)
            ->when($ignoreTeamId, fn ($query) => $query->where('id', '!=', $ignoreTeamId))
            ->exists();
    }
}
