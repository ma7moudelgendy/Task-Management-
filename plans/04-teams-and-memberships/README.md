# Phase 04: Teams and Memberships Plan

**Date:** 2026-10-28  
**Feature:** Teams & Team Memberships Module  
**Status:** In Progress  
**Architecture Mode:** MODE_A (`Controller -> FormRequest -> DTO -> Service -> Repository -> Model`)

---

## 1. Goal
Implement multi-tenant team management and team membership lifecycle for TaskFlow:
- Complete CRUD operations on teams with auto-generated unique slugs.
- Automatic initial team ownership: the team creator is guaranteed `OWNER` status inside an atomic database transaction.
- Team membership management: invite/add members by email with roles (`OWNER`, `MANAGER`, `MEMBER`), update member roles, and remove members.
- Strict owner-retention invariant: a team can never be left without at least one `OWNER`.
- Robust authorization via `TeamPolicy`: non-members cannot inspect or mutate team resources; only `OWNER` or `MANAGER` can update settings or manage members; only `OWNER` can delete team or change member roles.
- Standard TaskFlow HTTP 200 transport envelope (`jsonResponse`) and centralized exception handling (`handleApi`).

---

## 2. Design Decisions & Architecture Mode (MODE_A)

### Layer Architecture
- **Controller:** `App\Http\Controllers\Api\V1\TeamController`
  - Extends `ApiController`, uses `HandlesApiExceptions`.
  - HTTP 200 status on all business responses, outcome determined by `key` and `code`.
- **Form Requests:**
  - `StoreTeamRequest`: validates `name` (required, string, max:100), `slug` (nullable, string, max:100, alpha_dash, unique:teams,slug).
  - `UpdateTeamRequest`: validates `name` (sometimes, string, max:100), `slug` (sometimes, string, max:100, alpha_dash, unique:teams,slug,ignore).
  - `AddTeamMemberRequest`: validates `email` (required, email, exists:users,email), `role` (required, in:OWNER,MANAGER,MEMBER).
  - `UpdateTeamMemberRoleRequest`: validates `role` (required, in:OWNER,MANAGER,MEMBER).
- **DTOs:**
  - `App\DTOs\Team\StoreTeamDTO`: `readonly class StoreTeamDTO` with `fromRequest()`.
  - `App\DTOs\Team\UpdateTeamDTO`: `readonly class UpdateTeamDTO` with `fromRequest()`.
  - `App\DTOs\Team\AddTeamMemberDTO`: `readonly class AddTeamMemberDTO` with `fromRequest()`.
  - `App\DTOs\Team\UpdateTeamMemberRoleDTO`: `readonly class UpdateTeamMemberRoleDTO` with `fromRequest()`.
- **Service:** `App\Services\Team\TeamService`
  - Encapsulates domain logic:
    - `getUserTeams(User $user, int $perPage = 15)`
    - `createTeam(User $creator, StoreTeamDTO $dto): Team` (generates unique slug, attaches creator as `OWNER`, logs activity in DB transaction)
    - `updateTeam(Team $team, UpdateTeamDTO $dto): Team`
    - `deleteTeam(Team $team): void`
    - `getTeamMembers(Team $team)`
    - `addMember(Team $team, AddTeamMemberDTO $dto): TeamMember` (validates not already a member, attaches user with `joined_at = now()`, logs activity)
    - `changeMemberRole(Team $team, User $targetUser, UpdateTeamMemberRoleDTO $dto): void` (enforces last-owner safeguard)
    - `removeMember(Team $team, User $targetUser): void` (enforces last-owner safeguard)
- **Repository:**
  - `App\Repositories\Contracts\TeamRepositoryInterface`
  - `App\Repositories\Eloquent\TeamRepository`
  - Bound in `App\Providers\RepositoryServiceProvider`
- **Resources:**
  - `App\Http\Resources\Api\TeamResource` (includes `helpers` for action permissions `can_update`, `can_delete`, `can_manage_members`)
  - `App\Http\Resources\Api\TeamMemberResource`
- **Policy:**
  - `App\Policies\TeamPolicy` (registered and bound to `Team` model)

---

## 3. API Changes

| Method | Endpoint | Description | Auth Required | Envelope Key |
|---|---|---|---|---|
| GET | `/api/v1/teams` | List authenticated user's teams | Sanctum | `success` |
| POST | `/api/v1/teams` | Create new team | Sanctum | `success` (code 201) |
| GET | `/api/v1/teams/{team}` | Get team details & member count | Sanctum | `success` |
| PATCH | `/api/v1/teams/{team}` | Update team name / slug | Sanctum | `success` |
| DELETE | `/api/v1/teams/{team}` | Soft-delete team | Sanctum | `success` |
| GET | `/api/v1/teams/{team}/members` | List team members with roles | Sanctum | `success` |
| POST | `/api/v1/teams/{team}/members` | Add new member by email | Sanctum | `success` (code 201) |
| PATCH | `/api/v1/teams/{team}/members/{user}` | Update member role | Sanctum | `success` |
| DELETE | `/api/v1/teams/{team}/members/{user}` | Remove member from team | Sanctum | `success` |

---

## 4. Risks & Mitigations
- **Last Owner Removal/Demotion:** Demoting or removing the only `OWNER` of a team would orphan the team workspace.
  - *Mitigation:* Explicit query checks count of remaining owners with lock/transaction, throwing `ValidationException` (`key: 'fail'`, `code: 422`).
- **Duplicate Membership:** Concurrent requests inviting the same user.
  - *Mitigation:* Unique DB index on `(team_id, user_id)` + pre-check in service returning structured 422 error.
- **Slug Collisions:** Auto-generated slug from name may already exist in `teams`.
  - *Mitigation:* Loop with suffix counter ensuring unique slug generation.
- **IDOR / Unauthorized Access:** Accessing or tampering with teams where the user is not a member or lacks role permissions.
  - *Mitigation:* Enforce `TeamPolicy` on all controller actions returning `key: 'unauthorized'`, `code: 403`.

