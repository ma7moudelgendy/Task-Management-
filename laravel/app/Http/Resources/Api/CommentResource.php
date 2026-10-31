<?php

namespace App\Http\Resources\Api;

use App\Models\TaskComment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TaskComment
 */
class CommentResource extends JsonResource
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
            'task_id' => $this->task_id,
            'user_id' => $this->user_id,
            'body' => $this->body,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'user' => UserResource::make($this->whenLoaded('user')),
            'helpers' => [
                'can_update' => $user ? ($user->isAdmin() || (int) $this->user_id === (int) $user->id) : false,
                'can_delete' => $user ? ($user->isAdmin() || (int) $this->user_id === (int) $user->id || $this->task?->project?->isOwnerOrManager($user)) : false,
            ],
        ];
    }
}

