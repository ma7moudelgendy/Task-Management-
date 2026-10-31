# Phase 05: Projects and Project Memberships Plan

**Date:** 2026-10-28  
**Feature:** Projects & Project Memberships Module  
**Status:** In Progress  
**Architecture Mode:** MODE_A (`Controller -> FormRequest -> DTO -> Service -> Repository -> Model`)

---

## 1. Goal
Implement multi-tenant project management, strict team scoping, and project membership governance for TaskFlow:
- Complete CRUD operations on projects scoped under parent teams.
- Date validation enforcing chronological order (`due_date >= start_date`).
- Automatic project ownership: creator is atomically assigned role `OWNER` in `project_members`.
- Strict **Membership Separation**: being a member of a team does **not** grant automatic access to any project in that team. Only explicitly enrolled members in `project_members` (or system `ADMIN`) can access project resources.
- Eligible project membership: users can only be enrolled in a project if they are already active members of the parent team.
- Project role governance (`OWNER`, `MANAGER`, `MEMBER`, `VIEWER`).
- Strict owner retention invariant: a project can never be left without at least one `OWNER`.
- Task preservation policy: removing a member from a project immediately revokes their project access, but preserves past task assignments for audit and history.
- Standard TaskFlow HTTP 200 transport envelope (`jsonResponse`), centralized exception handling (`handleApi`), and fine-grained `ProjectPolicy`.

---

## 2. Design Decisions & Architecture Mode (MODE_A)

### Layer Architecture
- **Controller:** `App\Http\Controllers\Api\V1\ProjectController`
  - Extends `ApiController`, uses `HandlesApiExceptions`, `ApiResponse`, and `AuthorizesRequests`.
  - HTTP 200 status on all business responses, outcome determined by `key` and `code`.
- **Form Requests:**
  - `StoreProjectRequest`: `team_id` (required, exists:teams,id), `name` (required, string, max:150), `description` (nullable, string), `status` (sometimes, enum:ProjectStatus), `start_date` (nullable, date), `due_date` (nullable, date, after_or_equal:start_date).
  - `UpdateProjectRequest`: `name` (sometimes, string, max:150), `description` (nullable, string), `status` (sometimes, enum:ProjectStatus), `start_date` (nullable, date), `due_date` (nullable, date, after_or_equal:start_date).
  - `AddProjectMemberRequest`: `user_id` (required, exists:users,id), `role` (required, enum:ProjectRole).
  - `UpdateProjectMemberRoleRequest`: `role` (required, enum:ProjectRole).
- **DTOs:**
  - `StoreProjectDTO`, `UpdateProjectDTO`, `AddProjectMemberDTO`, `UpdateProjectMemberRoleDTO` (all `readonly` with `fromRequest()`).
- **Service:** `App\Services\Project\ProjectService`
  - Domain workflows wrapped in database transactions where appropriate:
    - `getUserProjects(User $user, array $filters, int $perPage = 15)`
    - `createProject(User $creator, StoreProjectDTO $dto): Project`
    - `updateProject(Project $project, UpdateProjectDTO $dto): Project`
    - `deleteProject(Project $project): void`
    - `getProjectMembers(Project $project)`
    - `addMember(Project $project, AddProjectMemberDTO $dto, User $actor): User` (validates team membership, prevents duplicate project membership)
    - `changeMemberRole(Project $project, User $targetUser, UpdateProjectMemberRoleDTO $dto): void` (enforces last-owner safeguard)
    - `removeMember(Project $project, User $targetUser): void` (enforces last-owner safeguard, detaches from `project_members`, preserves task assignment history)
- **Repository:**
  - `App\Repositories\Contracts\ProjectRepositoryInterface`
  - `App\Repositories\Eloquent\ProjectRepository`
  - Bound in `App\Providers\RepositoryServiceProvider`
- **Resources:**
  - `App\Http\Resources\Api\ProjectResource` (with `helpers` for action permissions `can_update`, `can_delete`, `can_manage_members`)
  - `App\Http\Resources\Api\ProjectMemberResource`
- **Policy:**
  - `App\Policies\ProjectPolicy`

---

## 3. API Changes

| Method | Endpoint | Description | Auth Required | Envelope Key |
|---|---|---|---|---|
| GET | `/api/v1/projects` | List projects user belongs to (filters: team_id, status) | Sanctum | `success` |
| POST | `/api/v1/projects` | Create project under a team | Sanctum | `success` (code 201) |
| GET | `/api/v1/projects/{project}` | Get project details, team info, member/task counts | Sanctum | `success` |
| PATCH | `/api/v1/projects/{project}` | Update project metadata / status / dates | Sanctum | `success` |
| DELETE | `/api/v1/projects/{project}` | Soft-delete project | Sanctum | `success` |
| GET | `/api/v1/projects/{project}/members` | List project members & roles | Sanctum | `success` |
| POST | `/api/v1/projects/{project}/members` | Add team member to project | Sanctum | `success` (code 201) |
| PATCH | `/api/v1/projects/{project}/members/{user}` | Update project member role | Sanctum | `success` |
| DELETE | `/api/v1/projects/{project}/members/{user}` | Remove member from project (preserves tasks) | Sanctum | `success` |

---

## 4. Risks & Mitigations
- **Bypassing Membership Separation:** A team member trying to inspect tasks or settings of a project they are not enrolled in.
  - *Mitigation:* `ProjectPolicy@view` strictly checks `project_members` pivot, returning `403` regardless of team membership.
- **Enrolling Non-Team Members:** Adding arbitrary users to a project who do not belong to the parent team.
  - *Mitigation:* `ProjectService@addMember` verifies candidate exists in `team_members` for `project->team_id`, throwing `ValidationException` (422) if not.
- **Orphaned Project (Last Owner Removal):** Demoting or removing the only `OWNER` of a project.
  - *Mitigation:* Count of project owners is verified before role changes or removals, throwing `ValidationException` (`key: 'fail'`, `code: 422`).
- **Date Inconsistencies:** `due_date` earlier than `start_date`.
  - *Mitigation:* Form request validation rule `after_or_equal:start_date` prevents invalid chronological inputs.

