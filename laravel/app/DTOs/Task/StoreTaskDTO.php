<?php

namespace App\DTOs\Task;

use App\Enums\TaskPriority;
use App\Http\Requests\Api\Task\StoreTaskRequest;

readonly class StoreTaskDTO
{
    /**
     * @param  array<int>  $assigneeIds
     */
    public function __construct(
        public string $title,
        public ?string $description = null,
        public TaskPriority $priority = TaskPriority::MEDIUM,
        public ?int $parentTaskId = null,
        public ?string $startAt = null,
        public ?string $dueAt = null,
        public int $position = 0,
        public array $assigneeIds = [],
    ) {}

    public static function fromRequest(StoreTaskRequest $request): static
    {
        $priorityValue = $request->validated('priority');
        $priority = $priorityValue ? TaskPriority::from((string) $priorityValue) : TaskPriority::MEDIUM;

        return new static(
            title: (string) $request->validated('title'),
            description: $request->validated('description'),
            priority: $priority,
            parentTaskId: $request->validated('parent_task_id') ? (int) $request->validated('parent_task_id') : null,
            startAt: $request->validated('start_at'),
            dueAt: $request->validated('due_at'),
            position: (int) ($request->validated('position') ?? 0),
            assigneeIds: array_map('intval', $request->validated('assignee_ids', [])),
        );
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority->value,
            'parent_task_id' => $this->parentTaskId,
            'start_at' => $this->startAt,
            'due_at' => $this->dueAt,
            'position' => $this->position,
        ];
    }
}

