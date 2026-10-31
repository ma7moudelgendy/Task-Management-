# Notifications Specification: Comments & Attachments

## Overview
Discussions and uploads on tasks trigger domain notifications and FCM push notifications.

---

## 1. Events & Payloads

### Event: `TaskCommentAdded`
- **Trigger:** When a comment is posted (`POST /api/v1/tasks/{task}/comments`).
- **Recipients:** Task creator and active assignees (excluding author).
- **Payload Example:**
```json
{
  "notification": {
    "title": "New Comment on Task",
    "body": "{author_name} commented on '{task_title}': '{comment_snippet}'"
  },
  "data": {
    "event_type": "task_comment_added",
    "task_id": "15",
    "comment_id": "4",
    "project_id": "2",
    "author_id": "3",
    "click_action": "FLUTTER_NOTIFICATION_CLICK"
  }
}
```

### Event: `TaskAttachmentUploaded`
- **Trigger:** When a file is uploaded to a task (`POST /api/v1/tasks/{task}/attachments`).
- **Recipients:** Task creator and assignees.
- **Payload Example:**
```json
{
  "notification": {
    "title": "File Attached to Task",
    "body": "{uploader_name} attached '{file_name}' to '{task_title}'."
  },
  "data": {
    "event_type": "task_attachment_uploaded",
    "task_id": "15",
    "attachment_id": "7",
    "file_name": "architecture-spec.pdf",
    "size": "1048576",
    "click_action": "FLUTTER_NOTIFICATION_CLICK"
  }
}
```

