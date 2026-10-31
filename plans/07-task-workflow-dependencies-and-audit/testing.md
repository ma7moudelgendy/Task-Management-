# Testing Plan: Task Workflow, Status Transitions, Dependencies & Audit Trail

## Test Structure
- Unit tests: `tests/Unit/CycleDetectionTest.php`
- Feature tests: `tests/Feature/TaskWorkflowTest.php`
Run with `DatabaseTransactions` on test database.

---

## 1. Unit Tests: Cycle Detection & Graph Algorithms
1. **Direct Cycle:**
   - Edge A -> B exists.
   - Attempting to add B -> A must return `wouldCreateCycle == true`.
2. **Transitive Cycle:**
   - Edges: A -> B, B -> C.
   - Attempting to add C -> A must return `wouldCreateCycle == true`.
3. **Deep Transitive Cycle:**
   - Edges: A -> B -> C -> D -> E.
   - Attempting to add E -> A must return `wouldCreateCycle == true`.
4. **Valid Diamond / Non-Cycle DAG:**
   - Edges: A -> B, A -> C, B -> D.
   - Adding C -> D must return `wouldCreateCycle == false`.

---

## 2. Feature Tests: State Machine & Completion Gates

### Journey A: Lifecycle Flow & Audit Trail
1. Create task in status `TODO`.
2. User transitions `TODO` -> `IN_PROGRESS` with reason "Started sprint work".
   - Assert HTTP 200, envelope `key: 'success'`, `code: 200`.
   - Assert `task_status_histories` has record (`old: TODO`, `new: IN_PROGRESS`, reason recorded).
3. User transitions `IN_PROGRESS` -> `IN_REVIEW`.
   - Assert status is updated.
4. User transitions `IN_REVIEW` -> `DONE`.
   - Assert `completed_at` is set to current timestamp.
5. User reopens task: `DONE` -> `IN_PROGRESS`.
   - Assert `completed_at` is set back to `null`.
6. Assert `GET /api/v1/tasks/{task}/history` returns 4 ordered audit trail records.

### Journey B: Illegal Transition Matrix Rejections
1. Attempt `TODO` -> `DONE` directly.
   - Assert HTTP 200, envelope `key: 'fail'`, `code: 422`.
2. Attempt `TODO` -> `IN_REVIEW` directly.
   - Assert HTTP 200, envelope `key: 'fail'`, `code: 422`.
3. Attempt `CANCELLED` -> `DONE` directly.
   - Assert HTTP 200, envelope `key: 'fail'`, `code: 422`.

### Journey C: Dependency Completion Blockers
1. Task A depends on Task B (`Task A -> depends on -> Task B`).
2. Task B is in status `IN_PROGRESS`.
3. Attempt to transition Task A to `DONE`.
   - Assert HTTP 200, envelope `key: 'fail'`, `code: 422`.
   - Assert error states prerequisite dependencies remain incomplete.
4. Transition Task B to `DONE`.
5. Transition Task A to `DONE`.
   - Assert HTTP 200, envelope `key: 'success'`, `code: 200`.

### Journey D: Dependency Boundary & Self-Dependency Validation
1. Attempt Task A depends on Task A.
   - Assert 422 (`A task cannot depend on itself.`).
2. Attempt Task A (Project 1) depends on Task C (Project 2).
   - Assert 422 (`Dependencies must be within the same project.`).
3. Delete dependency edge: Task A no longer blocked by Task B.

