# Feature Memory: 07 - Task Workflow, Status Transitions, Dependencies & Audit Trail

## Context
Implemented the task workflow engine: strict lifecycle state-machine, completion gating, DAG cycle detection using Depth-First Search, prerequisite dependency management, immutable status histories, and activity logging.

## Implementation Details
- **Architecture Mode:** MODE_A (`Controller -> FormRequest -> DTO -> Service -> Repository -> Model`).
- **State Machine:**
  - `TaskStatus` enum provides `allowedTransitions()` and `canTransitionTo(TaskStatus $target): bool`.
  - Allowed paths: `TODO` -> `IN_PROGRESS`, `CANCELLED`; `IN_PROGRESS` -> `IN_REVIEW`, `TODO`, `CANCELLED`; `IN_REVIEW` -> `DONE`, `IN_PROGRESS`, `CANCELLED`; `DONE` -> `IN_PROGRESS`; `CANCELLED` -> `TODO`.
- **Completion Blockers:**
  - `TaskWorkflowService::changeStatus()` enforces two completion gates before setting `DONE`:
    1. Prerequisite dependencies gate (`hasUnfinishedDependencies`).
    2. Subtasks gate (`subtasks()->whereNotIn('status', ['DONE', 'CANCELLED'])->exists()`).
- **DAG Cycle Detection:**
  - `DependencyService::wouldCreateCycle(int $blockedTaskId, int $prerequisiteTaskId): bool` uses Depth-First Search (DFS) with visited set to verify reachability.
  - Rejects direct cycles, transitive cycles, deep chains, and self-dependencies.
  - Enforces project boundary scoping (no cross-project dependencies).
- **Audit Trails:**
  - `TaskStatusHistoryRepository` creates records in `task_status_histories`.
  - `ActivityLog` entries created for `status_changed`, `added_dependency`, `removed_dependency`.
- **Repositories & Service Provider:**
  - `TaskDependencyRepository` and `TaskStatusHistoryRepository` bound in `RepositoryServiceProvider`.
- **Verification:**
  - Unit tests: `tests/Unit/CycleDetectionTest.php` (5 tests passing).
  - Feature tests: `tests/Feature/TaskWorkflowTest.php` (12 tests passing).
  - OpenApi tests: `tests/Feature/OpenApiSpecTest.php` (5 tests passing, 24 paths documented).
  - Total application test suite: 93 passed (501 assertions, 0 failures).

