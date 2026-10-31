# Notifications Specification: Projects & Project Memberships

## Overview
Project events trigger audit activity logging and future FCM push notifications when project state changes or memberships are modified.

---

## 1. Events & FCM Payloads

### Event: `ProjectMemberAdded`
- **Trigger:** When a team member is added to a project (`POST /api/v1/projects/{project}/members`).
- **Recipients:** The enrolled user (`user_id`).
- **Payload Example:**
```json
{
  "notification": {
    "title": "Added to Project",
    "body": "You were added to '{project_name}' as {role}."
  },
  "data": {
    "event_type": "project_member_added",
    "project_id": "1",
    "team_id": "1",
    "role": "MEMBER",
    "click_action": "FLUTTER_NOTIFICATION_CLICK"
  }
}
```

### Event: `ProjectMemberRoleChanged`
- **Trigger:** When a project owner updates another member's role (`PATCH /api/v1/projects/{project}/members/{user}`).
- **Recipients:** The target user.
- **Payload Example:**
```json
{
  "notification": {
    "title": "Project Role Updated",
    "body": "Your role in project '{project_name}' was updated to {new_role}."
  },
  "data": {
    "event_type": "project_role_changed",
    "project_id": "1",
    "new_role": "MANAGER",
    "click_action": "FLUTTER_NOTIFICATION_CLICK"
  }
}
```

### Event: `ProjectMemberRemoved`
- **Trigger:** When a member is removed from a project (`DELETE /api/v1/projects/{project}/members/{user}`).
- **Recipients:** The removed user.
- **Payload Example:**
```json
{
  "notification": {
    "title": "Removed from Project",
    "body": "You have been removed from project '{project_name}'."
  },
  "data": {
    "event_type": "project_member_removed",
    "project_id": "1"
  }
}
```

