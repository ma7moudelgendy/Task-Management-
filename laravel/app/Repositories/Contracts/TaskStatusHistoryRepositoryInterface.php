<?php

namespace App\Repositories\Contracts;

use App\Models\Task;
use App\Models\TaskStatusHistory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TaskStatusHistoryRepositoryInterface
{
    public function create(array $data): TaskStatusHistory;

    public function paginateForTask(Task $task, int $perPage = 15): LengthAwarePaginator;
}

