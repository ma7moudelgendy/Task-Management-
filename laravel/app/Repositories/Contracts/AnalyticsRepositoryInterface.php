<?php

namespace App\Repositories\Contracts;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface AnalyticsRepositoryInterface
{
    /**
     * Get aggregate task statistics for a specific user.
     *
     * @return array<string, mixed>
     */
    public function getUserTaskStats(User $user): array;

    /**
     * Get overdue tasks assigned to the user.
     *
     * @return Collection<int, \App\Models\Task>
     */
    public function getUserOverdueTasks(User $user, int $limit = 10): Collection;

    /**
     * Get upcoming tasks assigned to the user (due within 7 days).
     *
     * @return Collection<int, \App\Models\Task>
     */
    public function getUserUpcomingTasks(User $user, int $limit = 10): Collection;

    /**
     * Get recent activity feed across projects enrolled by the user.
     *
     * @return Collection<int, \App\Models\ActivityLog>
     */
    public function getUserRecentActivity(User $user, int $limit = 10): Collection;

    /**
     * Get project overall task totals, completion progress, and overdue counts.
     *
     * @return array<string, mixed>
     */
    public function getProjectTaskTotals(Project $project): array;

    /**
     * Get task counts grouped by status for a project.
     *
     * @return array<string, int>
     */
    public function getProjectStatusDistribution(Project $project): array;

    /**
     * Get task counts grouped by priority for a project.
     *
     * @return array<string, int>
     */
    public function getProjectPriorityDistribution(Project $project): array;

    /**
     * Get workload distribution across all project assignees.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getProjectAssigneeWorkload(Project $project): array;
}

