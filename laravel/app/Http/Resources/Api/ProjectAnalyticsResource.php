<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectAnalyticsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'project_id' => $this->resource['project_id'],
            'project_name' => $this->resource['project_name'],
            'total_tasks' => $this->resource['total_tasks'],
            'completed_tasks' => $this->resource['completed_tasks'],
            'cancelled_tasks' => $this->resource['cancelled_tasks'],
            'active_tasks' => $this->resource['active_tasks'],
            'completion_percentage' => $this->resource['completion_percentage'],
            'overdue_count' => $this->resource['overdue_count'],
            'status_distribution' => $this->resource['status_distribution'],
            'priority_distribution' => $this->resource['priority_distribution'],
            'assignee_workload' => $this->resource['assignee_workload'],
        ];
    }
}

