# Notifications & Task Recurrence API Documentation

All endpoints return transport HTTP status `200` with the business outcome identified in `key` and `code`.

---

## 1. List Notifications
- **URL:** `/api/v1/notifications`
- **Method:** `GET`
- **Auth:** Bearer Token (`auth:sanctum`)
- **Permissions:** Scoped strictly to the authenticated user.

### Query Parameters
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `unread_only` | boolean | `false` | Filter to unread notifications only |
| `per_page` | integer | `15` | Items per page |
| `page` | integer | `1` | Page number |

### Response (`key: success`, `code: 200`)
```json
{
  "key": "success",
  "code": 200,
  "msg": "Notifications retrieved successfully.",
  "data": {
    "data": [
      {
        "id": "a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11",
        "type": "TaskAssignedNotification",
        "data": {
          "task_id": 10,
          "title": "Design System Tokens",
          "project_id": 2,
          "project_name": "Design Sprint",
          "assigner_id": 1,
          "assigner_name": "John Doe",
          "message": "You were assigned to task: Design System Tokens"
        },
        "read_at": null,
        "created_at": "2026-10-31T12:00:00.000000Z"
      }
    ],
    "links": {
      "first": "http://localhost:8000/api/v1/notifications?page=1",
      "last": "http://localhost:8000/api/v1/notifications?page=1",
      "prev": null,
      "next": null
    },
    "meta": {
      "current_page": 1,
      "last_page": 1,
      "per_page": 15,
      "total": 1
    }
  },
  "response_status": {
    "error": false,
    "validation_errors": {}
  }
}
```

---

## 2. Mark Single Notification as Read
- **URL:** `/api/v1/notifications/{id}/read`
- **Method:** `POST`
- **Auth:** Bearer Token (`auth:sanctum`)
- **Permissions:** Scoped to the authenticated user's notification. Attempting to mark another user's notification returns `not_found`.

### Response (`key: success`, `code: 200`)
```json
{
  "key": "success",
  "code": 200,
  "msg": "Notification marked as read.",
  "data": {
    "id": "a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11",
    "type": "TaskAssignedNotification",
    "data": {
      "task_id": 10,
      "title": "Design System Tokens"
    },
    "read_at": "2026-10-31T12:05:00.000000Z",
    "created_at": "2026-10-31T12:00:00.000000Z"
  },
  "response_status": {
    "error": false,
    "validation_errors": {}
  }
}
```

---

## 3. Mark All Notifications as Read
- **URL:** `/api/v1/notifications/read-all`
- **Method:** `POST`
- **Auth:** Bearer Token (`auth:sanctum`)
- **Permissions:** Authenticated user.

### Response (`key: success`, `code: 200`)
```json
{
  "key": "success",
  "code": 200,
  "msg": "All notifications marked as read.",
  "data": {
    "marked_as_read_count": 3
  },
  "response_status": {
    "error": false,
    "validation_errors": {}
  }
}
```

---

## 4. Get Task Recurrence Rule
- **URL:** `/api/v1/tasks/{task}/recurrence`
- **Method:** `GET`
- **Auth:** Bearer Token (`auth:sanctum`)
- **Permissions:** Any member of the task's project.

### Response (`key: success`, `code: 200`)
```json
{
  "key": "success",
  "code": 200,
  "msg": "Recurrence rule retrieved successfully.",
  "data": {
    "id": 1,
    "task_id": 10,
    "frequency": "WEEKLY",
    "interval": 1,
    "starts_at": "2026-11-01T09:00:00.000000Z",
    "ends_at": null,
    "next_run_at": "2026-11-01T09:00:00.000000Z",
    "timezone": "UTC",
    "is_active": true
  },
  "response_status": {
    "error": false,
    "validation_errors": {}
  }
}
```

---

## 5. Set or Update Task Recurrence Rule
- **URL:** `/api/v1/tasks/{task}/recurrence`
- **Method:** `POST`
- **Auth:** Bearer Token (`auth:sanctum`)
- **Permissions:** Task creator or project `OWNER`/`MANAGER`.

### Request Body
```json
{
  "frequency": "WEEKLY",
  "interval": 1,
  "starts_at": "2026-11-01T09:00:00Z",
  "ends_at": "2027-01-01T09:00:00Z",
  "timezone": "UTC"
}
```

### Validation Rules
| Field | Rules | Description |
|---|---|---|
| `frequency` | `required`, `in:DAILY,WEEKLY,MONTHLY` | Schedule recurrence cadence |
| `interval` | `required`, `integer`, `min:1`, `max:365` | Frequency multiplier |
| `starts_at` | `required`, `date` | Start timestamp |
| `ends_at` | `nullable`, `date`, `after:starts_at` | Optional expiration timestamp |
| `timezone` | `sometimes`, `string`, `timezone` | Target timezone |

### Response (`key: success`, `code: 201`)
```json
{
  "key": "success",
  "code": 201,
  "msg": "Recurrence rule saved successfully.",
  "data": {
    "id": 1,
    "task_id": 10,
    "frequency": "WEEKLY",
    "interval": 1,
    "starts_at": "2026-11-01T09:00:00.000000Z",
    "ends_at": "2027-01-01T09:00:00.000000Z",
    "next_run_at": "2026-11-01T09:00:00.000000Z",
    "timezone": "UTC",
    "is_active": true
  },
  "response_status": {
    "error": false,
    "validation_errors": {}
  }
}
```
