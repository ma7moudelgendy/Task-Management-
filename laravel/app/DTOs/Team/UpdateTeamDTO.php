<?php

namespace App\DTOs\Team;

use App\Http\Requests\Api\Team\UpdateTeamRequest;

readonly class UpdateTeamDTO
{
    public function __construct(
        public ?string $name = null,
        public ?string $slug = null,
    ) {}

    public static function fromRequest(UpdateTeamRequest $request): static
    {
        return new static(
            name: $request->has('name') ? (string) $request->validated('name') : null,
            slug: $request->has('slug') ? (string) $request->validated('slug') : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'slug' => $this->slug,
        ], fn ($val) => $val !== null);
    }
}

