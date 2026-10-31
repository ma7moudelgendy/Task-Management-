# Projects & Project Memberships API Documentation

All endpoints return transport HTTP status `200` with the business outcome identified in `key` and `code`.

---

## 1. List User Projects
- **URL:** `/api/v1/projects`
- **Method:** `GET`
- **Auth:** Bearer Token (`auth:sanctum`)
- **Query Parameters:**
  - `team_id` *(optional, integer)*: Filter projects by parent team.
  - `status` *(optional, string)*: Filter by project status (`ACTIVE`, `ON_HOLD`, `COMPLETED`, `ARCHIVED`).
  - `per_page` *(optional, integer, default: 15)*.

### Response (`key: success`, `code: 200`)
```json
{
  "key": "success",
  "code": 200,
  "msg": "Projects retrieved successfully.",
  "data": {
    "data": [
      {
        "id": 1,
        "team_id": 1,
        "name": "Core Architecture Revamp",
        "description": "Redesigning database layer",
        "status": "ACTIVE",
        "start_date": "2026-11-01",
        "due_date": "2026-12-31",
        "created_by": 1,
        "member_count": 3,
        "tasks_count": 0,
        "created_at": "2026-10-28T16:00:00+00:00",
        "updated_at": "2026-10-28T16:00:00+00:00",
        "helpers": {
          "can_update": true,
          "can_delete": true,
          "can_manage_members": true,
          "can_change_role": true
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

## 2. Create Project
- **URL:** `/api/v1/projects`
- **Method:** `POST`
- **Auth:** Bearer Token (`auth:sanctum`)
- **Permissions:** Parent Team `OWNER`, `MANAGER`, or system Admin.

### Request Body
```json
{
  "team_id": 1,
  "name": "Project Phoenix",
  "description": "Core infrastructure revamp",
  "status": "ACTIVE",
  "start_date": "2026-11-01",
  "due_date": "2026-12-31"
}
```
*Validation:* `due_date` must be equal to or after `start_date`. Creator automatically becomes the initial project `OWNER`.

### Response (`key: success`, `code: 201`)
```json
{
  "key": "success",
  "code": 201,
  "msg": "Project created successfully.",
  "data": {
    "id": 1,
    "team_id": 1,
    "name": "Project Phoenix",
    "description": "Core infrastructure revamp",
    "status": "ACTIVE",
    "start_date": "2026-11-01",
    "due_date": "2026-12-31",
    "created_by": 1,
    "member_count": 1,
    "tasks_count": 0,
    "created_at": "2026-10-28T16:00:00+00:00",
    "updated_at": "2026-10-28T16:00:00+00:00",
    "helpers": {
      "can_update": true,
      "can_delete": true,
      "can_manage_members": true,
      "can_change_role": true
    }
  },
  "response_status": {
    "error": false,
    "validation_errors": {}
  }
}
```

---

## 3. Get Project Details
- **URL:** `/api/v1/projects/{project}`
- **Method:** `GET`
- **Auth:** Bearer Token (`auth:sanctum`)
- **Permissions:** Enrolled Project Member or system Admin (Membership Separation enforced).

---

## 4. Update Project Settings
- **URL:** `/api/v1/projects/{project}`
- **Method:** `PATCH`
- **Auth:** Bearer Token (`auth:sanctum`)
- **Permissions:** Project `OWNER`, `MANAGER`, or system Admin.

### Request Body
```json
{
  "name": "Project Phoenix 2.0",
  "status": "ON_HOLD",
  "due_date": "2027-01-15"
}
```

---

## 5. Delete Project
- **URL:** `/api/v1/projects/{project}`
- **Method:** `DELETE`
- **Auth:** Bearer Token (`auth:sanctum`)
- **Permissions:** Project `OWNER` or system Admin only.

---

## 6. List Project Members
- **URL:** `/api/v1/projects/{project}/members`
- **Method:** `GET`
- **Auth:** Bearer Token (`auth:sanctum`)
- **Permissions:** Enrolled Project Member or system Admin.

### Response (`key: success`, `code: 200`)
```json
{
  "key": "success",
  "code": 200,
  "msg": "Project members retrieved successfully.",
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "name": "Alice Developer",
      "email": "alice@example.com",
      "role": "OWNER",
      "joined_at": "2026-10-28T16:00:00+00:00"
    }
  ],
  "response_status": {
    "error": false,
    "validation_errors": {}
  }
}
```

---

## 7. Add Member to Project
- **URL:** `/api/v1/projects/{project}/members`
- **Method:** `POST`
- **Auth:** Bearer Token (`auth:sanctum`)
- **Permissions:** Project `OWNER`, `MANAGER`, or system Admin.

### Request Body
```json
{
  "user_id": 2,
  "role": "MEMBER"
}
```
*Allowed roles: `OWNER`, `MANAGER`, `MEMBER`, `VIEWER`.*  
*Prerequisite:* Target user **must already be an active member of the project's parent team**.

### Responses
- `key=success` (`code=201`): Member enrolled in project.
- `key=fail` (`code=422`): Candidate is not in the parent team, or is already in the project.

---

## 8. Update Project Member Role
- **URL:** `/api/v1/projects/{project}/members/{user}`
- **Method:** `PATCH`
- **Auth:** Bearer Token (`auth:sanctum`)
- **Permissions:** Project `OWNER` or system Admin only.

### Request Body
```json
{
  "role": "MANAGER"
}
```

### Invariant Safeguard
- `key=fail` (`code=422`): If attempting to demote the sole remaining `OWNER`, returns:
  `"Project must retain at least one owner."`

---

## 9. Remove Member from Project
- **URL:** `/api/v1/projects/{project}/members/{user}`
- **Method:** `DELETE`
- **Auth:** Bearer Token (`auth:sanctum`)
- **Permissions:** Project `OWNER`, `MANAGER`, or system Admin.

### Task History & Access Policy
- Project access is **immediately revoked** for the removed user.
- Existing task assignments for the removed user are **strictly preserved** for historical and audit purposes.
- If attempting to remove the sole remaining `OWNER`, returns `code=422` (`"Project must retain at least one owner."`).

