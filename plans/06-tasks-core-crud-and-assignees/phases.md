# Implementation Phases: Task Core CRUD, Subtasks & Assignees

## Checklist

- [x] **Phase 1: Domain & Authorization Layer**
  - [x] Implement `App\Policies\TaskPolicy` (`view`, `create`, `update`, `delete`, `assign`).
  - [x] Register `TaskPolicy` in `AppServiceProvider`.
  - [x] Add domain helper methods on `Task` model (`isAssignee`, `isCreator`, `wouldCauseCycle`).

- [x] **Phase 2: Repository Layer (MODE_A)**
  - [x] Create `App\Repositories\Contracts\TaskRepositoryInterface`.
  - [x] Create `App\Repositories\Eloquent\TaskRepository`.
  - [x] Register binding in `App\Providers\RepositoryServiceProvider`.

- [x] **Phase 3: Form Requests & DTOs**
  - [x] Create `StoreTaskRequest` and `StoreTaskDTO`.
  - [x] Create `UpdateTaskRequest` and `UpdateTaskDTO`.
  - [x] Create `SyncAssigneesRequest` and `SyncAssigneesDTO`.
  - [x] Create `FilterTaskRequest` and `FilterTaskDTO`.

- [x] **Phase 4: Service Layer**
  - [x] Implement `App\Services\Task\TaskService`:
    - `getUserTasks(User $user, FilterTaskDTO $filters, int $perPage = 15)`
    - `getProjectTasks(Project $project, FilterTaskDTO $filters, int $perPage = 15)`
    - `createTask(Project $project, User $creator, StoreTaskDTO $dto): Task`
    - `updateTask(Task $task, UpdateTaskDTO $dto, User $actor): Task`
    - `deleteTask(Task $task, User $actor): void`
    - `syncAssignees(Task $task, SyncAssigneesDTO $dto, User $assigner): Task`

- [x] **Phase 5: API Resources**
  - [x] Create `App\Http\Resources\Api\TaskResource` (with `helpers` action permissions: `can_update`, `can_delete`, `can_assign`).

- [x] **Phase 6: Controllers & Routing**
  - [x] Implement `App\Http\Controllers\Api\V1\TaskController`.
  - [x] Create routes file `routes/guards/v1/tasks.php`.
  - [x] Register `tasks.php` inside `routes/api.php` under Sanctum authentication.

- [x] **Phase 7: OpenAPI Specification & Synchronizations**
  - [x] Update `App\Console\Commands\GenerateOpenApiSpec.php` with Task endpoints, tags, schemas, and parameters.
  - [x] Update `tests/Feature/OpenApiSpecTest.php` expected route list and protected operations.
  - [x] Re-generate `docs/openapi.json` via `php artisan openapi:generate`.

- [x] **Phase 8: Comprehensive Feature Testing**
  - [x] Create `tests/Feature/TaskTest.php` covering:
    - User can create task in project they belong to.
    - Subtask creation fails if `parent_task_id` is from a different project (422).
    - Subtask cannot be its own parent or cycle (422).
    - Assigning user who is not a project member fails validation (422).
    - Date validation rejects `due_at` before `start_at` (422).
    - Status change to `DONE` automatically sets `completed_at`.
    - Project `VIEWER` cannot create or update tasks (403).
    - Non-project member cannot view task (403).
    - Filtering by status, priority, and assignee works accurately.
    - Assignee sync replaces assignees and tracks `assigned_by` and `assigned_at`.
  - [x] Run test suite with zero regressions (76 passed, 400 assertions).

- [x] **Phase 9: Documentation & Memory Synchronization**
  - [x] Create client documentation `api-docs/frontend-docs/user/tasks.md`.
  - [x] Create Agent Memory `docs/memory/api/v1-tasks.md`.
  - [x] Create Agent Memory `docs/memory/features/06-tasks-core-crud-and-assignees.md`.
  - [x] Update `docs/memory/INDEX.md`.
