<?php

namespace App\Http\Resources\Api;

use App\Models\TaskAttachment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TaskAttachment
 */
class AttachmentResource extends JsonResource
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
            'uploaded_by' => $this->uploaded_by,
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'created_at' => $this->created_at?->toIso8601String(),
            'uploader' => UserResource::make($this->whenLoaded('uploader')),
            'helpers' => [
                'can_delete' => $user ? ($user->isAdmin() || (int) $this->uploaded_by === (int) $user->id || $this->task?->project?->isOwnerOrManager($user)) : false,
            ],
        ];
    }
}

