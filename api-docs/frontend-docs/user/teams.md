# Teams & Team Memberships API Documentation

All endpoints return transport HTTP status `200` with the business outcome identified in `key` and `code`.

---

## 1. List User Teams
- **URL:** `/api/v1/teams`
- **Method:** `GET`
- **Auth:** Bearer Token (`auth:sanctum`)
- **Query Parameters:**
  - `per_page` *(optional, integer, default: 15)*

### Response (`key: success`, `code: 200`)
```json
{
  "key": "success",
  "code": 200,
  "msg": "Teams retrieved successfully.",
  "data": {
    "data": [
      {
        "id": 1,
        "name": "Alpha Engineering",
        "slug": "alpha-engineering",
        "created_by": 1,
        "member_count": 3,
        "created_at": "2026-10-28T14:00:00+00:00",
        "updated_at": "2026-10-28T14:00:00+00:00",
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

## 2. Create Team
- **URL:** `/api/v1/teams`
- **Method:** `POST`
- **Auth:** Bearer Token (`auth:sanctum`)

### Request Body
```json
{
  "name": "Alpha Engineering",
  "slug": "alpha-engineering"
}
```
*Note: `slug` is optional. If omitted, a unique slug will be auto-generated from `name`.*

### Response (`key: success`, `code: 201`)
```json
{
  "key": "success",
  "code": 201,
  "msg": "Team created successfully.",
  "data": {
    "id": 1,
    "name": "Alpha Engineering",
    "slug": "alpha-engineering",
    "created_by": 1,
    "member_count": 1,
    "created_at": "2026-10-28T14:00:00+00:00",
    "updated_at": "2026-10-28T14:00:00+00:00",
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

## 3. Get Team Details
- **URL:** `/api/v1/teams/{team}`
- **Method:** `GET`
- **Auth:** Bearer Token (`auth:sanctum`)
- **Permissions:** Team Member or system Admin.

---

## 4. Update Team Settings
- **URL:** `/api/v1/teams/{team}`
- **Method:** `PATCH`
- **Auth:** Bearer Token (`auth:sanctum`)
- **Permissions:** Team `OWNER`, `MANAGER`, or system Admin.

### Request Body
```json
{
  "name": "Alpha Core Engineering",
  "slug": "alpha-core-engineering"
}
```

---

## 5. Delete Team
- **URL:** `/api/v1/teams/{team}`
- **Method:** `DELETE`
- **Auth:** Bearer Token (`auth:sanctum`)
- **Permissions:** Team `OWNER` or system Admin only.

---

## 6. List Team Members
- **URL:** `/api/v1/teams/{team}/members`
- **Method:** `GET`
- **Auth:** Bearer Token (`auth:sanctum`)
- **Permissions:** Team Member or system Admin.

### Response (`key: success`, `code: 200`)
```json
{
  "key": "success",
  "code": 200,
  "msg": "Team members retrieved successfully.",
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "name": "Jane Doe",
      "email": "jane@example.com",
      "role": "OWNER",
      "joined_at": "2026-10-28T14:00:00+00:00"
    }
  ],
  "response_status": {
    "error": false,
    "validation_errors": {}
  }
}
```

---

## 7. Add Team Member
- **URL:** `/api/v1/teams/{team}/members`
- **Method:** `POST`
- **Auth:** Bearer Token (`auth:sanctum`)
- **Permissions:** Team `OWNER`, `MANAGER`, or system Admin.

### Request Body
```json
{
  "email": "colleague@example.com",
  "role": "MEMBER"
}
```
*Allowed roles: `OWNER`, `MANAGER`, `MEMBER`.*

### Responses
- `key=success` (`code=201`): Member attached successfully.
- `key=fail` (`code=422`): User does not exist or is already a member of the team.

---

## 8. Update Member Role
- **URL:** `/api/v1/teams/{team}/members/{user}`
- **Method:** `PATCH`
- **Auth:** Bearer Token (`auth:sanctum`)
- **Permissions:** Team `OWNER` or system Admin only.

### Request Body
```json
{
  "role": "MANAGER"
}
```

### Invariant Safeguard
- `key=fail` (`code=422`): If attempting to demote the sole remaining `OWNER` of a team, returns:
  `"Team must retain at least one owner."`

---

## 9. Remove Member
- **URL:** `/api/v1/teams/{team}/members/{user}`
- **Method:** `DELETE`
- **Auth:** Bearer Token (`auth:sanctum`)
- **Permissions:** Team `OWNER`, `MANAGER`, or system Admin.

### Invariant Safeguard
- `key=fail` (`code=422`): If attempting to remove the sole remaining `OWNER`, returns:
  `"Team must retain at least one owner."`

