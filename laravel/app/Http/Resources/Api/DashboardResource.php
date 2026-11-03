<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'stats' => $this->resource['stats'],
            'overdue_tasks' => collect($this->resource['overdue_tasks'])->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'priority' => $task->priority->value,
                    'status' => $task->status->value,
                    'due_at' => $task->due_at?->toIso8601String(),
                    'project' => $task->project ? [
                        'id' => $task->project->id,
                        'name' => $task->project->name,
                    ] : null,
                ];
            })->all(),
            'upcoming_tasks' => collect($this->resource['upcoming_tasks'])->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'priority' => $task->priority->value,
                    'status' => $task->status->value,
                    'due_at' => $task->due_at?->toIso8601String(),
                    'project' => $task->project ? [
                        'id' => $task->project->id,
                        'name' => $task->project->name,
                    ] : null,
                ];
            })->all(),
            'recent_activity' => collect($this->resource['recent_activity'])->map(function ($log) {
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'actor' => $log->actor ? [
                        'id' => $log->actor->id,
                        'name' => $log->actor->name,
                        'email' => $log->actor->email,
                    ] : null,
                    'project' => $log->project ? [
                        'id' => $log->project->id,
                        'name' => $log->project->name,
                    ] : null,
                    'metadata' => $log->metadata,
                    'created_at' => $log->created_at?->toIso8601String(),
                ];
            })->all(),
        ];
    }
}

