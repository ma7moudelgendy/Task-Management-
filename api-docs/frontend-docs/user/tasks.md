# Tasks, Subtasks & Assignees API Documentation

All endpoints return transport HTTP status `200` with the business outcome identified in `key` and `code`.

---

## 1. List Tasks Across Enrolled Projects
- **URL:** `/api/v1/tasks`
- **Method:** `GET`
- **Auth:** Bearer Token (`auth:sanctum`)
- **Query Parameters:**
  - `status` *(optional, enum: `TODO`, `IN_PROGRESS`, `IN_REVIEW`, `DONE`, `CANCELLED`)*
  - `priority` *(optional, enum: `LOW`, `MEDIUM`, `HIGH`, `URGENT`)*
  - `assignee_id` *(optional, integer)*: Filter tasks assigned to specific user.
  - `due_before` *(optional, ISO 8601 date-time string)*
  - `due_after` *(optional, ISO 8601 date-time string)*
  - `sort_by` *(optional, enum: `due_at`, `priority`, `created_at`, `position`, `title`, default: `created_at`)*
  - `sort_dir` *(optional, enum: `asc`, `desc`, default: `desc`)*
  - `per_page` *(optional, integer, default: 15, max: 100)*

### Response (`key: success`, `code: 200`)
```json
{
  "key": "success",
  "code": 200,
  "msg": "Tasks retrieved successfully.",
  "data": {
    "data": [
      {
        "id": 1,
        "project_id": 1,
        "parent_task_id": null,
        "created_by": 1,
        "title": "Design Database Schema",
        "description": "Create migrations and relationships",
        "status": "TODO",
        "priority": "HIGH",
        "start_at": "2026-11-01T09:00:00.000000Z",
        "due_at": "2026-11-10T18:00:00.000000Z",
        "completed_at": null,
        "position": 0,
        "created_at": "2026-10-30T10:00:00.000000Z",
        "updated_at": "2026-10-30T10:00:00.000000Z",
        "helpers": {
          "can_update": true,
          "can_delete": true,
          "can_assign": true
        }
      }
    ],
    "meta": {
      "current_page": 1,
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

## 2. List Tasks in a Project
- **URL:** `/api/v1/projects/{project}/tasks`
- **Method:** `GET`
- **Auth:** Bearer Token (`auth:sanctum`)
- **Permissions:** Caller must be a member of the project or system `ADMIN`.
- **Query Parameters:** Same filters and pagination options as above.

---

## 3. Create Task or Subtask in Project
- **URL:** `/api/v1/projects/{project}/tasks`
- **Method:** `POST`
- **Auth:** Bearer Token (`auth:sanctum`)
- **Permissions:** Caller must hold `OWNER`, `MANAGER`, or `MEMBER` in the project (`VIEWER` is denied).

### Request Body
```json
{
  "title": "Build Authentication Flow",
  "description": "Sanctum bearer tokens and policies",
  "priority": "HIGH",
  "parent_task_id": null,
  "start_at": "2026-11-01 09:00:00",
  "due_at": "2026-11-10 18:00:00",
  "position": 1,
  "assignee_ids": [2, 3]
}
```

### Invariants:
- If `parent_task_id` is supplied, it must belong to the exact same `project_id`.
- All `assignee_ids` must be active verified members of the project.
- `due_at` must be greater than or equal to `start_at`.

### Response (`key: success`, `code: 201`)
```json
{
  "key": "success",
  "code": 201,
  "msg": "Task created successfully.",
  "data": {
    "id": 1,
    "project_id": 1,
    "parent_task_id": null,
    "created_by": 1,
    "title": "Build Authentication Flow",
    "description": "Sanctum bearer tokens and policies",
    "status": "TODO",
    "priority": "HIGH",
    "start_at": "2026-11-01T09:00:00.000000Z",
    "due_at": "2026-11-10T18:00:00.000000Z",
    "completed_at": null,
    "position": 1,
    "created_at": "2026-10-30T10:00:00.000000Z",
    "updated_at": "2026-10-30T10:00:00.000000Z",
    "helpers": {
      "can_update": true,
      "can_delete": true,
      "can_assign": true
    }
  },
  "response_status": {
    "error": false,
    "validation_errors": {}
  }
}
```

---

## 4. Get Task Details
- **URL:** `/api/v1/tasks/{task}`
- **Method:** `GET`
- **Auth:** Bearer Token (`auth:sanctum`)
- **Permissions:** Caller must be a member of the task's project or system `ADMIN`.

### Response (`key: success`, `code: 200`)
Returns complete task object with eager-loaded `project`, `creator`, `assignees`, and `subtasks`.

---

## 5. Update Task
- **URL:** `/api/v1/tasks/{task}`
- **Method:** `PATCH`
- **Auth:** Bearer Token (`auth:sanctum`)
- **Permissions:** Caller must be project `OWNER`, `MANAGER`, task creator, or assignee.

### Request Body
```json
{
  "title": "Refined Auth Flow",
  "status": "DONE",
  "priority": "URGENT",
  "parent_task_id": null
}
```

### Invariants:
- `parent_task_id` cannot be self or create circular dependency.
- Transitioning `status` to `DONE` automatically populates `completed_at` with timestamp.
- Transitioning away from `DONE` clears `completed_at` to `null`.

---

## 6. Delete Task (Soft Delete)
- **URL:** `/api/v1/tasks/{task}`
- **Method:** `DELETE`
- **Auth:** Bearer Token (`auth:sanctum`)
- **Permissions:** Caller must be project `OWNER`, `MANAGER`, task creator, or system `ADMIN`.

---

## 7. Sync Task Assignees
- **URL:** `/api/v1/tasks/{task}/assignees`
- **Method:** `PUT`
- **Auth:** Bearer Token (`auth:sanctum`)
- **Permissions:** Caller must hold `OWNER` or `MANAGER` in the project, or system `ADMIN`.

### Request Body
```json
{
  "assignee_ids": [2, 4]
}
```

### Invariants:
- All user IDs in `assignee_ids` must belong to `project_members` for the task's project.
- Replaces current assignees atomically and tracks `assigned_by` and `assigned_at`.

