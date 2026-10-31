<?php

namespace App\DTOs\Project;

use App\Enums\ProjectRole;
use App\Http\Requests\Api\Project\UpdateProjectMemberRoleRequest;

readonly class UpdateProjectMemberRoleDTO
{
    public function __construct(
        public ProjectRole $role,
    ) {}

    public static function fromRequest(UpdateProjectMemberRoleRequest $request): static
    {
        return new static(
            role: ProjectRole::from((string) $request->validated('role')),
        );
    }

    public function toArray(): array
    {
        return [
            'role' => $this->role->value,
        ];
    }
}

