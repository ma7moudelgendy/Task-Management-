<?php

namespace App\DTOs\Project;

use App\Enums\ProjectRole;
use App\Http\Requests\Api\Project\AddProjectMemberRequest;

readonly class AddProjectMemberDTO
{
    public function __construct(
        public int $userId,
        public ProjectRole $role,
    ) {}

    public static function fromRequest(AddProjectMemberRequest $request): static
    {
        return new static(
            userId: (int) $request->validated('user_id'),
            role: ProjectRole::from((string) $request->validated('role')),
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'role' => $this->role->value,
        ];
    }
}

