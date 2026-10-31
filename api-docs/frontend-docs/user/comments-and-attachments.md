# Comments & Attachments API Documentation

All endpoints (except binary file download streaming) return transport HTTP status `200` with the business outcome identified in `key` and `code`.

---

## 1. List Task Comments
- **URL:** `/api/v1/tasks/{task}/comments`
- **Method:** `GET`
- **Auth:** Bearer Token (`auth:sanctum`)
- **Permissions:** Caller must be a member of the task's project (`OWNER`, `MANAGER`, `MEMBER`, `VIEWER`).

### Query Parameters
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `per_page` | integer | `15` | Items per page |
| `page` | integer | `1` | Page number |

### Response (`key: success`, `code: 200`)
```json
{
  "key": "success",
  "code": 200,
  "msg": "Comments retrieved successfully.",
  "data": {
    "data": [
      {
        "id": 1,
        "task_id": 10,
        "user_id": 2,
        "body": "Let us schedule a code review tomorrow morning.",
        "created_at": "2026-10-31T10:00:00.000000Z",
        "updated_at": "2026-10-31T10:05:00.000000Z",
        "user": {
          "id": 2,
          "name": "Jane Doe",
          "email": "jane@example.com"
        },
        "helpers": {
          "can_update": true,
          "can_delete": true
        }
      }
    ],
    "links": {
      "first": "http://localhost:8000/api/v1/tasks/10/comments?page=1",
      "last": "http://localhost:8000/api/v1/tasks/10/comments?page=1",
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

## 2. Create Task Comment
- **URL:** `/api/v1/tasks/{task}/comments`
- **Method:** `POST`
- **Auth:** Bearer Token (`auth:sanctum`)
- **Permissions:** Project members with write access (`OWNER`, `MANAGER`, `MEMBER`). `VIEWER` cannot comment.

### Request Body
```json
{
  "body": "PR #42 is ready for review."
}
```

### Validation Rules
| Field | Rules | Description |
|---|---|---|
| `body` | `required`, `string`, `max:5000` | Comment text content |

### Response (`key: success`, `code: 201`)
```json
{
  "key": "success",
  "code": 201,
  "msg": "Comment added successfully.",
  "data": {
    "id": 2,
    "task_id": 10,
    "user_id": 2,
    "body": "PR #42 is ready for review.",
    "created_at": "2026-10-31T10:10:00.000000Z",
    "updated_at": "2026-10-31T10:10:00.000000Z",
    "user": {
      "id": 2,
      "name": "Jane Doe",
      "email": "jane@example.com"
    },
    "helpers": {
      "can_update": true,
      "can_delete": true
    }
  },
  "response_status": {
    "error": false,
    "validation_errors": {}
  }
}
```

---

## 3. Update Comment
- **URL:** `/api/v1/comments/{comment}`
- **Method:** `PATCH`
- **Auth:** Bearer Token (`auth:sanctum`)
- **Permissions:** **Author-exclusive**. Only the comment author can update their comment content.

### Request Body
```json
{
  "body": "PR #42 is ready for review with all CI tests passing."
}
```

### Response (`key: success`, `code: 200`)
```json
{
  "key": "success",
  "code": 200,
  "msg": "Comment updated successfully.",
  "data": {
    "id": 2,
    "task_id": 10,
    "user_id": 2,
    "body": "PR #42 is ready for review with all CI tests passing.",
    "created_at": "2026-10-31T10:10:00.000000Z",
    "updated_at": "2026-10-31T10:15:00.000000Z"
  },
  "response_status": {
    "error": false,
    "validation_errors": {}
  }
}
```

---

## 4. Delete Comment
- **URL:** `/api/v1/comments/{comment}`
- **Method:** `DELETE`
- **Auth:** Bearer Token (`auth:sanctum`)
- **Permissions:** Comment author OR project `OWNER`/`MANAGER` (moderation).

### Response (`key: success`, `code: 200`)
```json
{
  "key": "success",
  "code": 200,
  "msg": "Comment deleted successfully.",
  "data": null,
  "response_status": {
    "error": false,
    "validation_errors": {}
  }
}
```

---

## 5. List Task Attachments
- **URL:** `/api/v1/tasks/{task}/attachments`
- **Method:** `GET`
- **Auth:** Bearer Token (`auth:sanctum`)
- **Permissions:** Any project member (`OWNER`, `MANAGER`, `MEMBER`, `VIEWER`).

### Response (`key: success`, `code: 200`)
```json
{
  "key": "success",
  "code": 200,
  "msg": "Attachments retrieved successfully.",
  "data": {
    "data": [
      {
        "id": 1,
        "task_id": 10,
        "uploaded_by": 2,
        "original_name": "spec-v1.pdf",
        "mime_type": "application/pdf",
        "size": 1048576,
        "created_at": "2026-10-31T10:00:00.000000Z",
        "uploader": {
          "id": 2,
          "name": "Jane Doe",
          "email": "jane@example.com"
        },
        "helpers": {
          "can_delete": true
        }
      }
    ],
    "links": {
      "first": "http://localhost:8000/api/v1/tasks/10/attachments?page=1",
      "last": "http://localhost:8000/api/v1/tasks/10/attachments?page=1",
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
*Note: Storage disk and raw server paths are strictly omitted from payloads.*

---

## 6. Upload File Attachment
- **URL:** `/api/v1/tasks/{task}/attachments`
- **Method:** `POST`
- **Auth:** Bearer Token (`auth:sanctum`)
- **Content-Type:** `multipart/form-data`
- **Permissions:** Project write members (`OWNER`, `MANAGER`, `MEMBER`). `VIEWER` is forbidden.

### Form Fields
| Field | Type | Rules | Description |
|---|---|---|---|
| `file` | Binary File | `required`, `file`, `max:10240`, `mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg,zip,txt` | Max 10MB; executable and script files strictly rejected. |

### Response (`key: success`, `code: 201`)
```json
{
  "key": "success",
  "code": 201,
  "msg": "Attachment uploaded successfully.",
  "data": {
    "id": 1,
    "task_id": 10,
    "uploaded_by": 2,
    "original_name": "spec-v1.pdf",
    "mime_type": "application/pdf",
    "size": 1048576,
    "created_at": "2026-10-31T10:00:00.000000Z",
    "helpers": {
      "can_delete": true
    }
  },
  "response_status": {
    "error": false,
    "validation_errors": {}
  }
}
```

---

## 7. Download Attachment File
- **URL:** `/api/v1/tasks/{task}/attachments/{attachment}/download`
- **Method:** `GET`
- **Auth:** Bearer Token (`auth:sanctum`)
- **Permissions:** Any member of the task's project (`OWNER`, `MANAGER`, `MEMBER`, `VIEWER`). Non-members / outsiders receive 403 Forbidden (preventing IDOR).

### Headers Returned
- `Content-Type`: MIME type of the original file (e.g. `application/pdf`).
- `Content-Disposition`: `attachment; filename="spec-v1.pdf"`.
- Body: Binary stream.

---

## 8. Delete Attachment
- **URL:** `/api/v1/tasks/{task}/attachments/{attachment}`
- **Method:** `DELETE`
- **Auth:** Bearer Token (`auth:sanctum`)
- **Permissions:** Uploader of the attachment OR project `OWNER`/`MANAGER`.

### Response (`key: success`, `code: 200`)
```json
{
  "key": "success",
  "code": 200,
  "msg": "Attachment deleted successfully.",
  "data": null,
  "response_status": {
    "error": false,
    "validation_errors": {}
  }
}
```

