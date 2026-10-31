<?php

namespace App\DTOs\Task;

use App\Http\Requests\Api\Task\SyncAssigneesRequest;

readonly class SyncAssigneesDTO
{
    /**
     * @param  array<int>  $assigneeIds
     */
    public function __construct(
        public array $assigneeIds,
    ) {}

    public static function fromRequest(SyncAssigneesRequest $request): static
    {
        return new static(
            assigneeIds: array_map('intval', (array) $request->validated('assignee_ids', [])),
        );
    }
}

