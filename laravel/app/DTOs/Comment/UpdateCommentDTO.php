<?php

namespace App\DTOs\Comment;

use App\Http\Requests\Api\Comment\UpdateCommentRequest;

readonly class UpdateCommentDTO
{
    public function __construct(
        public string $body,
    ) {}

    public static function fromRequest(UpdateCommentRequest $request): static
    {
        return new static(
            body: (string) $request->validated('body'),
        );
    }
}

