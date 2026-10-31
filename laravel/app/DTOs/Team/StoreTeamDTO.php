<?php

namespace App\DTOs\Team;

use App\Http\Requests\Api\Team\StoreTeamRequest;

readonly class StoreTeamDTO
{
    public function __construct(
        public string $name,
        public ?string $slug = null,
    ) {}

    public static function fromRequest(StoreTeamRequest $request): static
    {
        return new static(
            name: (string) $request->validated('name'),
            slug: $request->validated('slug') ? (string) $request->validated('slug') : null,
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

