<?php

namespace App\DTOs\Team;

use App\Enums\TeamRole;
use App\Http\Requests\Api\Team\AddTeamMemberRequest;

readonly class AddTeamMemberDTO
{
    public function __construct(
        public string $email,
        public TeamRole $role,
    ) {}

    public static function fromRequest(AddTeamMemberRequest $request): static
    {
        $roleValue = (string) $request->validated('role');

        return new static(
            email: (string) $request->validated('email'),
            role: TeamRole::from($roleValue),
        );
    }

    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'role' => $this->role->value,
        ];
    }
}

