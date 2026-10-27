# Phase 7: Task Workflow, Status Transitions, Dependencies & Audit Trail

> **Spec Reference:** Section 5.8 (`task_dependencies`), Section 5.11 (`task_status_histories`), Section 5.12 (`activity_logs`), Section 8.4 (Workflow Endpoints), Section 10.1 - 10.2 (Business Logic & Cycles), Section 13 (Phase 6)  
> **Primary Goal:** Implement strict task status transitions, dependency DAG validation with cycle detection, completion blockers, immutable status histories, and activity logging.

---

## High-Level Objective
Transform the basic task CRUD into a workflow engine. Tasks progress through defined lifecycle states, cannot be completed if prerequisite tasks remain unfinished, cannot participate in circular dependency deadlocks, and record every status transition in an audit table.

---

## Detailed Task Breakdown

### Task 7.1: Status Transition State Machine & Validation
- [ ] **Step 1:** Create an enum or value object `app/Enums/TaskStatus.php`:
  ```php
  enum TaskStatus: string {
      case TODO = 'TODO';
      case IN_PROGRESS = 'IN_PROGRESS';
      case IN_REVIEW = 'IN_REVIEW';
      case DONE = 'DONE';
      case CANCELLED = 'CANCELLED';
  }
  ```
- [ ] **Step 2:** Define allowed transition matrix in `TaskWorkflowService`:
  - `TODO` -> `IN_PROGRESS`, `CANCELLED`
  - `IN_PROGRESS` -> `IN_REVIEW`, `TODO`, `CANCELLED`
  - `IN_REVIEW` -> `DONE`, `IN_PROGRESS`, `CANCELLED`
  - `DONE` -> `IN_PROGRESS` (reopen)
  - `CANCELLED` -> `TODO` (reopen)
- [ ] **Step 3:** Create `app/Http/Requests/Task/ChangeTaskStatusRequest.php`:
  - `status`: `['required', Rule::enum(TaskStatus::class)]`
  - `reason`: `['nullable', 'string', 'max:500']`

### Task 7.2: Task Dependency Engine & Cycle Detection (DAG)
- [ ] **Step 1:** Create `app/Services/DependencyService.php`:
  - `addDependency(Task $task, Task $prerequisite): void`:
    - **Check 1: Self-dependency:** If `$task->id === $prerequisite->id`, throw `ValidationException("A task cannot depend on itself.")`.
    - **Check 2: Project boundary:** If `$task->project_id !== $prerequisite->project_id`, throw `ValidationException("Dependencies must be within the same project.")`.
    - **Check 3: Cycle Detection (DFS):** Check if `$prerequisite` already depends on `$task` directly or transitively.
      ```php
      public function wouldCreateCycle(int $blockedTaskId, int $prerequisiteTaskId): bool
      {
          // If prerequisite is already reachable from blockedTask, adding blockedTask -> prerequisite is a cycle!
          // We run a Breadth-First or Depth-First Search starting from $prerequisiteTaskId.
          // If we ever encounter $blockedTaskId, a cycle is detected.
      }
      ```
    - **Check 4: Persistence:** Insert edge in `task_dependencies` inside a transaction.
  - `removeDependency(Task $task, Task $prerequisite): void`:
    - Detaches the dependency record.
  - `hasUnfinishedDependencies(Task $task): bool`:
    - Queries `$task->dependencies()->where('status', '!=', TaskStatus::DONE->value)->exists()`.

### Task 7.3: Status Transition Service & Audit History
- [ ] **Step 1:** In `app/Services/TaskWorkflowService.php`:
  - Implement `changeStatus(Task $task, TaskStatus $newStatus, User $user, ?string $reason = null): Task`:
    - **Guard 1: Transition Validity:** Verify `$newStatus` is a permitted transition from `$task->status`.
    - **Guard 2: Dependency Gate:** If `$newStatus === TaskStatus::DONE`, invoke `DependencyService::hasUnfinishedDependencies($task)`. If true, throw `DomainException("Cannot complete task because prerequisite dependencies remain incomplete.")`.
    - **Guard 3: Subtask Gate (Optional Rule):** Verify whether incomplete subtasks exist.
    - **Atomic Transaction:**
      ```php
      DB::transaction(function () use ($task, $newStatus, $user, $reason) {
          $oldStatus = $task->status;
          $task->status = $newStatus->value;
          $task->completed_at = ($newStatus === TaskStatus::DONE) ? now() : null;
          $task->save();

          // Write audit trail
          $task->statusHistories()->create([
              'changed_by' => $user->id,
              'old_status' => $oldStatus,
              'new_status' => $newStatus->value,
              'reason' => $reason,
              'created_at' => now(),
          ]);

          // Write activity log
          ActivityLog::create([
              'actor_id' => $user->id,
              'project_id' => $task->project_id,
              'subject_type' => Task::class,
              'subject_id' => $task->id,
              'action' => 'status_changed',
              'metadata' => [
                  'old' => $oldStatus,
                  'new' => $newStatus->value,
                  'reason' => $reason,
              ],
              'created_at' => now(),
          ]);
      });
      ```
    - Dispatch `TaskStatusChanged` event.
    - Return refreshed `$task`.

### Task 7.4: Controllers & API Endpoints
- [ ] **Step 1:** In `TaskController.php` or dedicated `TaskWorkflowController.php`:
  - `changeStatus(ChangeTaskStatusRequest $request, Task $task)`: Calls `TaskWorkflowService::changeStatus()`.
  - `addDependency(Request $request, Task $task)`: Validates `depends_on_task_id`, calls `DependencyService::addDependency()`.
  - `removeDependency(Task $task, Task $dependency)`: Removes dependency edge.
  - `history(Task $task)`: Returns paginated `TaskStatusHistoryResource` collection ordered by `created_at desc`.

### Task 7.5: Automated Feature & Unit Tests
- [ ] **Step 1:** Create `tests/Unit/CycleDetectionTest.php`:
  - Direct cycle (A -> B, then B -> A) is detected and blocked.
  - Transitive cycle (A -> B -> C, then C -> A) is detected and blocked.
  - Valid tree/DAG (A -> B, A -> C, B -> D, C -> D) is permitted.
- [ ] **Step 2:** Create `tests/Feature/TaskWorkflowTest.php`:
  - Attempting to complete a task with an uncompleted dependency returns 422 / 409 error.
  - Completing task sets `completed_at` timestamp.
  - Reopening task clears `completed_at`.
  - Every status change creates an immutable row in `task_status_histories`.
  - Adding a cross-project dependency fails validation.
- [ ] **Step 3:** Run `php artisan test --filter=Workflow`.

---

## Acceptance Criteria
- [x] Status changes strictly adhere to state machine transitions.
- [x] Completion is blocked if prerequisite dependencies are not `DONE`.
- [x] DFS cycle detection prevents circular dependencies across all graph depths.
- [x] Status histories and activity logs are atomically recorded in the database.
- [x] All cycle detection and workflow tests pass.

