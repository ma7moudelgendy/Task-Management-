<?php

namespace App\Repositories\Contracts;

use App\Models\Task;
use App\Models\TaskAttachment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AttachmentRepositoryInterface
{
    public function create(array $data): TaskAttachment;

    public function findById(int $id): ?TaskAttachment;

    public function delete(TaskAttachment $attachment): bool;

    public function paginateForTask(Task $task, int $perPage = 15): LengthAwarePaginator;
}

