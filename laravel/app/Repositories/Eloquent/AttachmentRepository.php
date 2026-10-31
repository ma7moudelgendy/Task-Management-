<?php

namespace App\Repositories\Eloquent;

use App\Models\Task;
use App\Models\TaskAttachment;
use App\Repositories\Contracts\AttachmentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AttachmentRepository implements AttachmentRepositoryInterface
{
    public function create(array $data): TaskAttachment
    {
        return TaskAttachment::create($data);
    }

    public function findById(int $id): ?TaskAttachment
    {
        return TaskAttachment::with('uploader:id,name,email')->find($id);
    }

    public function delete(TaskAttachment $attachment): bool
    {
        return $attachment->delete();
    }

    public function paginateForTask(Task $task, int $perPage = 15): LengthAwarePaginator
    {
        return $task->attachments()
            ->with('uploader:id,name,email')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}

