<?php

namespace App\DTOs\Task;

use App\Http\Requests\Api\Task\FilterTaskRequest;

readonly class FilterTaskDTO
{
    public function __construct(
        public ?string $status = null,
        public ?string $priority = null,
        public ?int $assigneeId = null,
        public ?string $dueBefore = null,
        public ?string $dueAfter = null,
        public string $sortBy = 'created_at',
        public string $sortDir = 'desc',
        public int $perPage = 15,
    ) {}

    public static function fromRequest(FilterTaskRequest $request): static
    {
        return new static(
            status: $request->validated('status'),
            priority: $request->validated('priority'),
            assigneeId: $request->validated('assignee_id') ? (int) $request->validated('assignee_id') : null,
            dueBefore: $request->validated('due_before'),
            dueAfter: $request->validated('due_after'),
            sortBy: (string) ($request->validated('sort_by') ?? 'created_at'),
            sortDir: (string) ($request->validated('sort_dir') ?? 'desc'),
            perPage: min(100, max(1, (int) ($request->validated('per_page') ?? 15))),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'status' => $this->status,
            'priority' => $this->priority,
            'assignee_id' => $this->assigneeId,
            'due_before' => $this->dueBefore,
            'due_after' => $this->dueAfter,
            'sort_by' => $this->sortBy,
            'sort_dir' => $this->sortDir,
        ], fn ($val) => $val !== null);
    }
}

