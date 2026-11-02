# API Memory: `/api/v1/dashboard` & `/api/v1/projects/{project}/analytics`

## Endpoints

| Method | Path | Summary | Role/Auth | Envelope |
|---|---|---|---|---|
| `GET` | `/api/v1/dashboard` | User personal aggregated dashboard | BearerAuth (Self) | 200 `key: success` |
| `GET` | `/api/v1/projects/{project}/analytics` | Project completion and workload metrics | BearerAuth (Project member) | 200 `key: success` |

## Business Invariants & Rules
- **Dashboard Personal Scoping:**
  - Aggregations only count tasks explicitly assigned to the authenticated user.
  - Overdue list excludes `DONE` and `CANCELLED` tasks, filtered by `due_at < now()`, max 10.
  - Upcoming list includes tasks due within 7 days, max 10, ordered by `due_at asc`.
  - Recent activity feed is scoped strictly to projects where the user is an enrolled member.
- **Project Analytics Formula:**
  - `active_tasks = total_tasks - cancelled_tasks`
  - `completion_percentage = active_tasks > 0 ? round((completed_tasks / active_tasks) * 100, 2) : 0.0`
  - Workload analysis groups tasks by member into open vs completed partitions using a single SQL aggregation join to avoid N+1 queries.
