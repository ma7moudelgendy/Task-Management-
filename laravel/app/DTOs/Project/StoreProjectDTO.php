<?php

namespace App\DTOs\Project;

use App\Enums\ProjectStatus;
use App\Http\Requests\Api\Project\StoreProjectRequest;

readonly class StoreProjectDTO
{
    public function __construct(
        public int $teamId,
        public string $name,
        public ?string $description = null,
        public ProjectStatus $status = ProjectStatus::ACTIVE,
        public ?string $startDate = null,
        public ?string $dueDate = null,
    ) {}

    public static function fromRequest(StoreProjectRequest $request): static
    {
        $statusValue = $request->validated('status');
        $status = $statusValue ? ProjectStatus::from((string) $statusValue) : ProjectStatus::ACTIVE;

        return new static(
            teamId: (int) $request->validated('team_id'),
            name: (string) $request->validated('name'),
            description: $request->validated('description'),
            status: $status,
            startDate: $request->validated('start_date'),
            dueDate: $request->validated('due_date'),
        );
    }

    public function toArray(): array
    {
        return [
            'team_id' => $this->teamId,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status->value,
            'start_date' => $this->startDate,
            'due_date' => $this->dueDate,
        ];
    }
}

