<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;

class TeamPolicy
{
    /**
     * Determine whether user can view the team.
     */
    public function view(User $user, Team $team): bool
    {
        return $user->isAdmin() || $team->isMember($user);
    }

    /**
     * Determine whether user can update the team settings.
     */
    public function update(User $user, Team $team): bool
    {
        return $user->isAdmin() || $team->isOwnerOrManager($user);
    }

    /**
     * Determine whether user can delete the team.
     */
    public function delete(User $user, Team $team): bool
    {
        return $user->isAdmin() || $team->isOwner($user);
    }

    /**
     * Determine whether user can manage team members (add/remove).
     */
    public function manageMembers(User $user, Team $team): bool
    {
        return $user->isAdmin() || $team->isOwnerOrManager($user);
    }

    /**
     * Determine whether user can change member roles.
     */
    public function changeRole(User $user, Team $team): bool
    {
        return $user->isAdmin() || $team->isOwner($user);
    }
}

