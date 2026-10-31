<?php

namespace App\Policies;

use App\Enums\ProjectRole;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\User;

class AttachmentPolicy
{
    /**
     * Determine whether user can view attachments on the task.
     */
    public function view(User $user, Task $task): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $task->project->isMember($user);
    }

    /**
     * Determine whether user can upload an attachment to the task.
     * Caller must hold OWNER, MANAGER, or MEMBER in the project (VIEWER denied).
     */
    public function upload(User $user, Task $task): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $role = $task->project->userRole($user);

        return in_array($role, [ProjectRole::OWNER, ProjectRole::MANAGER, ProjectRole::MEMBER], true);
    }

    /**
     * Determine whether user can download the attachment.
     * Caller must be a member of the project or system ADMIN (IDOR prevention).
     */
    public function download(User $user, TaskAttachment $attachment): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $attachment->task->project->isMember($user);
    }

    /**
     * Determine whether user can delete the attachment.
     * Uploader OR project OWNER/MANAGER can delete.
     */
    public function delete(User $user, TaskAttachment $attachment): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ((int) $attachment->uploaded_by === (int) $user->id) {
            return true;
        }

        return $attachment->task->project->isOwnerOrManager($user);
    }
}

