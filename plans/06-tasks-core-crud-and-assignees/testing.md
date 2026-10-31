# Testing Plan: Task Core CRUD, Subtasks & Assignees

## Test Structure
Tests will be placed in `tests/Feature/TaskTest.php` and run using Laravel test suite with real database assertions and `DatabaseTransactions`.

---

## 1. End-to-End User Journeys

### Journey A: Task Lifecycle & Subtask Hierarchy
1. User A (team & project `OWNER`) creates a project "Core App".
2. User A creates a root task "Design Architecture" (`status: TODO`, `priority: HIGH`, `start_at: 2026-11-01`, `due_at: 2026-11-10`).
   - Assert HTTP 200, envelope `key: 'success'`, `code: 201`.
   - Assert task recorded in `tasks` table with `project_id`, `created_by = User A`.
3. User A creates a subtask "Database Schema Design" with `parent_task_id = root_task->id`.
   - Assert HTTP 200, envelope `key: 'success'`, `code: 201`.
   - Assert `parent_task_id` is set correctly.
4. User A updates subtask status to `DONE`.
   - Assert `completed_at` is populated automatically.
5. User A views root task.
   - Assert subtasks are loaded and included in response.
6. User A soft-deletes the subtask.
   - Assert `deleted_at` is set and subtask is excluded from standard lists.

### Journey B: Cross-Project & Hierarchy Edge Cases
1. User A creates Project 1 and Project 2.
2. User A creates Task 1 in Project 1.
3. User A attempts to create Task 2 in Project 2 with `parent_task_id = Task 1->id`.
   - Assert HTTP 200, envelope `key: 'fail'`, `code: 422`.
   - Assert error states `parent_task_id` belongs to a different project.
4. User A attempts to update Task 1 with `parent_task_id = Task 1->id` (self-parenting).
   - Assert HTTP 200, envelope `key: 'fail'`, `code: 422`.

### Journey C: Project-Member Assignee Enforcement & Sync
1. User A creates project "TaskFlow".
2. User B is added to project as `MEMBER`.
3. User C is NOT added to project (or only in parent team).
4. User A creates a task attempting to assign User C (`assignee_ids: [User C->id]`).
   - Assert HTTP 200, envelope `key: 'fail'`, `code: 422`.
   - Assert message indicates user is not an active member of this project.
5. User A creates task assigning User B (`assignee_ids: [User B->id]`).
   - Assert HTTP 200, envelope `key: 'success'`, `code: 201`.
   - Assert `task_assignees` has record for User B with `assigned_by = User A->id`.
6. User A syncs assignees with `PUT /api/v1/tasks/{task}/assignees` to add User A and remove User B.
   - Assert `task_assignees` reflects the new set atomically.

### Journey D: Role-Based Authorization
1. User D is added to project as `VIEWER`.
2. User D attempts to create task -> 403 Forbidden (`key: 'unauthorized'`).
3. User D attempts to update task -> 403 Forbidden.
4. User D attempts to delete task -> 403 Forbidden.
5. User E (non-member of project) attempts to view task -> 403 Forbidden.

---

## 2. Query Filtering & Sorting Tests
- Filter by `status` (e.g. `TODO`, `DONE`).
- Filter by `priority` (e.g. `HIGH`, `URGENT`).
- Filter by `assignee_id`.
- Sort by `due_at` asc / desc.
- Pagination bounds (max 100).

