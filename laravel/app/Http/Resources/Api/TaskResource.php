<?php

namespace App\Http\Resources\Api;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Task
 */
class TaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $status = $this->status instanceof TaskStatus ? $this->status->value : (string) $this->status;
        $priority = $this->priority instanceof TaskPriority ? $this->priority->value : (string) $this->priority;

        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'parent_task_id' => $this->parent_task_id,
            'created_by' => $this->created_by,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $status,
            'priority' => $priority,
            'start_at' => $this->start_at?->toIso8601String(),
            'due_at' => $this->due_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'position' => $this->position,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'project' => ProjectResource::make($this->whenLoaded('project')),
            'creator' => UserResource::make($this->whenLoaded('creator')),
            'assignees' => UserResource::collection($this->whenLoaded('assignees')),
            'subtasks' => TaskResource::collection($this->whenLoaded('subtasks')),
            'helpers' => [
                'can_update' => $user ? ($user->isAdmin() || $this->project?->isOwnerOrManager($user) || $this->isCreator($user) || $this->isAssignee($user)) : false,
                'can_delete' => $user ? ($user->isAdmin() || $this->project?->isOwnerOrManager($user) || $this->isCreator($user)) : false,
                'can_assign' => $user ? ($user->isAdmin() || $this->project?->isOwnerOrManager($user)) : false,
            ],
        ];
    }
}

