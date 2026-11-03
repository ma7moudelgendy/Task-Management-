# Phase 10: Dashboards & Project Analytics

## Overview
Phase 10 implements performant aggregated dashboard metrics for users, and project-level completion analytics using optimized SQL aggregation and indexed queries.

## Architecture
Strict adherence to **MODE_A**:
`Controller -> FormRequest -> DTO -> Service -> Repository -> Model`

## Key Capabilities
1. **User Dashboard (`GET /api/v1/dashboard`):**
   - Personal task counts grouped by status (`TODO`, `IN_PROGRESS`, `IN_REVIEW`, `DONE`).
   - Overdue tasks assigned to the user (`status != DONE && status != CANCELLED && due_at < now()`, max 10, ordered by `due_at asc`).
   - Upcoming tasks assigned to the user (`due_at BETWEEN now() AND now()->addDays(7)`, max 10, ordered by `due_at asc`).
   - Recent activity feed (latest 10 entries across projects where user is a member).
2. **Project Analytics (`GET /api/v1/projects/{project}/analytics`):**
   - Progress percentage: `A = total - cancelled`, `completed / A * 100`.
   - Task counts grouped by status and priority.
   - Workload distribution by assignee: each project member's assigned open vs completed tasks.
   - Overdue tasks count.
   - Authorized via `ProjectPolicy::view`.
3. **Optimized Aggregation & Zero N+1:**
   - Single-query conditional aggregations (`COUNT(CASE WHEN ... THEN 1 END)`).
   - Direct indexed lookups with eager loading where appropriate.

