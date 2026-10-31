<?php

namespace App\DTOs\Project;

use App\Enums\ProjectStatus;
use App\Http\Requests\Api\Project\UpdateProjectRequest;

readonly class UpdateProjectDTO
{
    public function __construct(
        public ?string $name = null,
        public ?string $description = null,
        public ?ProjectStatus $status = null,
        public ?string $startDate = null,
        public ?string $dueDate = null,
    ) {}

    public static function fromRequest(UpdateProjectRequest $request): static
    {
        $status = $request->has('status') && $request->validated('status')
            ? ProjectStatus::from((string) $request->validated('status'))
            : null;

        return new static(
            name: $request->has('name') ? (string) $request->validated('name') : null,
            description: $request->has('description') ? $request->validated('description') : null,
            status: $status,
            startDate: $request->has('start_date') ? $request->validated('start_date') : null,
            dueDate: $request->has('due_date') ? $request->validated('due_date') : null,
        );
    }

    public function toArray(): array
    {
        $data = [];
        if ($this->name !== null) {
            $data['name'] = $this->name;
        }
        if ($this->description !== null) {
            $data['description'] = $this->description;
        }
        if ($this->status !== null) {
            $data['status'] = $this->status->value;
        }
        if ($this->startDate !== null) {
            $data['start_date'] = $this->startDate;
        }
        if ($this->dueDate !== null) {
            $data['due_date'] = $this->dueDate;
        }

        return $data;
    }
}

