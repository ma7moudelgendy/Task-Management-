<?php

namespace App\DTOs\Auth;

use App\Http\Requests\Api\Auth\ForgotPasswordRequest;

readonly class ForgotPasswordDTO
{
    public function __construct(
        public string $email,
    ) {}

    public static function fromRequest(ForgotPasswordRequest $request): static
    {
        return new static(
            email: (string) $request->validated('email'),
        );
    }
}
