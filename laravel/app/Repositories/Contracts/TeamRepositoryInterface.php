<?php

namespace App\Repositories\Contracts;

use App\Models\Team;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TeamRepositoryInterface
{
    public function create(array $data): Team;

    public function findById(int $id): ?Team;

    public function findBySlug(string $slug): ?Team;

    public function update(Team $team, array $data): bool;

    public function delete(Team $team): bool;

    public function paginateForUser(User $user, int $perPage = 15): LengthAwarePaginator;

    public function attachMember(Team $team, int $userId, string $role, ?DateTimeInterface $joinedAt = null): void;

    public function updateMemberRole(Team $team, int $userId, string $role): void;

    public function detachMember(Team $team, int $userId): void;

    public function isMember(Team $team, int $userId): bool;

    public function countOwners(Team $team): int;

    public function slugExists(string $slug, ?int $ignoreTeamId = null): bool;
}

