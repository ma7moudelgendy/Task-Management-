# Phase 6: Task Core CRUD, Subtasks & Assignees

> **Spec Reference:** Section 5.6 - 5.7 (`tasks`, `task_assignees`), Section 8.4 (Task Endpoints), Section 8.7 (Filtering/Sorting/Pagination), Section 10.3 (Assignment Rules), Section 13 (Phase 5)  
> **Primary Goal:** Implement complete Task CRUD, hierarchical subtasks, multiple project-member assignees, advanced query filtering, sorting, pagination, and eager-loading optimizations.

---

## High-Level Objective
Build the core operational engine of TaskFlow. Tasks must reside within projects, support self-referential subtasks, allow multiple assignees who are verified project members, and offer performant search/filtering without N+1 query overhead.

---

## Detailed Task Breakdown

### Task 6.1: Task Policy & Scoped Visibility
- [ ] **Step 1:** Create `app/Policies/TaskPolicy.php`:
  - `view(User $user, Task $task)`: Caller must be a member of the task's project or system `ADMIN`.
  - `create(User $user, Project $project)`: Caller must hold `OWNER`, `MANAGER`, or `MEMBER` in the project (`VIEWER` is denied).
  - `update(User $user, Task $task)`: Caller must be project `OWNER`, `MANAGER`, task creator, or an assignee.
  - `delete(User $user, Task $task)`: Caller must be project `OWNER`, `MANAGER`, or task creator.
  - `assign(User $user, Task $task)`: Caller must hold `OWNER` or `MANAGER` (or `MEMBER` if team settings permit self-assignment).

### Task 6.2: Form Requests & Business Validation
- [ ] **Step 1:** Create `app/Http/Requests/Task/StoreTaskRequest.php`:
  - `title`: `['required', 'string', 'max:255']`
  - `description`: `['nullable', 'string']`
  - `priority`: `['sometimes', Rule::in(['LOW', 'MEDIUM', 'HIGH', 'URGENT'])]`
  - `parent_task_id`: `['nullable', 'exists:tasks,id']`
  - `start_at`: `['nullable', 'date']`
  - `due_at`: `['nullable', 'date', 'after_or_equal:start_at']`
  - `assignee_ids`: `['nullable', 'array']`
  - `assignee_ids.*`: `['exists:users,id']`
  - Custom Rule: Verify `parent_task_id` belongs to the exact same `project_id`.
  - Custom Rule: Verify all `assignee_ids` are verified members of this `project_id`.
- [ ] **Step 2:** Create `app/Http/Requests/Task/UpdateTaskRequest.php`:
  - `title`: `['sometimes', 'string', 'max:255']`
  - `description`: `['nullable', 'string']`
  - `priority`: `['sometimes', Rule::in(['LOW', 'MEDIUM', 'HIGH', 'URGENT'])]`
  - `parent_task_id`: `['nullable', 'exists:tasks,id', Rule::notIn([$this->task->id])]`
  - `start_at`: `['nullable', 'date']`
  - `due_at`: `['nullable', 'date', 'after_or_equal:start_at']`
  - `position`: `['sometimes', 'integer', 'min:0']`
  - Custom Rule: Subtask cannot be its own ancestor or parent.
- [ ] **Step 3:** Create `app/Http/Requests/Task/SyncAssigneesRequest.php`:
  - `assignee_ids`: `['present', 'array']`
  - `assignee_ids.*`: `['exists:users,id']`
  - Custom Rule: All specified IDs must be members of the task's project.
- [ ] **Step 4:** Create `app/Http/Requests/Task/FilterTaskRequest.php`:
  - `status`: `['sometimes', Rule::in(['TODO', 'IN_PROGRESS', 'IN_REVIEW', 'DONE', 'CANCELLED'])]`
  - `priority`: `['sometimes', Rule::in(['LOW', 'MEDIUM', 'HIGH', 'URGENT'])]`
  - `assignee_id`: `['sometimes', 'exists:users,id']`
  - `due_before`: `['sometimes', 'date']`
  - `due_after`: `['sometimes', 'date']`
  - `sort_by`: `['sometimes', Rule::in(['due_at', 'priority', 'created_at', 'position', 'title'])]`
  - `sort_dir`: `['sometimes', Rule::in(['asc', 'desc'])]`
  - `per_page`: `['sometimes', 'integer', 'min:1', 'max:100']`

### Task 6.3: Task Service Layer
- [ ] **Step 1:** Create `app/Services/TaskService.php`:
  - `createTask(Project $project, User $creator, array $data): Task`:
    - Wraps in DB transaction.
    - Validates subtask parent belongs to `$project`.
    - Creates task with initial status `TODO`.
    - If `assignee_ids` provided: validates project membership, syncs with `assigned_by = $creator->id`, `assigned_at = now()`.
    - Dispatches `TaskAssigned` event for new assignees.
    - Returns newly created task with eager relations.
  - `updateTask(Task $task, array $data): Task`:
    - Validates no circular parenting (`parent_task_id != $task->id`).
    - Updates fields and returns fresh instance.
  - `syncAssignees(Task $task, array $assigneeIds, User $assigner): void`:
    - Validates all users belong to `$task->project_id`.
    - Computes diff (added vs removed assignees).
    - Syncs pivot table.
    - Dispatches `TaskAssigned` for newly added members.

### Task 6.4: API Resources
- [ ] **Step 1:** Create `app/Http/Resources/TaskResource.php`:
  - Exposes: `id`, `project_id`, `parent_task_id`, `title`, `description`, `status`, `priority`, `start_at`, `due_at`, `completed_at`, `position`, `created_at`, `updated_at`.
  - Conditional: `assignees` (`UserResource`), `subtasks` (`TaskResource` summary), `project` (`ProjectResource`).
- [ ] **Step 2:** Create `app/Http/Resources/TaskCollection.php` with standard metadata and pagination wrapper.

### Task 6.5: Task Controller Implementation
- [ ] **Step 1:** Create `app/Http/Controllers/Api/V1/TaskController.php`:
  - `index(FilterTaskRequest $request)`:
    - Scopes to tasks in projects where user is an active member.
    - Applies status, priority, assignee, and date range filters.
    - Eager loads `['assignees', 'project:id,name,team_id']`.
    - Applies bounded pagination (default 20, max 100).
  - `projectTasks(Project $project, FilterTaskRequest $request)`:
    - Scopes to `$project->tasks()`.
  - `store(StoreTaskRequest $request, Project $project)`:
    - Authorizes with policy.
    - Calls `TaskService::createTask()`, returns 201 with `TaskResource`.
  - `show(Task $task)`:
    - Authorizes with `view`.
    - Eager loads `['assignees', 'subtasks', 'dependencies', 'project']`.
    - Returns `TaskResource`.
  - `update(UpdateTaskRequest $request, Task $task)`:
    - Authorizes and updates task attributes.
  - `destroy(Task $task)`:
    - Authorizes and soft-deletes task.
  - `syncAssignees(SyncAssigneesRequest $request, Task $task)`:
    - Replaces assignees list atomically.

### Task 6.6: Feature Tests for Tasks
- [ ] **Step 1:** Create `tests/Feature/TaskTest.php`.
- [ ] **Step 2:** Test scenarios:
  - User can create task in a project they belong to.
  - Subtask creation fails if `parent_task_id` is from a different project (422).
  - Assigning a user who is not a project member fails validation (422).
  - Task cannot be its own parent (422).
  - Date validation rejects `due_at` before `start_at` (422).
  - Filter by `status` and `priority` correctly restricts returned result set.
  - Assignee replacement correctly syncs pivot table.
- [ ] **Step 3:** Run `php artisan test --filter=TaskTest`.

---

## Acceptance Criteria
- [x] Full CRUD operations for tasks and subtasks within permitted projects.
- [x] Subtasks are strictly enforced to share the parent's project ID.
- [x] Only active members of the project can be assigned to tasks.
- [x] High-performance index-backed filtering and bounded pagination (max 100).
- [x] All task feature tests pass cleanly.

