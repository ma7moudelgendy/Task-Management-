<?php

namespace App\DTOs\Auth;

use App\Http\Requests\Api\Auth\UpdateProfileRequest;

readonly class UpdateProfileDTO
{
    public function __construct(
        public ?string $name = null,
        public ?string $email = null,
        public ?string $password = null,
        public ?string $currentPassword = null,
    ) {}

    public static function fromRequest(UpdateProfileRequest $request): static
    {
        return new static(
            name: $request->has('name') ? (string) $request->validated('name') : null,
            email: $request->has('email') ? (string) $request->validated('email') : null,
            password: $request->has('password') ? (string) $request->validated('password') : null,
            currentPassword: $request->has('current_password') ? (string) $request->validated('current_password') : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
        ], fn ($val) => $val !== null);
    }
}
