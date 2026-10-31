<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\Attachment\UploadAttachmentRequest;
use App\Http\Resources\Api\AttachmentResource;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Services\Collaboration\AttachmentService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends ApiController
{
    public function __construct(
        protected AttachmentService $attachmentService,
    ) {}

    /**
     * Display a listing of attachments on a task.
     */
    public function index(Request $request, Task $task): JsonResponse
    {
        return $this->handleApi(function () use ($request, $task) {
            $this->authorize('view', $task);

            $perPage = (int) $request->input('per_page', 15);
            $attachments = $this->attachmentService->getTaskAttachments($task, $perPage);

            return $this->jsonResponse(
                data: AttachmentResource::collection($attachments)->response()->getData(true),
                msg: 'Attachments retrieved successfully.'
            );
        });
    }

    /**
     * Upload an attachment to a task.
     */
    public function upload(UploadAttachmentRequest $request, Task $task): JsonResponse
    {
        return $this->handleApi(function () use ($request, $task) {
            $this->authorize('upload', [TaskAttachment::class, $task]);

            $attachment = $this->attachmentService->uploadAttachment(
                $task,
                $request->user(),
                $request->file('file')
            );

            return $this->jsonResponse(
                data: AttachmentResource::make($attachment),
                msg: 'Attachment uploaded successfully.',
                code: 201
            );
        });
    }

    /**
     * Stream an attachment file for download.
     */
    public function download(Task $task, TaskAttachment $attachment): StreamedResponse
    {
        if ((int) $attachment->task_id !== (int) $task->id) {
            throw new ModelNotFoundException('Attachment not found for this task.');
        }

        $this->authorize('download', $attachment);

        if (! Storage::disk($attachment->disk)->exists($attachment->path)) {
            abort(404, 'File not found on storage.');
        }

        return Storage::disk($attachment->disk)->download(
            $attachment->path,
            $attachment->original_name,
            ['Content-Type' => $attachment->mime_type]
        );
    }

    /**
     * Delete an attachment.
     */
    public function destroy(Task $task, TaskAttachment $attachment): JsonResponse
    {
        return $this->handleApi(function () use ($task, $attachment) {
            if ((int) $attachment->task_id !== (int) $task->id) {
                throw new ModelNotFoundException('Attachment not found for this task.');
            }

            $this->authorize('delete', $attachment);

            $this->attachmentService->deleteAttachment($attachment, request()->user());

            return $this->jsonResponse(
                msg: 'Attachment deleted successfully.'
            );
        });
    }
}

