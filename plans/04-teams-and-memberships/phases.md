# Implementation Phases: Teams & Team Memberships

## Checklist

- [x] **Phase 1: Domain & Authorization Layer**
  - [x] Implement `App\Policies\TeamPolicy` with fine-grained role checks (`view`, `update`, `delete`, `manageMembers`, `changeRole`).
  - [x] Register `TeamPolicy` in Laravel policy mappings.
  - [x] Add necessary helper methods on `Team` model (`isOwner`, `isMember`, `userRole`).

- [x] **Phase 2: Repository Layer (MODE_A)**
  - [x] Create `App\Repositories\Contracts\TeamRepositoryInterface`.
  - [x] Create `App\Repositories\Eloquent\TeamRepository`.
  - [x] Register repository binding in `App\Providers\RepositoryServiceProvider`.

- [x] **Phase 3: Form Requests & DTOs**
  - [x] Create `StoreTeamRequest` and `StoreTeamDTO`.
  - [x] Create `UpdateTeamRequest` and `UpdateTeamDTO`.
  - [x] Create `AddTeamMemberRequest` and `AddTeamMemberDTO`.
  - [x] Create `UpdateTeamMemberRoleRequest` and `UpdateTeamMemberRoleDTO`.

- [x] **Phase 4: Service Layer**
  - [x] Implement `App\Services\Team\TeamService`:
    - `getUserTeams`
    - `createTeam` (with slug generation, creator owner attachment, and activity log in transaction)
    - `updateTeam`
    - `deleteTeam`
    - `getTeamMembers`
    - `addMember` (prevent duplicates, attach with joined_at)
    - `changeMemberRole` (prevent last owner demotion)
    - `removeMember` (prevent last owner removal)

- [x] **Phase 5: API Resources**
  - [x] Create `App\Http\Resources\Api\TeamResource` (with conditional members and permissions helper).
  - [x] Create `App\Http\Resources\Api\TeamMemberResource`.

- [x] **Phase 6: Controllers & Routing**
  - [x] Implement `App\Http\Controllers\Api\V1\TeamController`.
  - [x] Create routes file `routes/guards/v1/teams.php`.
  - [x] Register `teams.php` inside `routes/api.php` under Sanctum authentication.

- [x] **Phase 7: OpenAPI Specification & Synchronizations**
  - [x] Update `App\Console\Commands\GenerateOpenApiSpec.php` with all team endpoints.
  - [x] Update `tests/Feature/OpenApiSpecTest.php` expected route list.
  - [x] Re-generate `docs/openapi.json` via `php artisan openapi:generate`.

- [x] **Phase 8: Comprehensive Feature Testing**
  - [x] Create `tests/Feature/TeamTest.php` covering:
    - Team creation & automatic `OWNER` status.
    - Slug generation (auto vs custom).
    - Team update & soft delete authorization.
    - Non-member access prevention (403).
    - Adding members (OWNER and MANAGER authorized).
    - Duplicate member prevention (422).
    - Changing member role (only OWNER authorized).
    - Safeguard: last owner cannot be demoted (422).
    - Safeguard: last owner cannot be removed (422).
    - Member removal.
  - [x] Execute full test suite to guarantee 100% pass rate.

- [x] **Phase 9: Documentation & Agent Memory**
  - [x] Create `api-docs/frontend-docs/user/teams.md`.
  - [x] Create `docs/memory/api/v1-teams.md`.
  - [x] Create `docs/memory/features/04-teams-and-memberships.md`.
  - [x] Update `docs/memory/INDEX.md`.
