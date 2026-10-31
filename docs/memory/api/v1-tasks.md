# API Memory: `/api/v1/tasks` & `/api/v1/projects/{project}/tasks`

## Endpoints

| Method | Path | Summary | Role/Auth | Envelope |
|---|---|---|---|---|
| `GET` | `/api/v1/tasks` | List user accessible tasks across projects | BearerAuth | 200 `key: success` |
| `GET` | `/api/v1/projects/{project}/tasks` | List project tasks with filters | BearerAuth | 200 `key: success` |
| `POST` | `/api/v1/projects/{project}/tasks` | Create task / subtask in project | BearerAuth (OWNER/MANAGER/MEMBER) | 200 `key: success`, `code: 201` |
| `GET` | `/api/v1/tasks/{task}` | View task with assignees & subtasks | BearerAuth (Project member/ADMIN) | 200 `key: success` |
| `PATCH` | `/api/v1/tasks/{task}` | Update task details & status | BearerAuth (OWNER/MANAGER/Creator/Assignee) | 200 `key: success` |
| `DELETE` | `/api/v1/tasks/{task}` | Soft delete task | BearerAuth (OWNER/MANAGER/Creator) | 200 `key: success` |
| `PUT` | `/api/v1/tasks/{task}/assignees` | Atomically sync assignees | BearerAuth (OWNER/MANAGER) | 200 `key: success` |

## Validation & Business Rules
- `parent_task_id`: If provided, must belong to the exact same `project_id`. Self-parenting or circular ancestry throws validation error (422).
- `assignee_ids`: Every user ID must be an active member of `project_members` for the task's project.
- `status`: Default is `TODO`. Transitioning to `DONE` automatically sets `completed_at = now()`. Transitioning out of `DONE` resets `completed_at = null`.
- `due_at`: Must be `>= start_at` when both are present.
- Transport is strictly HTTP 200 with structured JSON envelopes (`HandlesApiExceptions`).

