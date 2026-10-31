<?php

namespace App\Http\Resources\Api;

use App\Enums\TeamRole;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamMemberResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $role = $this->pivot?->role;
        $roleValue = $role instanceof TeamRole ? $role->value : (string) $role;

        return [
            'id' => $this->id,
            'user_id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $roleValue,
            'joined_at' => $this->pivot?->joined_at instanceof \DateTimeInterface
                ? $this->pivot->joined_at->toIso8601String()
                : (is_string($this->pivot?->joined_at) ? $this->pivot->joined_at : null),
        ];
    }
}

