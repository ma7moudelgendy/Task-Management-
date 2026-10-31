<?php

namespace App\Http\Resources\Api;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Project
 */
class ProjectResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $status = $this->status instanceof ProjectStatus ? $this->status->value : (string) $this->status;

        return [
            'id' => $this->id,
            'team_id' => $this->team_id,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $status,
            'start_date' => $this->start_date?->toDateString(),
            'due_date' => $this->due_date?->toDateString(),
            'created_by' => $this->created_by,
            'member_count' => $this->members_count ?? $this->members()->count(),
            'tasks_count' => $this->tasks_count ?? $this->tasks()->count(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'team' => TeamResource::make($this->whenLoaded('team')),
            'members' => ProjectMemberResource::collection($this->whenLoaded('members')),
            'helpers' => [
                'can_update' => $user ? ($user->isAdmin() || $this->isOwnerOrManager($user)) : false,
                'can_delete' => $user ? ($user->isAdmin() || $this->isOwner($user)) : false,
                'can_manage_members' => $user ? ($user->isAdmin() || $this->isOwnerOrManager($user)) : false,
                'can_change_role' => $user ? ($user->isAdmin() || $this->isOwner($user)) : false,
            ],
        ];
    }
}

