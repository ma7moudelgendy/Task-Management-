<?php

namespace App\Repositories\Contracts;

use App\Models\Task;
use App\Models\TaskComment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CommentRepositoryInterface
{
    public function create(array $data): TaskComment;

    public function findById(int $id): ?TaskComment;

    public function update(TaskComment $comment, array $data): bool;

    public function delete(TaskComment $comment): bool;

    public function paginateForTask(Task $task, int $perPage = 15): LengthAwarePaginator;
}

