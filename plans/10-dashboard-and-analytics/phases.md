# Implementation Phases: Dashboards & Project Analytics

## Checklist

- [x] **Phase 1: Repositories & Data Aggregation Layer (MODE_A)**
  - [x] Create `App\Repositories\Contracts\AnalyticsRepositoryInterface`.
  - [x] Create `App\Repositories\Eloquent\AnalyticsRepository`:
    - `getUserTaskStats(User $user): array`
    - `getUserOverdueTasks(User $user, int $limit = 10): Collection`
    - `getUserUpcomingTasks(User $user, int $limit = 10): Collection`
    - `getUserRecentActivity(User $user, int $limit = 10): Collection`
    - `getProjectStatusDistribution(Project $project): array`
    - `getProjectPriorityDistribution(Project $project): array`
    - `getProjectAssigneeWorkload(Project $project): array`
    - `getProjectOverdueCount(Project $project): int`
  - [x] Register `AnalyticsRepositoryInterface` in `RepositoryServiceProvider`.

- [x] **Phase 2: Service Layer**
  - [x] Implement `App\Services\DashboardService`:
    - `getUserDashboard(User $user): array`
  - [x] Implement `App\Services\ProjectAnalyticsService`:
    - `getProjectAnalytics(Project $project): array`

- [x] **Phase 3: API Resources**
  - [x] Create `App\Http\Resources\Api\DashboardResource`.
  - [x] Create `App\Http\Resources\Api\ProjectAnalyticsResource`.

- [x] **Phase 4: Controllers & Endpoint Routing**
  - [x] Create `App\Http\Controllers\Api\V1\DashboardController` (`index`).
  - [x] Add `analytics(Project $project)` method to `App\Http\Controllers\Api\V1\ProjectController`.
  - [x] Register routes in `routes/guards/v1/analytics.php` and mount in `routes/api.php`:
    - `GET /api/v1/dashboard`
    - `GET /api/v1/projects/{project}/analytics`

- [x] **Phase 5: Feature Tests**
  - [x] Create `tests/Feature/DashboardTest.php`:
    - Status breakdown counts accurate for assigned user.
    - Tasks assigned to other users excluded.
    - Overdue tasks listed properly.
    - Upcoming tasks within 7 days listed properly.
    - Recent activities across user's projects included.
  - [x] Create `tests/Feature/ProjectAnalyticsTest.php`:
    - Completion percentage calculated accurately.
    - Cancelled tasks excluded from denominator.
    - Priority and status distributions correct.
    - Assignee workload correctly partitions open vs completed.
    - Non-member access denied with 403 / unauthorized.

- [x] **Phase 6: OpenAPI Documentation & Verification**
  - [x] Update `GenerateOpenApiSpec.php` with Dashboard and Analytics paths and schemas.
  - [x] Update `tests/Feature/OpenApiSpecTest.php`.
  - [x] Run full test suite with real database assertions.

- [x] **Phase 7: Memory & Documentation Synchronization**
  - [x] Create `api-docs/frontend-docs/user/dashboard-and-analytics.md`.
  - [x] Create `docs/memory/api/v1-dashboard-and-analytics.md`.
  - [x] Create `docs/memory/features/10-dashboard-and-analytics.md`.
  - [x] Update `docs/memory/INDEX.md`.
