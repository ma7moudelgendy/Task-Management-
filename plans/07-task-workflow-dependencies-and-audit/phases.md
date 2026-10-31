# Implementation Phases: Task Workflow, Status Transitions, Dependencies & Audit Trail

## Checklist

- [x] **Phase 1: Domain, Policy & Invariants Layer**
  - [x] Add state machine transition map & validation methods in domain enum/service.
  - [x] Extend `TaskPolicy` with `changeStatus`, `manageDependencies`.
  - [x] Add helper relations / query scopes on `Task` and `TaskDependency`.

- [x] **Phase 2: Repository Layer (MODE_A)**
  - [x] Create `App\Repositories\Contracts\TaskDependencyRepositoryInterface`.
  - [x] Create `App\Repositories\Eloquent\TaskDependencyRepository`.
  - [x] Create `App\Repositories\Contracts\TaskStatusHistoryRepositoryInterface`.
  - [x] Create `App\Repositories\Eloquent\TaskStatusHistoryRepository`.
  - [x] Register bindings in `App\Providers\RepositoryServiceProvider`.

- [x] **Phase 3: Form Requests & DTOs**
  - [x] Create `ChangeTaskStatusRequest` and `ChangeTaskStatusDTO`.
  - [x] Create `AddDependencyRequest` and `AddDependencyDTO`.

- [x] **Phase 4: Service Layer**
  - [x] Implement `App\Services\Task\DependencyService`:
    - `wouldCreateCycle(int $blockedTaskId, int $prerequisiteTaskId): bool` (DFS traversal)
    - `addDependency(Task $task, int $prerequisiteTaskId, User $actor): void`
    - `removeDependency(Task $task, int $prerequisiteTaskId, User $actor): void`
    - `hasUnfinishedDependencies(Task $task): bool`
  - [x] Implement `App\Services\Task\TaskWorkflowService`:
    - Transition matrix validation.
    - Prerequisite & subtask completion blocker checks.
    - Atomic transaction writing `task_status_histories` and `activity_logs`.
    - `getHistory(Task $task, int $perPage = 15)`
    - `getDependencies(Task $task)`

- [x] **Phase 5: API Resources**
  - [x] Create `App\Http\Resources\Api\TaskStatusHistoryResource`.

- [x] **Phase 6: Controllers & Routing**
  - [x] Implement `App\Http\Controllers\Api\V1\TaskWorkflowController`.
  - [x] Mount workflow routes in `routes/guards/v1/tasks.php`.

- [x] **Phase 7: Unit & Feature Testing**
  - [x] Create `tests/Unit/CycleDetectionTest.php` (direct cycle, transitive cycle, valid DAG diamond).
  - [x] Create `tests/Feature/TaskWorkflowTest.php`:
    - Valid state transition creates audit history row.
    - Illegal state transition rejected with 422.
    - Completion blocked when dependency is incomplete.
    - Completion blocked when subtask is incomplete.
    - Reopening task clears `completed_at`.
    - Self-dependency rejected (422).
    - Cross-project dependency rejected (422).
    - Circular dependency rejected (422).
    - Removing dependency unlocks completion.
    - Paginated audit history listing works.

- [x] **Phase 8: OpenAPI Specification & Synchronizations**
  - [x] Update `App\Console\Commands\GenerateOpenApiSpec.php` with workflow endpoints, tags, schemas, and responses.
  - [x] Update `tests/Feature/OpenApiSpecTest.php` expected route list and protected operations.
  - [x] Regenerate `docs/openapi.json` (24 API paths).

- [x] **Phase 9: Documentation & Memory Synchronization**
  - [x] Create client documentation `api-docs/frontend-docs/user/task-workflow.md`.
  - [x] Create Agent Memory `docs/memory/api/v1-task-workflow.md`.
  - [x] Create Agent Memory `docs/memory/features/07-task-workflow-dependencies-and-audit.md`.
  - [x] Update `docs/memory/INDEX.md`.
