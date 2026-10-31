<?php

namespace App\Policies;

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    /**
     * Determine whether user can view the task.
     * Caller must be a member of the task's project or system ADMIN.
     */
    public function view(User $user, Task $task): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $task->project->isMember($user);
    }

    /**
     * Determine whether user can create a task in the given project.
     * Caller must hold OWNER, MANAGER, or MEMBER in the project (VIEWER is denied), or system ADMIN.
     */
    public function create(User $user, Project $project): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $role = $project->userRole($user);

        return in_array($role, [ProjectRole::OWNER, ProjectRole::MANAGER, ProjectRole::MEMBER], true);
    }

    /**
     * Determine whether user can update the task.
     * Caller must be project OWNER, MANAGER, task creator, an assignee, or system ADMIN.
     */
    public function update(User $user, Task $task): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Project OWNER or MANAGER
        if ($task->project->isOwnerOrManager($user)) {
            return true;
        }

        // Task Creator or Assignee
        if ($task->isCreator($user) || $task->isAssignee($user)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether user can delete the task.
     * Caller must be project OWNER, MANAGER, task creator, or system ADMIN.
     */
    public function delete(User $user, Task $task): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($task->project->isOwnerOrManager($user)) {
            return true;
        }

        return $task->isCreator($user);
    }

    /**
     * Determine whether user can assign/sync assignees on the task.
     * Caller must hold OWNER or MANAGER in the project, or system ADMIN.
     */
    public function assign(User $user, Task $task): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $task->project->isOwnerOrManager($user);
    }

    /**
     * Determine whether user can change status of the task.
     * Caller must be project OWNER, MANAGER, creator, assignee, or system ADMIN.
     */
    public function changeStatus(User $user, Task $task): bool
    {
        return $this->update($user, $task);
    }

    /**
     * Determine whether user can manage dependencies of the task.
     * Caller must be project OWNER, MANAGER, task creator, or system ADMIN.
     */
    public function manageDependencies(User $user, Task $task): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($task->project->isOwnerOrManager($user)) {
            return true;
        }

        return $task->isCreator($user);
    }
}

