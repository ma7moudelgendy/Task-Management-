<?php

namespace App\DTOs\Auth;

use App\Http\Requests\Api\Auth\LoginRequest;

readonly class LoginDTO
{
    public function __construct(
        public string $email,
        public string $password,
        public ?string $deviceName = null,
    ) {}

    public static function fromRequest(LoginRequest $request): static
    {
        return new static(
            email: (string) $request->validated('email'),
            password: (string) $request->validated('password'),
            deviceName: $request->validated('device_name') ? (string) $request->validated('device_name') : null,
        );
    }
}
