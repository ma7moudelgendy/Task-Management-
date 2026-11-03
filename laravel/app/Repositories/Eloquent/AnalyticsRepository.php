<?php

namespace App\Repositories\Eloquent;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Repositories\Contracts\AnalyticsRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class AnalyticsRepository implements AnalyticsRepositoryInterface
{
    /**
     * Get aggregate task statistics for a specific user.
     *
     * @return array<string, mixed>
     */
    public function getUserTaskStats(User $user): array
    {
        $raw = DB::table('tasks')
            ->join('task_assignees', 'tasks.id', '=', 'task_assignees.task_id')
            ->where('task_assignees.user_id', $user->id)
            ->whereNull('tasks.deleted_at')
            ->selectRaw("
                COUNT(CASE WHEN tasks.status = 'TODO' THEN 1 END) as todo_count,
                COUNT(CASE WHEN tasks.status = 'IN_PROGRESS' THEN 1 END) as in_progress_count,
                COUNT(CASE WHEN tasks.status = 'IN_REVIEW' THEN 1 END) as in_review_count,
                COUNT(CASE WHEN tasks.status = 'DONE' THEN 1 END) as done_count,
                COUNT(CASE WHEN tasks.status = 'CANCELLED' THEN 1 END) as cancelled_count,
                COUNT(*) as total_assigned,
                COUNT(CASE WHEN tasks.status NOT IN ('DONE', 'CANCELLED') AND tasks.due_at < NOW() THEN 1 END) as overdue_count
            ")
            ->first();

        return [
            'status' => [
                'TODO' => (int) ($raw?->todo_count ?? 0),
                'IN_PROGRESS' => (int) ($raw?->in_progress_count ?? 0),
                'IN_REVIEW' => (int) ($raw?->in_review_count ?? 0),
                'DONE' => (int) ($raw?->done_count ?? 0),
                'CANCELLED' => (int) ($raw?->cancelled_count ?? 0),
            ],
            'total_assigned' => (int) ($raw?->total_assigned ?? 0),
            'overdue_count' => (int) ($raw?->overdue_count ?? 0),
        ];
    }

    /**
     * Get overdue tasks assigned to the user.
     *
     * @return Collection<int, Task>
     */
    public function getUserOverdueTasks(User $user, int $limit = 10): Collection
    {
        return Task::query()
            ->with(['project:id,name,team_id'])
            ->whereHas('assignees', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            })
            ->whereNotIn('status', [TaskStatus::DONE->value, TaskStatus::CANCELLED->value])
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->orderBy('due_at', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get upcoming tasks assigned to the user (due within 7 days).
     *
     * @return Collection<int, Task>
     */
    public function getUserUpcomingTasks(User $user, int $limit = 10): Collection
    {
        $now = now();
        $threshold = $now->copy()->addDays(7);

        return Task::query()
            ->with(['project:id,name,team_id'])
            ->whereHas('assignees', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            })
            ->whereNotIn('status', [TaskStatus::DONE->value, TaskStatus::CANCELLED->value])
            ->whereNotNull('due_at')
            ->whereBetween('due_at', [$now, $threshold])
            ->orderBy('due_at', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get recent activity feed across projects enrolled by the user.
     *
     * @return Collection<int, ActivityLog>
     */
    public function getUserRecentActivity(User $user, int $limit = 10): Collection
    {
        $projectIds = $user->projects()->pluck('projects.id')->all();

        if (empty($projectIds)) {
            return new Collection();
        }

        return ActivityLog::query()
            ->with(['actor:id,name,email', 'project:id,name'])
            ->whereIn('project_id', $projectIds)
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Get project overall task totals, completion progress, and overdue counts.
     *
     * @return array<string, mixed>
     */
    public function getProjectTaskTotals(Project $project): array
    {
        $raw = DB::table('tasks')
            ->where('project_id', $project->id)
            ->whereNull('deleted_at')
            ->selectRaw("
                COUNT(*) as total_tasks,
                COUNT(CASE WHEN status = 'DONE' THEN 1 END) as completed_tasks,
                COUNT(CASE WHEN status = 'CANCELLED' THEN 1 END) as cancelled_tasks,
                COUNT(CASE WHEN status NOT IN ('DONE', 'CANCELLED') AND due_at < NOW() THEN 1 END) as overdue_count
            ")
            ->first();

        $total = (int) ($raw?->total_tasks ?? 0);
        $completed = (int) ($raw?->completed_tasks ?? 0);
        $cancelled = (int) ($raw?->cancelled_tasks ?? 0);
        $active = max(0, $total - $cancelled);
        $overdue = (int) ($raw?->overdue_count ?? 0);

        $completionPercentage = $active > 0
            ? round(($completed / $active) * 100, 2)
            : 0.0;

        return [
            'total_tasks' => $total,
            'completed_tasks' => $completed,
            'cancelled_tasks' => $cancelled,
            'active_tasks' => $active,
            'completion_percentage' => $completionPercentage,
            'overdue_count' => $overdue,
        ];
    }

    /**
     * Get task counts grouped by status for a project.
     *
     * @return array<string, int>
     */
    public function getProjectStatusDistribution(Project $project): array
    {
        $counts = DB::table('tasks')
            ->where('project_id', $project->id)
            ->whereNull('deleted_at')
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->all();

        $result = [];
        foreach (TaskStatus::cases() as $status) {
            $result[$status->value] = (int) ($counts[$status->value] ?? 0);
        }

        return $result;
    }

    /**
     * Get task counts grouped by priority for a project.
     *
     * @return array<string, int>
     */
    public function getProjectPriorityDistribution(Project $project): array
    {
        $counts = DB::table('tasks')
            ->where('project_id', $project->id)
            ->whereNull('deleted_at')
            ->select('priority', DB::raw('count(*) as count'))
            ->groupBy('priority')
            ->pluck('count', 'priority')
            ->all();

        $result = [];
        foreach (TaskPriority::cases() as $priority) {
            $result[$priority->value] = (int) ($counts[$priority->value] ?? 0);
        }

        return $result;
    }

    /**
     * Get workload distribution across all project assignees.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getProjectAssigneeWorkload(Project $project): array
    {
        $rows = DB::table('project_members')
            ->join('users', 'project_members.user_id', '=', 'users.id')
            ->leftJoin('task_assignees', 'users.id', '=', 'task_assignees.user_id')
            ->leftJoin('tasks', function ($join) use ($project) {
                $join->on('task_assignees.task_id', '=', 'tasks.id')
                    ->where('tasks.project_id', '=', $project->id)
                    ->whereNull('tasks.deleted_at');
            })
            ->where('project_members.project_id', $project->id)
            ->groupBy('users.id', 'users.name', 'users.email')
            ->select(
                'users.id as user_id',
                'users.name',
                'users.email',
                DB::raw("COUNT(CASE WHEN tasks.id IS NOT NULL AND tasks.status NOT IN ('DONE', 'CANCELLED') THEN 1 END) as open_tasks_count"),
                DB::raw("COUNT(CASE WHEN tasks.id IS NOT NULL AND tasks.status = 'DONE' THEN 1 END) as completed_tasks_count"),
                DB::raw("COUNT(CASE WHEN tasks.id IS NOT NULL THEN 1 END) as total_assigned")
            )
            ->orderBy('users.name', 'asc')
            ->get();

        return $rows->map(function ($row) {
            return [
                'user_id' => (int) $row->user_id,
                'name' => (string) $row->name,
                'email' => (string) $row->email,
                'open_tasks_count' => (int) $row->open_tasks_count,
                'completed_tasks_count' => (int) $row->completed_tasks_count,
                'total_assigned' => (int) $row->total_assigned,
            ];
        })->all();
    }
}

