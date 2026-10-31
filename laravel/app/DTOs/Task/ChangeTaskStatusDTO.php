<?php

namespace App\DTOs\Task;

use App\Enums\TaskStatus;
use App\Http\Requests\Api\Task\ChangeTaskStatusRequest;

readonly class ChangeTaskStatusDTO
{
    public function __construct(
        public TaskStatus $status,
        public ?string $reason = null,
    ) {}

    public static function fromRequest(ChangeTaskStatusRequest $request): static
    {
        return new static(
            status: TaskStatus::from((string) $request->validated('status')),
            reason: $request->validated('reason'),
        );
    }
}

