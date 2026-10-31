# Notifications Specification: Tasks & Assignees

## Overview
Task creation, updates, and assignments trigger domain notifications and FCM push payloads for mobile and web clients.

---

## 1. Events & FCM Payloads

### Event: `TaskAssigned`
- **Trigger:** When a user is assigned to a task (upon task creation or via assignee sync `PUT /api/v1/tasks/{task}/assignees`).
- **Recipients:** The assigned user(s).
- **Payload Example:**
```json
{
  "notification": {
    "title": "New Task Assigned",
    "body": "You were assigned to task '{task_title}' in project '{project_name}'."
  },
  "data": {
    "event_type": "task_assigned",
    "task_id": "10",
    "project_id": "3",
    "priority": "HIGH",
    "due_at": "2026-11-10T18:00:00Z",
    "click_action": "FLUTTER_NOTIFICATION_CLICK"
  }
}
```

### Event: `TaskStatusUpdated`
- **Trigger:** When task status changes (e.g. from `TODO` to `IN_PROGRESS` or `DONE`).
- **Recipients:** Task creator and active assignees.
- **Payload Example:**
```json
{
  "notification": {
    "title": "Task Status Updated",
    "body": "Task '{task_title}' status changed to {new_status}."
  },
  "data": {
    "event_type": "task_status_updated",
    "task_id": "10",
    "project_id": "3",
    "new_status": "DONE",
    "completed_at": "2026-11-05T14:30:00Z",
    "click_action": "FLUTTER_NOTIFICATION_CLICK"
  }
}
```

### Event: `TaskDueSoon`
- **Trigger:** Scheduled reminder job (Phase 9) for tasks approaching deadline.
- **Recipients:** All assigned users.
- **Payload Example:**
```json
{
  "notification": {
    "title": "Task Deadline Approaching",
    "body": "Task '{task_title}' is due in 24 hours."
  },
  "data": {
    "event_type": "task_due_soon",
    "task_id": "10",
    "project_id": "3",
    "due_at": "2026-11-10T18:00:00Z",
    "click_action": "FLUTTER_NOTIFICATION_CLICK"
  }
}
```

