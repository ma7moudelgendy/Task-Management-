# Analytics Specifications & Formulas

## 1. Completion Percentage Formula
```
Total Tasks = T
Completed Tasks (DONE) = C
Cancelled Tasks (CANCELLED) = X
Active Pool = A = T - X

Progress % = A > 0 ? round((C / A) * 100, 2) : 0.0
```

## 2. Dashboard Payload Structure (`GET /api/v1/dashboard`)
```json
{
  "key": "success",
  "code": 200,
  "msg": "Dashboard metrics retrieved successfully.",
  "data": {
    "stats": {
      "status": {
        "todo": 4,
        "in_progress": 2,
        "in_review": 1,
        "done": 7
      },
      "total_assigned": 14,
      "overdue_count": 2
    },
    "overdue_tasks": [
      {
        "id": 12,
        "title": "Fix memory leak",
        "priority": "HIGH",
        "status": "IN_PROGRESS",
        "due_at": "2026-10-30T10:00:00Z",
        "project": {
          "id": 1,
          "name": "Backend Refactor"
        }
      }
    ],
    "upcoming_tasks": [
      {
        "id": 15,
        "title": "Implement search index",
        "priority": "MEDIUM",
        "status": "TODO",
        "due_at": "2026-11-04T12:00:00Z",
        "project": {
          "id": 1,
          "name": "Backend Refactor"
        }
      }
    ],
    "recent_activity": [
      {
        "id": 105,
        "action": "status_changed",
        "actor": {
          "id": 2,
          "name": "Jane Doe"
        },
        "project": {
          "id": 1,
          "name": "Backend Refactor"
        },
        "metadata": {
          "new_status": "DONE"
        },
        "created_at": "2026-10-31T14:00:00Z"
      }
    ]
  },
  "response_status": {
    "error": false,
    "validation_errors": {}
  }
}
```

## 3. Project Analytics Payload Structure (`GET /api/v1/projects/{project}/analytics`)
```json
{
  "key": "success",
  "code": 200,
  "msg": "Project analytics retrieved successfully.",
  "data": {
    "project_id": 1,
    "project_name": "Backend Refactor",
    "total_tasks": 20,
    "completed_tasks": 10,
    "cancelled_tasks": 2,
    "active_tasks": 18,
    "completion_percentage": 55.56,
    "overdue_count": 3,
    "status_distribution": {
      "TODO": 5,
      "IN_PROGRESS": 2,
      "IN_REVIEW": 1,
      "DONE": 10,
      "CANCELLED": 2
    },
    "priority_distribution": {
      "LOW": 4,
      "MEDIUM": 8,
      "HIGH": 6,
      "URGENT": 2
    },
    "assignee_workload": [
      {
        "user_id": 2,
        "name": "Jane Doe",
        "email": "jane@example.com",
        "open_tasks_count": 3,
        "completed_tasks_count": 5,
        "total_assigned": 8
      }
    ]
  },
  "response_status": {
    "error": false,
    "validation_errors": {}
  }
}
```
