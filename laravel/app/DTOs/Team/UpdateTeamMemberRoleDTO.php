<?php

namespace App\DTOs\Team;

use App\Enums\TeamRole;
use App\Http\Requests\Api\Team\UpdateTeamMemberRoleRequest;

readonly class UpdateTeamMemberRoleDTO
{
    public function __construct(
        public TeamRole $role,
    ) {}

    public static function fromRequest(UpdateTeamMemberRoleRequest $request): static
    {
        $roleValue = (string) $request->validated('role');

        return new static(
            role: TeamRole::from($roleValue),
        );
    }

    public function toArray(): array
    {
        return [
            'role' => $this->role->value,
        ];
    }
}

