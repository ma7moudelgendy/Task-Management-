<?php

namespace App\Services;

use App\Models\Project;
use App\Repositories\Contracts\AnalyticsRepositoryInterface;

class ProjectAnalyticsService
{
    public function __construct(
        protected AnalyticsRepositoryInterface $analyticsRepository,
    ) {}

    /**
     * Get aggregated completion and workload analytics for a project.
     *
     * @return array<string, mixed>
     */
    public function getProjectAnalytics(Project $project): array
    {
        $totals = $this->analyticsRepository->getProjectTaskTotals($project);
        $statusDistribution = $this->analyticsRepository->getProjectStatusDistribution($project);
        $priorityDistribution = $this->analyticsRepository->getProjectPriorityDistribution($project);
        $workload = $this->analyticsRepository->getProjectAssigneeWorkload($project);

        return [
            'project_id' => (int) $project->id,
            'project_name' => (string) $project->name,
            'total_tasks' => (int) $totals['total_tasks'],
            'completed_tasks' => (int) $totals['completed_tasks'],
            'cancelled_tasks' => (int) $totals['cancelled_tasks'],
            'active_tasks' => (int) $totals['active_tasks'],
            'completion_percentage' => (float) $totals['completion_percentage'],
            'overdue_count' => (int) $totals['overdue_count'],
            'status_distribution' => $statusDistribution,
            'priority_distribution' => $priorityDistribution,
            'assignee_workload' => $workload,
        ];
    }
}

