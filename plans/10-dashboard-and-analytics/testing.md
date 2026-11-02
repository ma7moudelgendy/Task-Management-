# Testing Strategy: Phase 10

## Objectives
Validate aggregation accuracy, scoping, edge cases (zero tasks, only cancelled tasks, 100% completion), and security/authorization boundaries.

## Key Feature Tests

1. `tests/Feature/DashboardTest.php`:
   - `test_user_dashboard_returns_correct_task_counts_by_status`:
     - Creates tasks across different statuses assigned to the user.
     - Asserts matching counts in `stats.status`.
   - `test_tasks_assigned_to_others_are_excluded_from_user_dashboard`:
     - Asserts strict personal scoping.
   - `test_overdue_tasks_only_include_past_due_incomplete_tasks`:
     - Tests `due_at < now()`, excluding `DONE` and `CANCELLED`.
   - `test_upcoming_tasks_window`:
     - Tests `due_at` between `now()` and `now() + 7 days`.
   - `test_recent_activity_scoped_to_user_projects`:
     - Logs from user's projects appear; logs from unassociated projects are omitted.

2. `tests/Feature/ProjectAnalyticsTest.php`:
   - `test_project_completion_percentage_calculation`:
     - 4 tasks: 2 DONE, 1 IN_PROGRESS, 1 TODO -> 50.0%.
   - `test_cancelled_tasks_are_excluded_from_active_pool`:
     - 5 tasks: 2 DONE, 2 TODO, 1 CANCELLED -> Active pool = 4 -> 50.0%.
   - `test_empty_project_returns_zero_percentage`:
     - 0 tasks -> 0.0%.
   - `test_assignee_workload_breakdown`:
     - Open count vs completed count per assignee.
   - `test_non_member_cannot_view_project_analytics`:
     - Returns 200 with `key: unauthorized`.
