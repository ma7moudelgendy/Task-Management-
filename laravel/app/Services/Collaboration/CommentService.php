<?php

namespace App\Services\Collaboration;

use App\DTOs\Comment\StoreCommentDTO;
use App\DTOs\Comment\UpdateCommentDTO;
use App\Models\ActivityLog;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use App\Repositories\Contracts\CommentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CommentService
{
    public function __construct(
        protected CommentRepositoryInterface $commentRepository,
    ) {}

    /**
     * Get paginated comments for a task.
     */
    public function getTaskComments(Task $task, int $perPage = 15): LengthAwarePaginator
    {
        return $this->commentRepository->paginateForTask($task, $perPage);
    }

    /**
     * Create a comment on a task.
     */
    public function createComment(Task $task, User $author, StoreCommentDTO $dto): TaskComment
    {
        return DB::transaction(function () use ($task, $author, $dto) {
            $comment = $this->commentRepository->create([
                'task_id' => $task->id,
                'user_id' => $author->id,
                'body' => $dto->body,
            ]);

            ActivityLog::create([
                'actor_id' => $author->id,
                'team_id' => $task->project?->team_id,
                'project_id' => $task->project_id,
                'subject_type' => Task::class,
                'subject_id' => $task->id,
                'action' => 'added_comment',
                'metadata' => [
                    'comment_id' => $comment->id,
                ],
                'created_at' => now(),
            ]);

            return $comment->load('user:id,name,email');
        });
    }

    /**
     * Update an existing comment.
     */
    public function updateComment(TaskComment $comment, UpdateCommentDTO $dto): TaskComment
    {
        $this->commentRepository->update($comment, [
            'body' => $dto->body,
        ]);

        return $comment->fresh('user:id,name,email');
    }

    /**
     * Delete a comment.
     */
    public function deleteComment(TaskComment $comment, User $actor): void
    {
        DB::transaction(function () use ($comment, $actor) {
            $task = $comment->task;

            $this->commentRepository->delete($comment);

            ActivityLog::create([
                'actor_id' => $actor->id,
                'team_id' => $task?->project?->team_id,
                'project_id' => $task?->project_id,
                'subject_type' => Task::class,
                'subject_id' => $comment->task_id,
                'action' => 'deleted_comment',
                'metadata' => [
                    'comment_id' => $comment->id,
                ],
                'created_at' => now(),
            ]);
        });
    }
}

