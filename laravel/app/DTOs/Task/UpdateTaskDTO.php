<?php

namespace App\DTOs\Task;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Http\Requests\Api\Task\UpdateTaskRequest;

readonly class UpdateTaskDTO
{
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public ?TaskStatus $status = null,
        public ?TaskPriority $priority = null,
        public ?int $parentTaskId = null,
        public ?string $startAt = null,
        public ?string $dueAt = null,
        public ?int $position = null,
        public bool $hasParentTaskId = false,
    ) {}

    public static function fromRequest(UpdateTaskRequest $request): static
    {
        $statusValue = $request->validated('status');
        $priorityValue = $request->validated('priority');

        return new static(
            title: $request->has('title') ? (string) $request->validated('title') : null,
            description: $request->has('description') ? $request->validated('description') : null,
            status: $statusValue ? TaskStatus::from((string) $statusValue) : null,
            priority: $priorityValue ? TaskPriority::from((string) $priorityValue) : null,
            parentTaskId: $request->has('parent_task_id') && $request->validated('parent_task_id')
                ? (int) $request->validated('parent_task_id')
                : null,
            startAt: $request->has('start_at') ? $request->validated('start_at') : null,
            dueAt: $request->has('due_at') ? $request->validated('due_at') : null,
            position: $request->has('position') ? (int) $request->validated('position') : null,
            hasParentTaskId: $request->has('parent_task_id'),
        );
    }

    public function toArray(): array
    {
        $data = [];

        if ($this->title !== null) {
            $data['title'] = $this->title;
        }

        if ($this->description !== null) {
            $data['description'] = $this->description;
        }

        if ($this->status !== null) {
            $data['status'] = $this->status->value;
        }

        if ($this->priority !== null) {
            $data['priority'] = $this->priority->value;
        }

        if ($this->hasParentTaskId) {
            $data['parent_task_id'] = $this->parentTaskId;
        }

        if ($this->startAt !== null) {
            $data['start_at'] = $this->startAt;
        }

        if ($this->dueAt !== null) {
            $data['due_at'] = $this->dueAt;
        }

        if ($this->position !== null) {
            $data['position'] = $this->position;
        }

        return $data;
    }
}

