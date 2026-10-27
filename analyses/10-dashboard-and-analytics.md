# Phase 10: Dashboards & Project Analytics

> **Spec Reference:** Section 8.3 (`/projects/{project}/analytics`), Section 8.6 (`/dashboard`), Section 13 (Phase 9), Section 15 (Performance Checklist)  
> **Primary Goal:** Implement performant aggregated dashboard metrics for users, and project-level completion analytics using optimized SQL aggregation and indexed queries.

---

## High-Level Objective
Provide actionable visibility into workload and progress. Users need an aggregated personal dashboard showing assigned tasks, deadlines, and overdue items. Project managers require project-wide completion metrics, member workload distribution, and milestone tracking without placing heavy query loads on the database.

---

## Detailed Task Breakdown

### Task 10.1: User Dashboard Aggregation Query
- [ ] **Step 1:** Create `app/Services/DashboardService.php`:
  - `getUserDashboard(User $user): array`:
    - **Assigned Tasks Counts by Status:**
      ```sql
      SELECT 
          COUNT(CASE WHEN tasks.status = 'TODO' THEN 1 END) as todo_count,
          COUNT(CASE WHEN tasks.status = 'IN_PROGRESS' THEN 1 END) as in_progress_count,
          COUNT(CASE WHEN tasks.status = 'IN_REVIEW' THEN 1 END) as in_review_count,
          COUNT(CASE WHEN tasks.status = 'DONE' THEN 1 END) as done_count
      FROM tasks
      INNER JOIN task_assignees ON tasks.id = task_assignees.task_id
      WHERE task_assignees.user_id = :user_id;
      ```
    - **Overdue Tasks:**
      - Tasks assigned to `$user` where `status != 'DONE'`, `status != 'CANCELLED'`, and `due_at < now()`.
      - Ordered by `due_at asc`, limited to 10 items.
    - **Upcoming Tasks:**
      - Tasks assigned to `$user` where `status != 'DONE'`, `due_at BETWEEN now() AND now()->addDays(7)`.
      - Ordered by `due_at asc`, limited to 10 items.
    - **Recent Activity:**
      - Last 10 activity log entries in projects where user is a member.

### Task 10.2: Project Analytics Service
- [ ] **Step 1:** In `app/Services/ProjectAnalyticsService.php`:
  - `getProjectAnalytics(Project $project): array`:
    - **Task Progress Calculation:**
      - Total tasks: `T`
      - Completed tasks (`status = DONE`): `C`
      - Cancelled tasks: `X`
      - Active pool: `A = T - X`
      - Progress percentage: `A > 0 ? round((C / A) * 100, 2) : 0.0`.
    - **Tasks by Priority:**
      - Counts grouped by `LOW`, `MEDIUM`, `HIGH`, `URGENT`.
    - **Tasks by Status:**
      - Counts grouped by each status enum.
    - **Workload by Assignee:**
      - Group tasks by project members, returning each member's count of assigned open vs completed tasks.
    - **Overdue Count:**
      - Total incomplete tasks past their `due_at`.

### Task 10.3: API Resources
- [ ] **Step 1:** Create `app/Http/Resources/DashboardResource.php`:
  - Exposes: `stats` (status breakdown, priority breakdown), `overdue_tasks`, `upcoming_tasks`, `recent_activity`.
- [ ] **Step 2:** Create `app/Http/Resources/ProjectAnalyticsResource.php`:
  - Exposes: `project_id`, `project_name`, `total_tasks`, `completed_tasks`, `completion_percentage`, `status_distribution`, `priority_distribution`, `assignee_workload`, `overdue_count`.

### Task 10.4: Controllers & Endpoint Wiring
- [ ] **Step 1:** In `app/Http/Controllers/Api/V1/DashboardController.php`:
  - `index(Request $request)`:
    - Calls `DashboardService::getUserDashboard($request->user())`.
    - Returns `DashboardResource`.
- [ ] **Step 2:** In `app/Http/Controllers/Api/V1/ProjectController.php`:
  - `analytics(Project $project)`:
    - Authorizes caller with `ProjectPolicy::view`.
    - Calls `ProjectAnalyticsService::getProjectAnalytics($project)`.
    - Returns `ProjectAnalyticsResource`.
- [ ] **Step 3:** Register endpoints in `routes/api.php`:
  ```php
  Route::get('/dashboard', [DashboardController::class, 'index']);
  Route::get('/projects/{project}/analytics', [ProjectController::class, 'analytics']);
  ```

### Task 10.5: Automated Feature Tests
- [ ] **Step 1:** Create `tests/Feature/DashboardTest.php`:
  - User receives correct counts for tasks assigned specifically to them.
  - Tasks assigned to other users are not counted in the user's dashboard.
  - Overdue list accurately includes tasks with past due dates.
- [ ] **Step 2:** Create `tests/Feature/ProjectAnalyticsTest.php`:
  - Completion percentage calculates correctly when tasks are completed.
  - Cancelled tasks are properly handled in the completion formula.
  - Member not belonging to the project receives 403 Forbidden.
- [ ] **Step 3:** Run `php artisan test --filter=Analytics`.

---

## Acceptance Criteria
- [x] Personal dashboard displays accurate aggregated counts, overdue items, and upcoming tasks.
- [x] Project analytics calculates completion percentages using database aggregations.
- [x] Zero N+1 query regressions; database queries use composite indexes.
- [x] Authorization checks prevent unauthorized users from viewing project analytics.
- [x] Feature tests verify calculation logic and scoping.

