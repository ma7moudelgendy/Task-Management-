# API Memory: `/api/v1/tasks/{task}/status`, `dependencies`, and `history`

## Endpoints

| Method | Path | Summary | Role/Auth | Envelope |
|---|---|---|---|---|
| `POST` | `/api/v1/tasks/{task}/status` | Advance or reopen task status | BearerAuth (OWNER/MANAGER/Creator/Assignee) | 200 `key: success` |
| `GET` | `/api/v1/tasks/{task}/dependencies` | List prerequisites and dependents | BearerAuth (Project member/ADMIN) | 200 `key: success` |
| `POST` | `/api/v1/tasks/{task}/dependencies` | Add prerequisite dependency edge | BearerAuth (OWNER/MANAGER/Creator) | 200 `key: success`, `code: 201` |
| `DELETE` | `/api/v1/tasks/{task}/dependencies/{dependency}` | Remove prerequisite dependency | BearerAuth (OWNER/MANAGER/Creator) | 200 `key: success` |
| `GET` | `/api/v1/tasks/{task}/history` | Paginated status change audit trail | BearerAuth (Project member/ADMIN) | 200 `key: success` |

## Business Invariants & Rules
- **State Machine Transitions:**
  - `TODO` -> `IN_PROGRESS`, `CANCELLED`
  - `IN_PROGRESS` -> `IN_REVIEW`, `TODO`, `CANCELLED`
  - `IN_REVIEW` -> `DONE`, `IN_PROGRESS`, `CANCELLED`
  - `DONE` -> `IN_PROGRESS` (reopening task sets `completed_at` to null)
  - `CANCELLED` -> `TODO`
- **Completion Blockers:**
  - Task cannot enter `DONE` if any prerequisite dependency is not `DONE`.
  - Task cannot enter `DONE` if active subtasks remain incomplete.
- **DAG Cycle Detection:**
  - Self-dependency rejected (`task_id != depends_on_task_id`).
  - Project boundary isolation strictly enforced.
  - DFS graph search blocks any transitive cycle loop across all depths.
- **Audit Logging:** Every transition writes to `task_status_histories` and creates an `ActivityLog` entry.

