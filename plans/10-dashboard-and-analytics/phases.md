 # Implementation Phases: Dashboards & Project Analytics

## Checklist

- [ ] **Phase 1: Repositories & Data Aggregation Layer (MODE_A)**
  - [ ] Create `App\Repositories\Contracts\AnalyticsRepositoryInterface`.
  - [ ] Create `App\Repositories\Eloquent\AnalyticsRepository`:
    - `getUserTaskStats(User $user): array`
    - `getUserOverdueTasks(User $user, int $limit = 10): Collection`
    - `getUserUpcomingTasks(User $user, int $limit = 10): Collection`
    - `getUserRecentActivity(User $user, int $limit = 10): Collection`
    - `getProjectStatusDistribution(Project $project): array`
    - `getProjectPriorityDistribution(Project $project): array`
    - `getProjectAssigneeWorkload(Project $project): array`
    - `getProjectOverdueCount(Project $project): int`
  - [ ] Register `AnalyticsRepositoryInterface` in `RepositoryServiceProvider`.

- [ ] **Phase 2: Service Layer**
  - [ ] Implement `App\Services\DashboardService`:
    - `getUserDashboard(User $user): array`
  - [ ] Implement `App\Services\ProjectAnalyticsService`:
    - `getProjectAnalytics(Project $project): array`

- [ ] **Phase 3: API Resources**
  - [ ] Create `App\Http\Resources\Api\DashboardResource`.
  - [ ] Create `App\Http\Resources\Api\ProjectAnalyticsResource`.

- [ ] **Phase 4: Controllers & Endpoint Routing**
  - [ ] Create `App\Http\Controllers\Api\V1\DashboardController` (`index`).
  - [ ] Add `analytics(Project $project)` method to `App\Http\Controllers\Api\V1\ProjectController`.
  - [ ] Register routes in `routes/guards/v1/analytics.php` and mount in `routes/api.php`:
    - `GET /api/v1/dashboard`
    - `GET /api/v1/projects/{project}/analytics`

- [ ] **Phase 5: Feature Tests**
  - [ ] Create `tests/Feature/DashboardTest.php`:
    - Status breakdown counts accurate for assigned user.
    - Tasks assigned to other users excluded.
    - Overdue tasks listed properly.
    - Upcoming tasks within 7 days listed properly.
    - Recent activities across user's projects included.
  - [ ] Create `tests/Feature/ProjectAnalyticsTest.php`:
    - Completion percentage calculated accurately.
    - Cancelled tasks excluded from denominator.
    - Priority and status distributions correct.
    - Assignee workload correctly partitions open vs completed.
    - Non-member access denied with 403 / unauthorized.

- [ ] **Phase 6: OpenAPI Documentation & Verification**
  - [ ] Update `GenerateOpenApiSpec.php` with Dashboard and Analytics paths and schemas.
  - [ ] Update `tests/Feature/OpenApiSpecTest.php`.
  - [ ] Run full test suite with real database assertions.

- [ ] **Phase 7: Memory & Documentation Synchronization**
  - [ ] Create `api-docs/frontend-docs/user/dashboard-and-analytics.md`.
  - [ ] Create `docs/memory/api/v1-dashboard-and-analytics.md`.
  - [ ] Create `docs/memory/features/10-dashboard-and-analytics.md`.
  - [ ] Update `docs/memory/INDEX.md`.
