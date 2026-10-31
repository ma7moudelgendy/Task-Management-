# Phase 06: Task Core CRUD, Subtasks & Assignees Plan

**Date:** 2026-10-30  
**Feature:** Task Core CRUD, Hierarchical Subtasks & Project-Member Assignees  
**Status:** In Progress  
**Architecture Mode:** MODE_A (`Controller -> FormRequest -> DTO -> Service -> Repository -> Model`)

---

## 1. Goal
Implement the core operational engine of TaskFlow according to `analyses/06-tasks-core-crud-and-assignees.md` and `laravel/prompt.md`:
- Full CRUD operations on tasks scoped within projects.
- Self-referential subtasks (`parent_task_id`) strictly enforced to share the exact same `project_id`, preventing circular parenting.
- Multiple project-member assignees (`task_assignees`), ensuring assignees are verified active members of the task's project.
- Advanced query filtering (`status`, `priority`, `assignee_id`, `due_before`, `due_after`), sorting (`due_at`, `priority`, `created_at`, `position`, `title`), and bounded pagination (default 15/20, max 100).
- Automatic `completed_at` timestamp management on status transition to `DONE`.
- Fine-grained role-based task policies (`TaskPolicy`):
  - `view`: caller is project member or system `ADMIN`.
  - `create`: caller holds `OWNER`, `MANAGER`, or `MEMBER` in project (`VIEWER` denied).
  - `update`: caller is project `OWNER`, `MANAGER`, task creator, or assignee.
  - `delete`: caller is project `OWNER`, `MANAGER`, or task creator.
  - `assign`: caller is project `OWNER` or `MANAGER`.
- Atomic assignee synchronization with assignment audit tracking (`assigned_by`, `assigned_at`).
- Standard TaskFlow HTTP 200 transport envelope (`jsonResponse`), centralized exception handling (`handleApi`), and OpenAPI 3.1.0 documentation.

---

## 2. Design Decisions & Architecture Mode (MODE_A)

### Layer Architecture
- **Repository Pattern (MODE_A):**
  - Contract: `App\Repositories\Contracts\TaskRepositoryInterface`
  - Implementation: `App\Repositories\Eloquent\TaskRepository`
  - Binding: Registered in `App\Providers\RepositoryServiceProvider`
- **Form Requests & DTOs:**
  - `StoreTaskRequest` -> `StoreTaskDTO`:
    - `title` (required, string, max:255)
    - `description` (nullable, string)
    - `priority` (sometimes, enum:TaskPriority: LOW, MEDIUM, HIGH, URGENT)
    - `parent_task_id` (nullable, exists:tasks,id, must belong to same project)
    - `start_at` (nullable, date)
    - `due_at` (nullable, date, after_or_equal:start_at)
    - `position` (sometimes, integer, min:0)
    - `assignee_ids` (nullable, array of user IDs, each must be an active project member)
  - `UpdateTaskRequest` -> `UpdateTaskDTO`:
    - `title`, `description`, `status` (enum:TaskStatus), `priority`, `parent_task_id`, `start_at`, `due_at`, `position`
    - Subtask validation: cannot be its own parent or cycle.
  - `SyncAssigneesRequest` -> `SyncAssigneesDTO`:
    - `assignee_ids` (required array of user IDs, all must be project members)
  - `FilterTaskRequest` -> `FilterTaskDTO`:
    - `status`, `priority`, `assignee_id`, `due_before`, `due_after`, `sort_by`, `sort_dir`, `per_page`
- **Service Layer:**
  - `App\Services\Task\TaskService`:
    - Encapsulates database transactions, assignee sync, validation of project membership, circular hierarchy checks, and completed_at transitions.
- **Controller:**
  - `App\Http\Controllers\Api\V1\TaskController`:
    - Extends `ApiController`, uses `HandlesApiExceptions`, `ApiResponse`, and `AuthorizesRequests`.
    - Returns HTTP 200 envelopes (`jsonResponse()`) with appropriate `key` and `code`.
- **Policy:**
  - `App\Policies\TaskPolicy`:
    - Registered in `AppServiceProvider`.
- **Resources:**
  - `App\Http\Resources\Api\TaskResource`:
    - Output fields: `id`, `project_id`, `parent_task_id`, `created_by`, `title`, `description`, `status`, `priority`, `start_at`, `due_at`, `completed_at`, `position`, `created_at`, `updated_at`.
    - Eager loaded relations: `assignees`, `creator`, `subtasks`, `project`.
    - `helpers` block: `can_update`, `can_delete`, `can_assign`.

---

## 3. API Changes

| Method | URI | Description | Auth |
|---|---|---|---|
| `GET` | `/api/v1/tasks` | List user's tasks across all member projects (filtered & paginated) | `BearerAuth` |
| `GET` | `/api/v1/projects/{project}/tasks` | List tasks within a specific project | `BearerAuth` |
| `POST` | `/api/v1/projects/{project}/tasks` | Create a new task within project | `BearerAuth` |
| `GET` | `/api/v1/tasks/{task}` | Get task details (with assignees & subtasks) | `BearerAuth` |
| `PATCH` | `/api/v1/tasks/{task}` | Update task details | `BearerAuth` |
| `DELETE` | `/api/v1/tasks/{task}` | Soft delete task | `BearerAuth` |
| `PUT` | `/api/v1/tasks/{task}/assignees` | Atomically sync assignees for task | `BearerAuth` |

---

## 4. Risks & Mitigations
- **Circular Subtask Hierarchy:** Ensure validation prevents `parent_task_id == task_id` and recursive ancestry loops.
- **Cross-Project Subtask Leakage:** Enforce that `parent_task_id` must have `project_id == target_project_id`.
- **Assigning Non-Project Members:** Always check `project_members` table before attaching assignees to prevent unauthorized user access to project information.
- **N+1 Queries:** Eager load `assignees`, `creator`, and `project` in repository queries.

