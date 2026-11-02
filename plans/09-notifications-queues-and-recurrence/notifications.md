# Notifications & Recurrence Specifications

## 1. Notification Types & Payloads

### `TaskAssignedNotification`
- **Trigger:** When a user is assigned to a task.
- **Recipient:** The assigned `User`.
- **Channels:** `database` (and `mail` in production).
- **Data Payload:**
  ```json
  {
    "task_id": 10,
    "title": "Design Database Schema",
    "project_id": 2,
    "project_name": "TaskFlow Backend",
    "assigner_id": 1,
    "assigner_name": "John Doe",
    "message": "You were assigned to task: Design Database Schema"
  }
  ```

### `TaskStatusChangedNotification`
- **Trigger:** When task status changes (e.g., `TODO` -> `IN_PROGRESS`, `IN_REVIEW` -> `DONE`).
- **Recipients:** Task creator and other assignees (excluding the user who made the change).
- **Channels:** `database`.
- **Data Payload:**
  ```json
  {
    "task_id": 10,
    "title": "Design Database Schema",
    "project_id": 2,
    "project_name": "TaskFlow Backend",
    "old_status": "TODO",
    "new_status": "IN_PROGRESS",
    "changer_id": 3,
    "changer_name": "Alice Smith",
    "message": "Task Design Database Schema status changed to IN_PROGRESS"
  }
  ```

### `TaskDueSoonNotification`
- **Trigger:** Console command `tasks:send-due-reminders` running for tasks due within 24 hours.
- **Recipients:** All task assignees (and creator if unassigned).
- **Channels:** `database`.
- **Data Payload:**
  ```json
  {
    "task_id": 10,
    "title": "Design Database Schema",
    "project_id": 2,
    "project_name": "TaskFlow Backend",
    "due_at": "2026-11-01T12:00:00Z",
    "hours_remaining": 18,
    "message": "Task Design Database Schema is due in 18 hours"
  }
  ```

---

## 2. Notification API Endpoints
- `GET /api/v1/notifications`: Paginated list of notifications for the authenticated user. Supports `?unread_only=true`.
- `POST /api/v1/notifications/{id}/read`: Mark specific notification as read.
- `POST /api/v1/notifications/read-all`: Mark all notifications as read for current user.
