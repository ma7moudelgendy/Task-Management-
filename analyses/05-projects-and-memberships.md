# Phase 5: Projects & Project Memberships Module

> **Spec Reference:** Section 2 (Roles & Permissions), Section 5.4 - 5.5 (`projects`, `project_members`), Section 8.3 (Endpoints), Section 13 (Phase 4 & 5)  
> **Primary Goal:** Implement project lifecycle management, team scoping, project-level role permissions (`OWNER`, `MANAGER`, `MEMBER`, `VIEWER`), and strict project isolation.

---

## High-Level Objective
Projects belong to teams and act as the execution space for tasks. A critical security and design invariant of TaskFlow is **Membership Separation**: being on a team does **not** grant automatic access to every project within that team. Access is controlled strictly via `project_members`.

---

## Detailed Task Breakdown

### Task 5.1: Project Policy & Scoped Access Control
- [ ] **Step 1:** Create `app/Policies/ProjectPolicy.php`:
  - `view(User $user, Project $project)`: Caller must be a member of `project_members` (any role) or system `ADMIN`.
  - `create(User $user, Team $team)`: Caller must be a member of the parent team with `OWNER` or `MANAGER` role.
  - `update(User $user, Project $project)`: Caller must hold `OWNER` or `MANAGER` in the project.
  - `delete(User $user, Project $project)`: Caller must hold `OWNER` in the project.
  - `manageMembers(User $user, Project $project)`: Caller must hold `OWNER` or `MANAGER` in the project.
  - `transferOwnership(User $user, Project $project)`: Caller must be current project `OWNER`.
- [ ] **Step 2:** Ensure policies check project-level pivot role, not team-level role.

### Task 5.2: Project Form Requests & Validation
- [ ] **Step 1:** Create `app/Http/Requests/Project/StoreProjectRequest.php`:
  - `team_id`: `['required', 'exists:teams,id']`
  - `name`: `['required', 'string', 'max:150']`
  - `description`: `['nullable', 'string']`
  - `status`: `['sometimes', Rule::in(['ACTIVE', 'ON_HOLD', 'COMPLETED', 'ARCHIVED'])]`
  - `start_date`: `['nullable', 'date']`
  - `due_date`: `['nullable', 'date', 'after_or_equal:start_date']`
- [ ] **Step 2:** Create `app/Http/Requests/Project/UpdateProjectRequest.php`:
  - `name`: `['sometimes', 'string', 'max:150']`
  - `description`: `['nullable', 'string']`
  - `status`: `['sometimes', Rule::in(['ACTIVE', 'ON_HOLD', 'COMPLETED', 'ARCHIVED'])]`
  - `start_date`: `['nullable', 'date']`
  - `due_date`: `['nullable', 'date', 'after_or_equal:start_date']`
- [ ] **Step 3:** Create `app/Http/Requests/Project/AddProjectMemberRequest.php`:
  - `user_id`: `['required', 'exists:users,id']`
  - `role`: `['required', Rule::in(['OWNER', 'MANAGER', 'MEMBER', 'VIEWER'])]`
  - Custom rule: Verify the user is an active member of the project's parent team.
- [ ] **Step 4:** Create `app/Http/Requests/Project/UpdateProjectMemberRoleRequest.php`:
  - `role`: `['required', Rule::in(['OWNER', 'MANAGER', 'MEMBER', 'VIEWER'])]`

### Task 5.3: Project Service Layer
- [ ] **Step 1:** Create `app/Services/ProjectService.php`:
  - `createProject(User $creator, array $data): Project`:
    - Wraps in DB transaction.
    - Creates project linked to `team_id` and `created_by = $creator->id`.
    - Inserts `$creator` into `project_members` with role `'OWNER'`.
    - Logs activity: `created_project`.
  - `addMember(Project $project, User $user, string $role): void`:
    - Verifies user belongs to parent team.
    - Prevents duplicate insertion into `project_members`.
    - Inserts with `joined_at = now()`.
  - `changeMemberRole(Project $project, User $targetUser, string $newRole): void`:
    - If target is currently `OWNER` and demoted: verify at least one other `OWNER` exists in the project.
    - Update role in pivot.
  - `removeMember(Project $project, User $targetUser): void`:
    - Check if last owner; prevent removal if count == 1.
    - Detach from `project_members`.
    - Also unassign user from active tasks in this project or reassign tasks (optional cleanup rule).

### Task 5.4: Project API Resources
- [ ] **Step 1:** Create `app/Http/Resources/ProjectResource.php`:
  - Exposes: `id`, `team_id`, `name`, `description`, `status`, `start_date`, `due_date`, `created_by`, `member_count`, `tasks_count`, `created_at`, `updated_at`.
  - Conditional: `team` and `members` relations when eager-loaded.
- [ ] **Step 2:** Create `app/Http/Resources/ProjectMemberResource.php`:
  - Exposes: `user_id`, `name`, `email`, `role`, `joined_at`.

### Task 5.5: Project Controller Implementation
- [ ] **Step 1:** Create `app/Http/Controllers/Api/V1/ProjectController.php`:
  - `index(Request $request)`: Query scoped to projects where `project_members.user_id = auth()->id()`. Supports filter by `team_id` and `status`.
  - `store(StoreProjectRequest $request)`: Calls `ProjectService::createProject()`, returns 201 with `ProjectResource`.
  - `show(Project $project)`: Authorizes with `view`, returns `ProjectResource`.
  - `update(UpdateProjectRequest $request, Project $project)`: Authorizes, updates fields, returns `ProjectResource`.
  - `destroy(Project $project)`: Authorizes, performs soft delete, returns 204.
- [ ] **Step 2:** Project membership endpoints:
  - `members(Project $project)`
  - `addMember(AddProjectMemberRequest $request, Project $project)`
  - `updateMemberRole(UpdateProjectMemberRoleRequest $request, Project $project, User $user)`
  - `removeMember(Project $project, User $user)`

### Task 5.6: Feature Tests for Projects
- [ ] **Step 1:** Create `tests/Feature/ProjectTest.php`.
- [ ] **Step 2:** Test scenarios:
  - Team member cannot view project they have not been added to (403 Forbidden).
  - Creator is automatically registered as project `OWNER`.
  - Adding a user who is not in the parent team fails validation (422).
  - Demoting or removing the only `OWNER` fails with an appropriate domain error.
  - Project start and due dates enforce chronological order (`due_date >= start_date`).
- [ ] **Step 3:** Run `php artisan test --filter=ProjectTest`.

---

## Acceptance Criteria
- [x] Project creation assigns the creator as `OWNER` inside a transaction.
- [x] Strict isolation: team membership alone does not bypass project authorization.
- [x] Date validation ensures `due_date` cannot precede `start_date`.
- [x] Last owner retention protects project governance.
- [x] All project feature tests pass.

