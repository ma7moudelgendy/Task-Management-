# Feature: 04 Teams & Memberships

## Summary
Complete multi-tenant workspace management for teams and memberships under MODE_A architecture. Guarantees team creator initial `OWNER` status inside an atomic database transaction. Enforces role-based permissions (`OWNER`, `MANAGER`, `MEMBER`) and owner-retention invariant (a team can never be left without an `OWNER`).

## Layer Structure (MODE_A)
- Controller: `App\Http\Controllers\Api\V1\TeamController`
- FormRequests & DTOs:
  - `StoreTeamRequest` / `StoreTeamDTO`
  - `UpdateTeamRequest` / `UpdateTeamDTO`
  - `AddTeamMemberRequest` / `AddTeamMemberDTO`
  - `UpdateTeamMemberRoleRequest` / `UpdateTeamMemberRoleDTO`
- Service: `App\Services\Team\TeamService`
- Repository: `TeamRepositoryInterface` / `TeamRepository` (bound in `RepositoryServiceProvider`)
- Policy: `App\Policies\TeamPolicy`
- Resources: `TeamResource`, `TeamMemberResource`

## Key Invariants Enforced
1. **Initial Ownership**: Team creation atomically attaches creator with role `OWNER` and logs `created_team` to `activity_logs`.
2. **Unique Slug Generation**: Custom slug validation or automatic collision-safe sequential slug generation via `Str::slug`.
3. **Owner Retention**: Demoting or removing the last remaining `OWNER` fails with validation error `code: 422` ("Team must retain at least one owner.").
4. **Duplicate Membership**: Prevents duplicate member attachment with `code: 422`.
5. **Authorization**: Enforces `TeamPolicy` (`view`, `update`, `delete`, `manageMembers`, `changeRole`), preventing IDOR.

## Tests
- `tests/Feature/TeamTest.php` (17 tests, 87 assertions passing).
- `tests/Feature/OpenApiSpecTest.php` (5 tests, 76 assertions passing).
- Full suite: 42 tests, 231 assertions passing with 0 failures.

