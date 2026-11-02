<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\AnalyticsRepositoryInterface;

class DashboardService
{
    public function __construct(
        protected AnalyticsRepositoryInterface $analyticsRepository,
    ) {}

    /**
     * Get aggregated personal dashboard for the authenticated user.
     *
     * @return array<string, mixed>
     */
    public function getUserDashboard(User $user): array
    {
        return [
            'stats' => $this->analyticsRepository->getUserTaskStats($user),
            'overdue_tasks' => $this->analyticsRepository->getUserOverdueTasks($user, 10),
            'upcoming_tasks' => $this->analyticsRepository->getUserUpcomingTasks($user, 10),
            'recent_activity' => $this->analyticsRepository->getUserRecentActivity($user, 10),
        ];
    }
}
