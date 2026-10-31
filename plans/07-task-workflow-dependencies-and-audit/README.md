# Phase 07: Task Workflow, Status Transitions, Dependencies & Audit Trail Plan

**Date:** 2026-10-31  
**Feature:** Task Lifecycle Workflow Engine, DAG Cycle Detection, Dependency Completion Gates, and Audit Trail  
**Status:** In Progress  
**Architecture Mode:** MODE_A (`Controller -> FormRequest -> DTO -> Service -> Repository -> Model`)

---

## 1. Goal
Transform TaskFlow's task engine into a robust, lifecycle-governed workflow system:
- **State Machine Transitions:** Enforce defined lifecycle transitions:
  - `TODO` -> `IN_PROGRESS`, `CANCELLED`
  - `IN_PROGRESS` -> `IN_REVIEW`, `TODO`, `CANCELLED`
  - `IN_REVIEW` -> `DONE`, `IN_PROGRESS`, `CANCELLED`
  - `DONE` -> `IN_PROGRESS` (reopen)
  - `CANCELLED` -> `TODO` (reopen)
  - Disallow invalid, skipped, or illegal transitions.
- **Dependency Engine & DAG Validation:**
  - Tasks can declare prerequisite tasks (`task_dependencies`) that must be finished before work or completion can proceed.
  - Self-dependencies are forbidden (`task_id !== depends_on_task_id`).
  - Project boundary isolation: dependencies must share the exact same `project_id`.
  - Deep Cycle Detection: Depth-First Search (DFS) traversal blocks any direct (A -> B -> A) or transitive (A -> B -> C -> A) circular deadlocks across arbitrary graph depths.
- **Completion Blockers:**
  - A task cannot transition to `DONE` if any prerequisite dependency is incomplete (`status != DONE`).
  - A task cannot transition to `DONE` if active subtasks remain incomplete.
- **Audit Trails & Immutable History:**
  - Every status change writes an immutable audit record to `task_status_histories` (`task_id`, `changed_by`, `old_status`, `new_status`, `reason`, `created_at`).
  - Automatic `completed_at` timestamping upon entering `DONE`, reset upon reopening.
  - Activity log audit records (`status_changed`, `added_dependency`, `removed_dependency`).
- **Standard Envelope & MODE_A:**
  - Transport HTTP Status is ALWAYS 200 with structured JSON envelopes (`key`, `code`, `msg`, `data`, `response_status`).
  - Centralized exception handling (`handleApi`), no manual `try/catch`.
  - OpenAPI 3.1.0 specification and test suite verification.

---

## 2. Design Decisions & Architecture Mode (MODE_A)

### Layer Architecture
- **Controllers:**
  - `App\Http\Controllers\Api\V1\TaskWorkflowController`:
    - `changeStatus(ChangeTaskStatusRequest $request, Task $task)`
    - `addDependency(AddDependencyRequest $request, Task $task)`
    - `removeDependency(Task $task, Task $dependency)`
    - `dependencies(Task $task)`
    - `history(Task $task)`
- **Form Requests & DTOs:**
  - `ChangeTaskStatusRequest` -> `ChangeTaskStatusDTO` (`status`: enum TaskStatus, `reason`: nullable string max 500)
  - `AddDependencyRequest` -> `AddDependencyDTO` (`depends_on_task_id`: required integer exists:tasks,id)
- **Services:**
  - `App\Services\Task\DependencyService`:
    - `addDependency(Task $task, int $prerequisiteTaskId, User $actor): void`
    - `removeDependency(Task $task, int $prerequisiteTaskId, User $actor): void`
    - `hasUnfinishedDependencies(Task $task): bool`
    - `wouldCreateCycle(int $blockedTaskId, int $prerequisiteTaskId): bool` (DFS traversal)
  - `App\Services\Task\TaskWorkflowService`:
    - `changeStatus(Task $task, ChangeTaskStatusDTO $dto, User $actor): Task`
    - `getHistory(Task $task, int $perPage = 15): LengthAwarePaginator`
    - `getDependencies(Task $task): array`
- **Repository Contracts & Implementations (MODE_A):**
  - `App\Repositories\Contracts\TaskDependencyRepositoryInterface` -> `App\Repositories\Eloquent\TaskDependencyRepository`
  - `App\Repositories\Contracts\TaskStatusHistoryRepositoryInterface` -> `App\Repositories\Eloquent\TaskStatusHistoryRepository`
  - Registered in `RepositoryServiceProvider`
- **Resources:**
  - `App\Http\Resources\Api\TaskStatusHistoryResource`
  - `App\Http\Resources\Api\TaskDependencyResource`
- **Policy:**
  - `TaskPolicy` extensions:
    - `changeStatus`: project OWNER, MANAGER, creator, or assignee.
    - `manageDependencies`: project OWNER, MANAGER, or creator.

---

## 3. API Changes

| Method | URI | Summary | Auth |
|---|---|---|---|
| `POST` | `/api/v1/tasks/{task}/status` | Transition task status with reason & validation gates | BearerAuth |
| `GET` | `/api/v1/tasks/{task}/dependencies` | Get prerequisites and dependents for a task | BearerAuth |
| `POST` | `/api/v1/tasks/{task}/dependencies` | Add prerequisite dependency with cycle detection | BearerAuth |
| `DELETE` | `/api/v1/tasks/{task}/dependencies/{dependency}` | Remove prerequisite dependency | BearerAuth |
| `GET` | `/api/v1/tasks/{task}/history` | Get paginated status audit trail | BearerAuth |

---

## 4. Risks & Mitigations
- **Graph Traversal Performance on Large DAGs:** Traversal uses a visited set and bounds recursion within the project scope.
- **Race Conditions in Status Gate:** Wrap status check and updates inside `DB::transaction()` with pessimistic locking where appropriate.

