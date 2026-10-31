<?php

namespace App\Repositories\Eloquent;

use App\Models\Task;
use App\Models\TaskComment;
use App\Repositories\Contracts\CommentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CommentRepository implements CommentRepositoryInterface
{
    public function create(array $data): TaskComment
    {
        return TaskComment::create($data);
    }

    public function findById(int $id): ?TaskComment
    {
        return TaskComment::with('user:id,name,email')->find($id);
    }

    public function update(TaskComment $comment, array $data): bool
    {
        return $comment->update($data);
    }

    public function delete(TaskComment $comment): bool
    {
        return $comment->delete();
    }

    public function paginateForTask(Task $task, int $perPage = 15): LengthAwarePaginator
    {
        return $task->comments()
            ->with('user:id,name,email')
            ->orderBy('created_at', 'asc')
            ->paginate($perPage);
    }
}

