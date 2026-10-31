<?php

namespace App\DTOs\Comment;

use App\Http\Requests\Api\Comment\StoreCommentRequest;

readonly class StoreCommentDTO
{
    public function __construct(
        public string $body,
    ) {}

    public static function fromRequest(StoreCommentRequest $request): static
    {
        return new static(
            body: (string) $request->validated('body'),
        );
    }
}

