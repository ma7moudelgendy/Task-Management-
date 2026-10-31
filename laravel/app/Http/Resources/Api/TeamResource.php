<?php

namespace App\Http\Resources\Api;

use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Team
 */
class TeamResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'created_by' => $this->created_by,
            'member_count' => $this->members_count ?? $this->members()->count(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'members' => TeamMemberResource::collection($this->whenLoaded('members')),
            'helpers' => [
                'can_update' => $user ? ($user->isAdmin() || $this->isOwnerOrManager($user)) : false,
                'can_delete' => $user ? ($user->isAdmin() || $this->isOwner($user)) : false,
                'can_manage_members' => $user ? ($user->isAdmin() || $this->isOwnerOrManager($user)) : false,
                'can_change_role' => $user ? ($user->isAdmin() || $this->isOwner($user)) : false,
            ],
        ];
    }
}

