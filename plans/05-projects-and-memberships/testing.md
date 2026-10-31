# Testing Plan: Projects & Project Memberships

## Test Structure
Tests will be placed in `tests/Feature/ProjectTest.php` and run using Laravel test suite with real database assertions and `DatabaseTransactions`.

---

## 1. End-to-End User Journeys

### Journey A: Project Lifecycle & Isolation
1. User A creates team "Engineering".
2. User B is added to team "Engineering" as `MEMBER`.
3. User A creates project "Project Phoenix" under "Engineering" (`due_date >= start_date`).
   - Assert HTTP 200, envelope `key: 'success'`, `code: 201`.
   - Assert User A is attached to `project_members` as `OWNER`.
   - Assert activity log recorded `created_project`.
4. User B attempts to GET `/api/v1/projects/{phoenix_id}`.
   - Assert HTTP 200, envelope `key: 'unauthorized'`, `code: 403` (Membership Separation enforced).
5. User A adds User B to "Project Phoenix" as `MEMBER`.
   - User B can now view "Project Phoenix".
6. User A updates project status to `ON_HOLD`.
   - Assert project status updated in database.
7. User A deletes project.
   - Assert project is soft deleted.

### Journey B: Project Membership, Roles & Task Preservation
1. User A (project `OWNER`) adds User B (team member) as `MANAGER` to project.
2. User C (not in parent team) is attempted to be added to project.
   - Assert HTTP 200, envelope `key: 'fail'`, `code: 422`, error message explains user is not a member of the parent team.
3. User C is added to parent team first, then successfully added to project as `MEMBER`.
4. Task is created in project and assigned to User C.
5. User A demotes self when being the sole owner.
   - Assert HTTP 200, envelope `key: 'fail'`, `code: 422` ("Project must retain at least one owner.").
6. User A removes User C from project.
   - User C detached from `project_members`.
   - User C attempts to view project -> 403 Forbidden.
   - Task assigned to User C remains intact (`task_assignees` record preserved for audit/history).

---

## 2. Negative & Edge Case Tests
- Non-team owner/manager cannot create project in team (403).
- Invalid date ranges (`due_date` before `start_date`) return 422.
- Duplicate member addition returns 422 ("User is already a member of this project.").
- Non-project member cannot access `/projects/{project}/members` (403).
- Project `VIEWER` or `MEMBER` cannot update project settings or manage members (403).
- Sole project `OWNER` cannot be removed (422).

