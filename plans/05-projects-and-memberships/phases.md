# Implementation Phases: Projects & Project Memberships

## Checklist

- [x] **Phase 1: Domain & Authorization Layer**
  - [x] Implement `App\Policies\ProjectPolicy` (`view`, `create`, `update`, `delete`, `manageMembers`, `changeRole`).
  - [x] Bind `ProjectPolicy` in `AppServiceProvider`.
  - [x] Add membership helpers on `Project` model (`isMember`, `isOwner`, `isOwnerOrManager`, `userRole`, `ownersCount`).

- [x] **Phase 2: Repository Layer (MODE_A)**
  - [x] Create `App\Repositories\Contracts\ProjectRepositoryInterface`.
  - [x] Create `App\Repositories\Eloquent\ProjectRepository`.
  - [x] Register repository binding in `App\Providers\RepositoryServiceProvider`.

- [x] **Phase 3: Form Requests & DTOs**
  - [x] Create `StoreProjectRequest` and `StoreProjectDTO`.
  - [x] Create `UpdateProjectRequest` and `UpdateProjectDTO`.
  - [x] Create `AddProjectMemberRequest` and `AddProjectMemberDTO`.
  - [x] Create `UpdateProjectMemberRoleRequest` and `UpdateProjectMemberRoleDTO`.

- [x] **Phase 4: Service Layer**
  - [x] Implement `App\Services\Project\ProjectService`:
    - `getUserProjects` (filters: team_id, status)
    - `createProject` (with transaction, creator owner assignment, activity log)
    - `updateProject`
    - `deleteProject`
    - `getProjectMembers`
    - `addMember` (enforces parent team membership and duplicate prevention)
    - `changeMemberRole` (enforces last-owner safeguard)
    - `removeMember` (enforces last-owner safeguard, detaches from project, preserves tasks)

- [x] **Phase 5: API Resources**
  - [x] Create `App\Http\Resources\Api\ProjectResource` (with permissions helpers).
  - [x] Create `App\Http\Resources\Api\ProjectMemberResource`.

- [x] **Phase 6: Controllers & Routing**
  - [x] Implement `App\Http\Controllers\Api\V1\ProjectController`.
  - [x] Create routes file `routes/guards/v1/projects.php`.
  - [x] Register `projects.php` inside `routes/api.php` under Sanctum authentication.

- [x] **Phase 7: OpenAPI Specification & Synchronizations**
  - [x] Update `App\Console\Commands\GenerateOpenApiSpec.php` with all project endpoints, tags, and schemas.
  - [x] Update `tests/Feature/OpenApiSpecTest.php` expected route list and protected operations.
  - [x] Re-generate `docs/openapi.json` via `php artisan openapi:generate`.

- [x] **Phase 8: Comprehensive Feature Testing**
  - [x] Create `tests/Feature/ProjectTest.php` covering:
    - Team member cannot view project they were not added to (403 Forbidden).
    - Team OWNER/MANAGER can create project; creator becomes project OWNER.
    - Non-team-member cannot be added to project (422 Unprocessable).
    - Team member can be added to project.
    - Duplicate member addition fails (422).
    - Date validation: due_date < start_date fails (422).
    - Project update & soft delete permissions.
    - Last owner demotion/removal protection (422).
    - Member removal revokes project access immediately while preserving task assignments.
  - [x] Execute full test suite to guarantee 100% pass rate.

- [x] **Phase 9: Documentation & Agent Memory**
  - [x] Create `api-docs/frontend-docs/user/projects.md`.
  - [x] Create `docs/memory/api/v1-projects.md`.
  - [x] Create `docs/memory/features/05-projects-and-memberships.md`.
  - [x] Update `docs/memory/INDEX.md`.
