<?php

namespace App\Policies;

use App\Enums\ProjectRole;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;

class CommentPolicy
{
    /**
     * Determine whether user can view comments on the task.
     */
    public function view(User $user, Task $task): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $task->project->isMember($user);
    }

    /**
     * Determine whether user can post a comment on the task.
     * Caller must hold OWNER, MANAGER, or MEMBER in the project (VIEWER denied).
     */
    public function create(User $user, Task $task): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $role = $task->project->userRole($user);

        return in_array($role, [ProjectRole::OWNER, ProjectRole::MANAGER, ProjectRole::MEMBER], true);
    }

    /**
     * Determine whether user can edit the comment.
     * Only the original author can edit their comment.
     */
    public function update(User $user, TaskComment $comment): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return (int) $comment->user_id === (int) $user->id;
    }

    /**
     * Determine whether user can delete the comment.
     * Author OR project OWNER/MANAGER can delete (moderation).
     */
    public function delete(User $user, TaskComment $comment): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ((int) $comment->user_id === (int) $user->id) {
            return true;
        }

        return $comment->task->project->isOwnerOrManager($user);
    }
}

