# Notifications Specification: Teams & Team Memberships

## Overview
Team lifecycle actions generate audit activity logs. When FCM / push notification services are hooked, they will dispatch events for team membership changes.

---

## 1. Events & FCM Payloads

### Event: `TeamMemberAdded`
- **Trigger:** When a user is added to a team (`POST /api/v1/teams/{team}/members`).
- **Recipients:** The invited user (`user_id`).
- **Payload Example:**
```json
{
  "notification": {
    "title": "Welcome to the team!",
    "body": "You were added to team '{team_name}' as {role}."
  },
  "data": {
    "event_type": "team_member_added",
    "team_id": "1",
    "team_slug": "alpha-engineering",
    "role": "MEMBER",
    "click_action": "FLUTTER_NOTIFICATION_CLICK"
  }
}
```

### Event: `TeamMemberRoleChanged`
- **Trigger:** When an owner updates another member's role (`PATCH /api/v1/teams/{team}/members/{user}`).
- **Recipients:** The target user.
- **Payload Example:**
```json
{
  "notification": {
    "title": "Role updated",
    "body": "Your role in team '{team_name}' has been changed to {new_role}."
  },
  "data": {
    "event_type": "team_role_changed",
    "team_id": "1",
    "new_role": "MANAGER",
    "click_action": "FLUTTER_NOTIFICATION_CLICK"
  }
}
```

### Event: `TeamMemberRemoved`
- **Trigger:** When a member is removed from a team (`DELETE /api/v1/teams/{team}/members/{user}`).
- **Recipients:** The removed user.
- **Payload Example:**
```json
{
  "notification": {
    "title": "Team membership removed",
    "body": "You have been removed from team '{team_name}'."
  },
  "data": {
    "event_type": "team_member_removed",
    "team_id": "1"
  }
}
```

