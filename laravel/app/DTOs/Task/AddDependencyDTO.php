<?php

namespace App\DTOs\Task;

use App\Http\Requests\Api\Task\AddDependencyRequest;

readonly class AddDependencyDTO
{
    public function __construct(
        public int $dependsOnTaskId,
    ) {}

    public static function fromRequest(AddDependencyRequest $request): static
    {
        return new static(
            dependsOnTaskId: (int) $request->validated('depends_on_task_id'),
        );
    }
}

