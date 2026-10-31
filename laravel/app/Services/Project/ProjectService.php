<?php

namespace App\Services\Project;

use App\DTOs\Project\AddProjectMemberDTO;
use App\DTOs\Project\StoreProjectDTO;
use App\DTOs\Project\UpdateProjectDTO;
use App\DTOs\Project\UpdateProjectMemberRoleDTO;
use App\Enums\ProjectRole;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\User;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use App\Repositories\Contracts\TeamRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProjectService
{
    public function __construct(
        protected ProjectRepositoryInterface $projectRepository,
        protected TeamRepositoryInterface $teamRepository,
        protected UserRepositoryInterface $userRepository
    ) {}

    /**
     * Get paginated projects where the user is an enrolled member.
     */
    public function getUserProjects(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->projectRepository->paginateForUser($user, $filters, $perPage);
    }

    /**
     * Create a new project under a team with creator as initial OWNER.
     */
    public function createProject(User $creator, StoreProjectDTO $dto): Project
    {
        return DB::transaction(function () use ($creator, $dto) {
            $data = $dto->toArray();
            $data['created_by'] = $creator->id;

            $project = $this->projectRepository->create($data);

            $this->projectRepository->attachMember(
                $project,
                $creator->id,
                ProjectRole::OWNER->value,
                now()
            );

            ActivityLog::create([
                'actor_id' => $creator->id,
                'team_id' => $dto->teamId,
                'project_id' => $project->id,
                'subject_type' => Project::class,
                'subject_id' => $project->id,
                'action' => 'created_project',
                'metadata' => [
                    'name' => $project->name,
                    'team_id' => $project->team_id,
                ],
                'created_at' => now(),
            ]);

            return $project->loadCount(['members', 'tasks']);
        });
    }

    /**
     * Update project attributes.
     */
    public function updateProject(Project $project, UpdateProjectDTO $dto): Project
    {
        $data = $dto->toArray();
        if (! empty($data)) {
            $this->projectRepository->update($project, $data);
        }

        return $project->fresh()->loadCount(['members', 'tasks']);
    }

    /**
     * Soft-delete a project.
     */
    public function deleteProject(Project $project): void
    {
        $this->projectRepository->delete($project);
    }

    /**
     * Get all members of a project.
     */
    public function getProjectMembers(Project $project): Collection
    {
        return $project->members()
            ->withPivot('role', 'joined_at')
            ->orderByPivot('joined_at', 'asc')
            ->get();
    }

    /**
     * Add a member to the project.
     * Candidate must already belong to the parent team.
     *
     * @throws ValidationException
     */
    public function addMember(Project $project, AddProjectMemberDTO $dto, User $actor): User
    {
        $parentTeam = $project->team ?: $this->teamRepository->findById($project->team_id);
        if (! $parentTeam || ! $this->teamRepository->isMember($parentTeam, $dto->userId)) {
            throw ValidationException::withMessages([
                'user_id' => ['User must be an active member of the parent team before joining this project.'],
            ]);
        }

        if ($this->projectRepository->isMember($project, $dto->userId)) {
            throw ValidationException::withMessages([
                'user_id' => ['User is already a member of this project.'],
            ]);
        }

        $this->projectRepository->attachMember($project, $dto->userId, $dto->role->value, now());

        ActivityLog::create([
            'actor_id' => $actor->id,
            'team_id' => $project->team_id,
            'project_id' => $project->id,
            'subject_type' => User::class,
            'subject_id' => $dto->userId,
            'action' => 'added_project_member',
            'metadata' => [
                'role' => $dto->role->value,
            ],
            'created_at' => now(),
        ]);

        return $project->members()->where('users.id', $dto->userId)->first();
    }

    /**
     * Change a project member's role.
     *
     * @throws ValidationException
     */
    public function changeMemberRole(Project $project, User $targetUser, UpdateProjectMemberRoleDTO $dto): void
    {
        if (! $this->projectRepository->isMember($project, $targetUser->id)) {
            throw ValidationException::withMessages([
                'user' => ['User is not a member of this project.'],
            ]);
        }

        $currentRole = $project->userRole($targetUser);

        // If demoting an OWNER, ensure at least one other OWNER remains
        if ($currentRole === ProjectRole::OWNER && $dto->role !== ProjectRole::OWNER) {
            if ($this->projectRepository->countOwners($project) <= 1) {
                throw ValidationException::withMessages([
                    'role' => ['Project must retain at least one owner.'],
                ]);
            }
        }

        $this->projectRepository->updateMemberRole($project, $targetUser->id, $dto->role->value);
    }

    /**
     * Remove a member from the project.
     * Preserves task assignments for audit and history, but access is revoked immediately.
     *
     * @throws ValidationException
     */
    public function removeMember(Project $project, User $targetUser): void
    {
        if (! $this->projectRepository->isMember($project, $targetUser->id)) {
            throw ValidationException::withMessages([
                'user' => ['User is not a member of this project.'],
            ]);
        }

        $currentRole = $project->userRole($targetUser);

        // Ensure at least one OWNER remains
        if ($currentRole === ProjectRole::OWNER && $this->projectRepository->countOwners($project) <= 1) {
            throw ValidationException::withMessages([
                'user' => ['Project must retain at least one owner.'],
            ]);
        }

        $this->projectRepository->detachMember($project, $targetUser->id);
    }
}

