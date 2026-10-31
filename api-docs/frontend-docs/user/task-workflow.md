# Task Workflow, Dependencies & Audit Trail API Documentation

All endpoints return transport HTTP status `200` with the business outcome identified in `key` and `code`.

---

## 1. Transition Task Status
- **URL:** `/api/v1/tasks/{task}/status`
- **Method:** `POST`
- **Auth:** Bearer Token (`auth:sanctum`)
- **Permissions:** Caller must be project `OWNER`, `MANAGER`, task creator, or assignee.

### State Machine Transition Rules
- `TODO` -> `IN_PROGRESS`, `CANCELLED`
- `IN_PROGRESS` -> `IN_REVIEW`, `TODO`, `CANCELLED`
- `IN_REVIEW` -> `DONE`, `IN_PROGRESS`, `CANCELLED`
- `DONE` -> `IN_PROGRESS` (reopening task clears `completed_at` to `null`)
- `CANCELLED` -> `TODO` (reopening task)

### Completion Blockers (Gating `DONE`)
1. **Prerequisite Dependencies Gate:** A task cannot be marked `DONE` if any prerequisite tasks (`dependencies`) have status other than `DONE`.
2. **Active Subtasks Gate:** A task cannot be marked `DONE` if active subtasks remain incomplete.

### Request Body
```json
{
  "status": "IN_PROGRESS",
  "reason": "Commencing backend development sprint"
}
```

### Response (`key: success`, `code: 200`)
```json
{
  "key": "success",
  "code": 200,
  "msg": "Task status updated successfully.",
  "data": {
    "id": 1,
    "title": "Build Authentication Flow",
    "status": "IN_PROGRESS",
    "completed_at": null,
    "updated_at": "2026-10-31T12:00:00.000000Z"
  },
  "response_status": {
    "error": false,
    "validation_errors": {}
  }
}
```

---

## 2. Add Prerequisite Dependency
- **URL:** `/api/v1/tasks/{task}/dependencies`
- **Method:** `POST`
- **Auth:** Bearer Token (`auth:sanctum`)
- **Permissions:** Caller must be project `OWNER`, `MANAGER`, or task creator.

### Request Body
```json
{
  "depends_on_task_id": 5
}
```

### Validation & Graph Invariants
- **Self-dependency:** A task cannot depend on itself (`task_id != depends_on_task_id`).
- **Project Boundary:** Both tasks must belong to the exact same `project_id`.
- **DAG Cycle Detection:** Depth-First Search (DFS) validates that adding this dependency does not create a circular loop across arbitrary graph depths.

### Response (`key: success`, `code: 201`)
```json
{
  "key": "success",
  "code": 201,
  "msg": "Task dependency added successfully.",
  "data": null,
  "response_status": {
    "error": false,
    "validation_errors": {}
  }
}
```

---

## 3. Remove Prerequisite Dependency
- **URL:** `/api/v1/tasks/{task}/dependencies/{dependency}`
- **Method:** `DELETE`
- **Auth:** Bearer Token (`auth:sanctum`)
- **Permissions:** Caller must be project `OWNER`, `MANAGER`, or task creator.

### Response (`key: success`, `code: 200`)
```json
{
  "key": "success",
  "code": 200,
  "msg": "Task dependency removed successfully.",
  "data": null,
  "response_status": {
    "error": false,
    "validation_errors": {}
  }
}
```

---

## 4. Get Task Dependencies and Dependents
- **URL:** `/api/v1/tasks/{task}/dependencies`
- **Method:** `GET`
- **Auth:** Bearer Token (`auth:sanctum`)
- **Permissions:** Caller must be a member of the task's project or system `ADMIN`.

### Response (`key: success`, `code: 200`)
```json
{
  "key": "success",
  "code": 200,
  "msg": "Task dependencies retrieved successfully.",
  "data": {
    "dependencies": [
      {
        "id": 5,
        "title": "Prerequisite Task",
        "status": "DONE"
      }
    ],
    "dependents": [
      {
        "id": 8,
        "title": "Blocked Waiting Task",
        "status": "TODO"
      }
    ]
  },
  "response_status": {
    "error": false,
    "validation_errors": {}
  }
}
```

---

## 5. Get Task Status Audit History
- **URL:** `/api/v1/tasks/{task}/history`
- **Method:** `GET`
- **Auth:** Bearer Token (`auth:sanctum`)
- **Query Parameters:** `per_page` *(optional, integer, default: 15)*

### Response (`key: success`, `code: 200`)
```json
{
  "key": "success",
  "code": 200,
  "msg": "Task status history retrieved successfully.",
  "data": {
    "data": [
      {
        "id": 1,
        "task_id": 1,
        "changed_by": 2,
        "old_status": "TODO",
        "new_status": "IN_PROGRESS",
        "reason": "Commencing backend development sprint",
        "created_at": "2026-10-31T12:00:00.000000Z",
        "changer": {
          "id": 2,
          "name": "Jane Developer",
          "email": "jane@example.com"
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

