<?php

namespace App\Repositories\Eloquent;

use App\Models\Task;
use App\Models\TaskStatusHistory;
use App\Repositories\Contracts\TaskStatusHistoryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TaskStatusHistoryRepository implements TaskStatusHistoryRepositoryInterface
{
    public function create(array $data): TaskStatusHistory
    {
        return TaskStatusHistory::create($data);
    }

    public function paginateForTask(Task $task, int $perPage = 15): LengthAwarePaginator
    {
        return $task->statusHistories()
            ->with('changer:id,name,email')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}

