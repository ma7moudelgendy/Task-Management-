<?php

namespace App\Services\Team;

use App\DTOs\Team\AddTeamMemberDTO;
use App\DTOs\Team\StoreTeamDTO;
use App\DTOs\Team\UpdateTeamDTO;
use App\DTOs\Team\UpdateTeamMemberRoleDTO;
use App\Enums\TeamRole;
use App\Models\ActivityLog;
use App\Models\Team;
use App\Models\User;
use App\Repositories\Contracts\TeamRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TeamService
{
    public function __construct(
        protected TeamRepositoryInterface $teamRepository,
        protected UserRepositoryInterface $userRepository
    ) {}

    /**
     * Get paginated teams where user is a member.
     */
    public function getUserTeams(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $this->teamRepository->paginateForUser($user, $perPage);
    }

    /**
     * Create a new team with creator as initial OWNER in a transaction.
     */
    public function createTeam(User $creator, StoreTeamDTO $dto): Team
    {
        $slug = $dto->slug ?: $this->generateUniqueSlug($dto->name);

        return DB::transaction(function () use ($creator, $dto, $slug) {
            $team = $this->teamRepository->create([
                'name' => $dto->name,
                'slug' => $slug,
                'created_by' => $creator->id,
            ]);

            $this->teamRepository->attachMember($team, $creator->id, TeamRole::OWNER->value, now());

            ActivityLog::create([
                'actor_id' => $creator->id,
                'team_id' => $team->id,
                'project_id' => null,
                'subject_type' => Team::class,
                'subject_id' => $team->id,
                'action' => 'created_team',
                'metadata' => [
                    'name' => $team->name,
                    'slug' => $team->slug,
                ],
                'created_at' => now(),
            ]);

            return $team->loadCount('members');
        });
    }

    /**
     * Update team details.
     */
    public function updateTeam(Team $team, UpdateTeamDTO $dto): Team
    {
        $data = $dto->toArray();
        if (! empty($data)) {
            $this->teamRepository->update($team, $data);
        }

        return $team->fresh()->loadCount('members');
    }

    /**
     * Soft-delete a team.
     */
    public function deleteTeam(Team $team): void
    {
        $this->teamRepository->delete($team);
    }

    /**
     * Get all members of a team.
     */
    public function getTeamMembers(Team $team): Collection
    {
        return $team->members()
            ->withPivot('role', 'joined_at')
            ->orderByPivot('joined_at', 'asc')
            ->get();
    }

    /**
     * Add a member to the team by email.
     *
     * @throws ValidationException
     */
    public function addMember(Team $team, AddTeamMemberDTO $dto, User $actor): User
    {
        $targetUser = $this->userRepository->findByEmail($dto->email);
        if (! $targetUser) {
            throw ValidationException::withMessages([
                'email' => ['User with this email does not exist.'],
            ]);
        }

        if ($this->teamRepository->isMember($team, $targetUser->id)) {
            throw ValidationException::withMessages([
                'email' => ['User is already a member of this team.'],
            ]);
        }

        $this->teamRepository->attachMember($team, $targetUser->id, $dto->role->value, now());

        ActivityLog::create([
            'actor_id' => $actor->id,
            'team_id' => $team->id,
            'project_id' => null,
            'subject_type' => User::class,
            'subject_id' => $targetUser->id,
            'action' => 'added_team_member',
            'metadata' => [
                'role' => $dto->role->value,
                'user_email' => $targetUser->email,
            ],
            'created_at' => now(),
        ]);

        return $team->members()->where('users.id', $targetUser->id)->first();
    }

    /**
     * Change a team member's role.
     *
     * @throws ValidationException
     */
    public function changeMemberRole(Team $team, User $targetUser, UpdateTeamMemberRoleDTO $dto): void
    {
        if (! $this->teamRepository->isMember($team, $targetUser->id)) {
            throw ValidationException::withMessages([
                'user' => ['User is not a member of this team.'],
            ]);
        }

        $currentRole = $team->userRole($targetUser);

        // If demoting an OWNER, ensure at least one other OWNER remains
        if ($currentRole === TeamRole::OWNER && $dto->role !== TeamRole::OWNER) {
            if ($this->teamRepository->countOwners($team) <= 1) {
                throw ValidationException::withMessages([
                    'role' => ['Team must retain at least one owner.'],
                ]);
            }
        }

        $this->teamRepository->updateMemberRole($team, $targetUser->id, $dto->role->value);
    }

    /**
     * Remove a member from the team.
     *
     * @throws ValidationException
     */
    public function removeMember(Team $team, User $targetUser): void
    {
        if (! $this->teamRepository->isMember($team, $targetUser->id)) {
            throw ValidationException::withMessages([
                'user' => ['User is not a member of this team.'],
            ]);
        }

        $currentRole = $team->userRole($targetUser);

        // If target user is an OWNER, ensure at least one other OWNER remains
        if ($currentRole === TeamRole::OWNER && $this->teamRepository->countOwners($team) <= 1) {
            throw ValidationException::withMessages([
                'user' => ['Team must retain at least one owner.'],
            ]);
        }

        $this->teamRepository->detachMember($team, $targetUser->id);
    }

    /**
     * Generate an auto-incrementing unique slug.
     */
    protected function generateUniqueSlug(string $name, ?int $ignoreTeamId = null): string
    {
        $baseSlug = Str::slug($name);
        if (empty($baseSlug)) {
            $baseSlug = 'team';
        }

        $slug = $baseSlug;
        $counter = 1;

        while ($this->teamRepository->slugExists($slug, $ignoreTeamId)) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}

