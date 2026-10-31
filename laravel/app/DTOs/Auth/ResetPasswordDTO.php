<?php

namespace App\DTOs\Auth;

use App\Http\Requests\Api\Auth\ResetPasswordRequest;

readonly class ResetPasswordDTO
{
    public function __construct(
        public string $email,
        public string $token,
        public string $password,
    ) {}

    public static function fromRequest(ResetPasswordRequest $request): static
    {
        return new static(
            email: (string) $request->validated('email'),
            token: (string) $request->validated('token'),
            password: (string) $request->validated('password'),
        );
    }
}

