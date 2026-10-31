<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\Team;
use App\Models\User;

class ProjectPolicy
{
    /**
     * Determine whether user can view the project.
     * Note: Strict membership separation - caller must be in project_members or ADMIN.
     */
    public function view(User $user, Project $project): bool
    {
        return $user->isAdmin() || $project->isMember($user);
    }

    /**
     * Determine whether user can create a project in the given team.
     * Caller must be team OWNER or MANAGER, or system ADMIN.
     */
    public function create(User $user, Team $team): bool
    {
        return $user->isAdmin() || $team->isOwnerOrManager($user);
    }

    /**
     * Determine whether user can update the project.
     * Caller must hold OWNER or MANAGER in the project, or system ADMIN.
     */
    public function update(User $user, Project $project): bool
    {
        return $user->isAdmin() || $project->isOwnerOrManager($user);
    }

    /**
     * Determine whether user can delete the project.
     * Caller must be project OWNER, or system ADMIN.
     */
    public function delete(User $user, Project $project): bool
    {
        return $user->isAdmin() || $project->isOwner($user);
    }

    /**
     * Determine whether user can manage project members (add/remove).
     * Caller must hold OWNER or MANAGER in the project, or system ADMIN.
     */
    public function manageMembers(User $user, Project $project): bool
    {
        return $user->isAdmin() || $project->isOwnerOrManager($user);
    }

    /**
     * Determine whether user can change member roles.
     * Only project OWNER or system ADMIN can promote/demote.
     */
    public function changeRole(User $user, Project $project): bool
    {
        return $user->isAdmin() || $project->isOwner($user);
    }
}

