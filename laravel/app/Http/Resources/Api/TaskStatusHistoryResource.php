<?php

namespace App\Http\Resources\Api;

use App\Enums\TaskStatus;
use App\Models\TaskStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TaskStatusHistory
 */
class TaskStatusHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $oldStatus = $this->old_status instanceof TaskStatus ? $this->old_status->value : (string) $this->old_status;
        $newStatus = $this->new_status instanceof TaskStatus ? $this->new_status->value : (string) $this->new_status;

        return [
            'id' => $this->id,
            'task_id' => $this->task_id,
            'changed_by' => $this->changed_by,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'reason' => $this->reason,
            'created_at' => $this->created_at?->toIso8601String(),
            'changer' => UserResource::make($this->whenLoaded('changer')),
        ];
    }
}

