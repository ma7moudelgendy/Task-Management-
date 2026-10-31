# Notifications Specification: Task Workflow & Status Transitions

## Overview
Status transitions and dependency events generate real-time client notifications and FCM payloads.

---

## 1. Events & Payloads

### Event: `TaskStatusTransitioned`
- **Trigger:** When `POST /api/v1/tasks/{task}/status` succeeds.
- **Recipients:** Task creator, assignees, and watchers.
- **Payload Example:**
```json
{
  "notification": {
    "title": "Task Status Updated",
    "body": "Task '{task_title}' changed from {old_status} to {new_status}."
  },
  "data": {
    "event_type": "task_status_transitioned",
    "task_id": "12",
    "project_id": "4",
    "old_status": "IN_PROGRESS",
    "new_status": "IN_REVIEW",
    "reason": "Submitted for PR review",
    "changed_by": "2",
    "click_action": "FLUTTER_NOTIFICATION_CLICK"
  }
}
```

### Event: `TaskDependencyAdded`
- **Trigger:** When a prerequisite dependency is linked (`POST /api/v1/tasks/{task}/dependencies`).
- **Recipients:** Assignees of the blocked task.
- **Payload Example:**
```json
{
  "notification": {
    "title": "Task Prerequisite Added",
    "body": "Task '{task_title}' is now blocked by '{prerequisite_title}'."
  },
  "data": {
    "event_type": "task_dependency_added",
    "task_id": "12",
    "depends_on_task_id": "8",
    "project_id": "4",
    "click_action": "FLUTTER_NOTIFICATION_CLICK"
  }
}
```

### Event: `TaskDependencyResolved`
- **Trigger:** When a prerequisite task is marked `DONE`.
- **Recipients:** Assignees of dependent/blocked tasks.
- **Payload Example:**
```json
{
  "notification": {
    "title": "Task Prerequisite Completed",
    "body": "Prerequisite '{prerequisite_title}' is done. '{task_title}' is unblocked!"
  },
  "data": {
    "event_type": "task_dependency_resolved",
    "task_id": "12",
    "completed_prerequisite_id": "8",
    "click_action": "FLUTTER_NOTIFICATION_CLICK"
  }
}
```

