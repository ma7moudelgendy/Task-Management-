# Phase 4: Teams & Team Memberships Module

> **Spec Reference:** Section 2 (Roles & Permissions), Section 5.2 - 5.3 (`teams`, `team_members`), Section 8.2 (Endpoints), Section 13 (Phase 4)  
> **Primary Goal:** Implement complete multi-tenant team management, slug generation, team member invitations/roles (`OWNER`, `MANAGER`, `MEMBER`), and strict owner-retention safeguards.

---

## High-Level Objective
Teams act as the top-level container for collaborative workspaces. A user who creates a team automatically becomes its first `OWNER`. Team roles govern who can manage settings and membership. A team must never be left without an owner.

---

## Detailed Task Breakdown

### Task 4.1: Team Policy & Authorization Rules
- [ ] **Step 1:** Create `app/Policies/TeamPolicy.php`:
  - `view(User $user, Team $team)`: Caller must be a team member or system `ADMIN`.
  - `update(User $user, Team $team)`: Caller must hold `OWNER` or `MANAGER` role in the team.
  - `delete(User $user, Team $team)`: Caller must be an `OWNER` of the team.
  - `manageMembers(User $user, Team $team)`: Caller must be `OWNER` or `MANAGER`.
  - `changeRole(User $user, Team $team)`: Only `OWNER` can promote/demote members.
- [ ] **Step 2:** Register `TeamPolicy` in `AuthServiceProvider` or via automatic discovery.

### Task 4.2: Team Form Requests & Validation
- [ ] **Step 1:** Create `app/Http/Requests/Team/StoreTeamRequest.php`:
  - `name`: `['required', 'string', 'max:100']`
  - `slug`: `['nullable', 'string', 'max:100', 'alpha_dash', 'unique:teams,slug']`
- [ ] **Step 2:** Create `app/Http/Requests/Team/UpdateTeamRequest.php`:
  - `name`: `['sometimes', 'string', 'max:100']`
  - `slug`: `['sometimes', 'string', 'max:100', 'alpha_dash', Rule::unique('teams')->ignore($this->team->id)]`
- [ ] **Step 3:** Create `app/Http/Requests/Team/AddTeamMemberRequest.php`:
  - `email`: `['required', 'email', 'exists:users,email']`
  - `role`: `['required', Rule::in(['OWNER', 'MANAGER', 'MEMBER'])]`
- [ ] **Step 4:** Create `app/Http/Requests/Team/UpdateTeamMemberRoleRequest.php`:
  - `role`: `['required', Rule::in(['OWNER', 'MANAGER', 'MEMBER'])]`

### Task 4.3: Team Service Layer
- [ ] **Step 1:** Create `app/Services/TeamService.php`:
  - `createTeam(User $creator, array $data): Team`:
    - Wraps in DB transaction: creates `Team` (generating unique slug via `Str::slug` if omitted).
    - Attaches `$creator` to `team_members` with role `'OWNER'`.
    - Logs activity: `created_team`.
  - `addMember(Team $team, User $user, string $role): void`:
    - Checks if already a member (prevents duplicates).
    - Attaches user with role and `joined_at = now()`.
  - `changeMemberRole(Team $team, User $targetUser, string $newRole): void`:
    - If demoting an `OWNER`: check count of remaining `OWNER`s. If count == 1, throw `DomainException("Team must retain at least one owner.")`.
    - Update pivot `role`.
  - `removeMember(Team $team, User $targetUser): void`:
    - If target is the last `OWNER`, throw `DomainException`.
    - Detach user.

### Task 4.4: Team API Resources
- [ ] **Step 1:** Create `app/Http/Resources/TeamResource.php`:
  - Exposes: `id`, `name`, `slug`, `created_by`, `member_count`, `created_at`, `updated_at`.
  - Conditional: `members` when loaded.
- [ ] **Step 2:** Create `app/Http/Resources/TeamMemberResource.php`:
  - Exposes: `user_id`, `name`, `email`, `role`, `joined_at`.

### Task 4.5: Team Controller Implementation
- [ ] **Step 1:** Create `app/Http/Controllers/Api/V1/TeamController.php`:
  - `index()`: Returns paginated teams where `$request->user()` is a member.
  - `store(StoreTeamRequest $request)`: Calls `TeamService::createTeam()`, returns 201 with `TeamResource`.
  - `show(Team $team)`: Authorizes with policy, returns `TeamResource` with members count.
  - `update(UpdateTeamRequest $request, Team $team)`: Authorizes, updates attributes, returns `TeamResource`.
  - `destroy(Team $team)`: Authorizes, deletes team, returns 204.
- [ ] **Step 2:** Implement Team Member methods:
  - `members(Team $team)`: Returns list of members.
  - `addMember(AddTeamMemberRequest $request, Team $team)`: Adds user by email, returns 201.
  - `updateMemberRole(UpdateTeamMemberRoleRequest $request, Team $team, User $user)`: Updates role.
  - `removeMember(Team $team, User $user)`: Detaches user.

### Task 4.6: Routes Registration
- [ ] **Step 1:** Add team routes in `routes/api.php` under Sanctum middleware:
  ```php
  Route::apiResource('teams', TeamController::class);
  Route::prefix('teams/{team}')->group(function () {
      Route::get('members', [TeamController::class, 'members']);
      Route::post('members', [TeamController::class, 'addMember']);
      Route::patch('members/{user}', [TeamController::class, 'updateMemberRole']);
      Route::delete('members/{user}', [TeamController::class, 'removeMember']);
  });
  ```

### Task 4.7: Feature Tests for Teams
- [ ] **Step 1:** Create `tests/Feature/TeamTest.php`.
- [ ] **Step 2:** Test scenarios:
  - User can create team and becomes `OWNER`.
  - Non-member cannot view or update team (403 Forbidden).
  - `MANAGER` can add members, but cannot delete team.
  - Duplicate member addition returns 422 conflict.
  - Attempting to remove or demote the last `OWNER` fails with 422 / domain error.
- [ ] **Step 3:** Run `php artisan test --filter=TeamTest`.

---

## Acceptance Criteria
- [x] Full CRUD operations on teams with auto-generated unique slugs.
- [x] Creator is guaranteed initial `OWNER` status inside an atomic transaction.
- [x] Last-owner demotion/removal protection is enforced and verified with tests.
- [x] Non-members are strictly forbidden from viewing or modifying team resources.

