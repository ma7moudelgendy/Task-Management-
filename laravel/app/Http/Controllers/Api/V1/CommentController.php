<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Comment\StoreCommentDTO;
use App\DTOs\Comment\UpdateCommentDTO;
use App\Http\Requests\Api\Comment\StoreCommentRequest;
use App\Http\Requests\Api\Comment\UpdateCommentRequest;
use App\Http\Resources\Api\CommentResource;
use App\Models\Task;
use App\Models\TaskComment;
use App\Services\Collaboration\CommentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends ApiController
{
    public function __construct(
        protected CommentService $commentService,
    ) {}

    /**
     * Display a listing of comments on a task.
     */
    public function index(Request $request, Task $task): JsonResponse
    {
        return $this->handleApi(function () use ($request, $task) {
            $this->authorize('view', $task);

            $perPage = (int) $request->input('per_page', 15);
            $comments = $this->commentService->getTaskComments($task, $perPage);

            return $this->jsonResponse(
                data: CommentResource::collection($comments)->response()->getData(true),
                msg: 'Comments retrieved successfully.'
            );
        });
    }

    /**
     * Store a newly created comment on a task.
     */
    public function store(StoreCommentRequest $request, Task $task): JsonResponse
    {
        return $this->handleApi(function () use ($request, $task) {
            $this->authorize('create', [TaskComment::class, $task]);

            $dto = StoreCommentDTO::fromRequest($request);
            $comment = $this->commentService->createComment($task, $request->user(), $dto);

            return $this->jsonResponse(
                data: CommentResource::make($comment),
                msg: 'Comment created successfully.',
                code: 201
            );
        });
    }

    /**
     * Update the specified comment.
     */
    public function update(UpdateCommentRequest $request, TaskComment $comment): JsonResponse
    {
        return $this->handleApi(function () use ($request, $comment) {
            $this->authorize('update', $comment);

            $dto = UpdateCommentDTO::fromRequest($request);
            $updatedComment = $this->commentService->updateComment($comment, $dto);

            return $this->jsonResponse(
                data: CommentResource::make($updatedComment),
                msg: 'Comment updated successfully.'
            );
        });
    }

    /**
     * Delete the specified comment.
     */
    public function destroy(TaskComment $comment): JsonResponse
    {
        return $this->handleApi(function () use ($comment) {
            $this->authorize('delete', $comment);

            $this->commentService->deleteComment($comment, request()->user());

            return $this->jsonResponse(
                msg: 'Comment deleted successfully.'
            );
        });
    }
}

