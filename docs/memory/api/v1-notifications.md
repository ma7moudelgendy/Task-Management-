# API Memory: `/api/v1/notifications` & `tasks/{task}/recurrence`

## Endpoints

| Method | Path | Summary | Role/Auth | Envelope |
|---|---|---|---|---|
| `GET` | `/api/v1/notifications` | List user notifications (paginated) | BearerAuth (Self) | 200 `key: success` |
| `POST` | `/api/v1/notifications/read-all` | Mark all notifications as read | BearerAuth (Self) | 200 `key: success` |
| `POST` | `/api/v1/notifications/{id}/read` | Mark single notification as read | BearerAuth (Self) | 200 `key: success` |
| `GET` | `/api/v1/tasks/{task}/recurrence` | View task recurrence schedule | BearerAuth (Project member) | 200 `key: success` |
| `POST` | `/api/v1/tasks/{task}/recurrence` | Configure task recurrence rule | BearerAuth (OWNER, MANAGER, Creator) | 200 `key: success`, `code: 201` |

## Business Invariants & Rules
- **Notification Isolation:**
  - Queries are strictly bound to `$request->user()->notifications()`. User IDs cannot be supplied in payloads.
  - Attempting to access another user's notification returns `not_found` (404).
- **Queued Notification Pipeline:**
  - `TaskAssigned` -> `TaskAssignedNotification` (queued). Excludes self-assignments.
  - `TaskStatusChanged` -> `TaskStatusChangedNotification` (queued). Sent to creator and assignees, excluding changer.
  - `TaskDueSoon` -> `TaskDueSoonNotification` (queued). Sent to assignees for tasks due within lookahead window (default 24h).
- **Recurring Task Engine:**
  - Background artisan command `tasks:process-recurring` runs hourly via scheduler with `withoutOverlapping()`.
  - Generates independent task clone with identical metadata and assignees.
  - Advances `next_run_at` atomically; deactivates expired rules when `ends_at` is reached.
  - Idempotent execution avoids duplicate tasks on retries.
- **Deadline Reminders:**
  - Background artisan command `tasks:send-due-reminders` runs daily via scheduler with `withoutOverlapping()`.
  - Cache lock key `task_due_reminder:{id}:{timestamp}` prevents duplicate reminders.

