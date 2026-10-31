# Feature Memory: 06 - Tasks Core CRUD, Subtasks & Assignees

## Context
Implemented the core operational engine of TaskFlow: project-scoped tasks, hierarchical subtasks, multiple project-member assignees, advanced query filtering and sorting, completion timestamps, and role-based policies.

## Implementation Details
- **Architecture Mode:** MODE_A (`Controller -> FormRequest -> DTO -> Service -> Repository -> Model`).
- **Policy (`TaskPolicy`):**
  - `view`: caller is project member or system `ADMIN`.
  - `create`: caller holds `OWNER`, `MANAGER`, or `MEMBER` in project (`VIEWER` denied).
  - `update`: caller is project `OWNER`, `MANAGER`, creator, or assignee.
  - `delete`: caller is project `OWNER`, `MANAGER`, or creator.
  - `assign`: caller is project `OWNER` or `MANAGER`.
- **Repository (`TaskRepository`):**
  - Implements `TaskRepositoryInterface`, bound in `RepositoryServiceProvider`.
  - Filtering by `status`, `priority`, `assignee_id`, `due_before`, `due_after`.
  - Sorting by `due_at`, `priority`, `created_at`, `position`, `title`.
  - Bounded pagination (default 15, max 100).
- **Service (`TaskService`):**
  - Manages atomic transactions.
  - Verifies parent tasks belong to the same project.
  - Prevents circular dependency cycles on updates.
  - Enforces project membership for all assigned users.
  - Records activity log entries (`created_task`, `updated_task`, `deleted_task`, `synced_task_assignees`).
- **Resource (`TaskResource`):**
  - Emits task fields, `helpers` with action permissions (`can_update`, `can_delete`, `can_assign`), and conditional relations.
- **Routes:** Mounted in `routes/guards/v1/tasks.php` and loaded in `routes/api.php` under Sanctum middleware.
- **Verification:** 18 feature test cases in `tests/Feature/TaskTest.php`, full test suite 76 tests passing (400 assertions).

