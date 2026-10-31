<?php

namespace App\Repositories\Contracts;

use App\Models\Project;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProjectRepositoryInterface
{
    public function create(array $data): Project;

    public function findById(int $id): ?Project;

    public function update(Project $project, array $data): bool;

    public function delete(Project $project): bool;

    public function paginateForUser(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function attachMember(Project $project, int $userId, string $role, ?DateTimeInterface $joinedAt = null): void;

    public function updateMemberRole(Project $project, int $userId, string $role): void;

    public function detachMember(Project $project, int $userId): void;

    public function isMember(Project $project, int $userId): bool;

    public function countOwners(Project $project): int;
}

