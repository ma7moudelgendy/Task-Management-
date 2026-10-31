<?php

namespace App\Services\Collaboration;

use App\Models\ActivityLog;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\User;
use App\Repositories\Contracts\AttachmentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AttachmentService
{
    public function __construct(
        protected AttachmentRepositoryInterface $attachmentRepository,
    ) {}

    /**
     * Get paginated attachments for a task.
     */
    public function getTaskAttachments(Task $task, int $perPage = 15): LengthAwarePaginator
    {
        return $this->attachmentRepository->paginateForTask($task, $perPage);
    }

    /**
     * Upload and store a new file attachment on the private storage disk.
     */
    public function uploadAttachment(Task $task, User $uploader, UploadedFile $file): TaskAttachment
    {
        return DB::transaction(function () use ($task, $uploader, $file) {
            $storedPath = $file->store('attachments/'.$task->id, 'local');

            $attachment = $this->attachmentRepository->create([
                'task_id' => $task->id,
                'uploaded_by' => $uploader->id,
                'disk' => 'local',
                'path' => $storedPath,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType() ?: ($file->getMimeType() ?: 'application/octet-stream'),
                'size' => $file->getSize(),
            ]);

            ActivityLog::create([
                'actor_id' => $uploader->id,
                'team_id' => $task->project?->team_id,
                'project_id' => $task->project_id,
                'subject_type' => Task::class,
                'subject_id' => $task->id,
                'action' => 'uploaded_attachment',
                'metadata' => [
                    'attachment_id' => $attachment->id,
                    'original_name' => $attachment->original_name,
                    'size' => $attachment->size,
                ],
                'created_at' => now(),
            ]);

            return $attachment->load('uploader:id,name,email');
        });
    }

    /**
     * Delete an attachment from storage and soft delete from database.
     */
    public function deleteAttachment(TaskAttachment $attachment, User $actor): void
    {
        DB::transaction(function () use ($attachment, $actor) {
            $task = $attachment->task;

            // Remove physical file from private disk
            if (Storage::disk($attachment->disk)->exists($attachment->path)) {
                Storage::disk($attachment->disk)->delete($attachment->path);
            }

            $this->attachmentRepository->delete($attachment);

            ActivityLog::create([
                'actor_id' => $actor->id,
                'team_id' => $task?->project?->team_id,
                'project_id' => $task?->project_id,
                'subject_type' => Task::class,
                'subject_id' => $attachment->task_id,
                'action' => 'deleted_attachment',
                'metadata' => [
                    'attachment_id' => $attachment->id,
                    'original_name' => $attachment->original_name,
                ],
                'created_at' => now(),
            ]);
        });
    }
}

