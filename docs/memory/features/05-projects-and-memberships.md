# Feature: 05 Projects & Project Memberships

## Summary
Complete project lifecycle management under teams, strict membership separation (team membership does not grant automatic project access), scoped project roles (`OWNER`, `MANAGER`, `MEMBER`, `VIEWER`), candidate verification against parent team, date validation, and task preservation on member removal under MODE_A architecture.

## Layer Structure (MODE_A)
- Controller: `App\Http\Controllers\Api\V1\ProjectController`
- FormRequests & DTOs:
  - `StoreProjectRequest` / `StoreProjectDTO`
  - `UpdateProjectRequest` / `UpdateProjectDTO`
  - `AddProjectMemberRequest` / `AddProjectMemberDTO`
  - `UpdateProjectMemberRoleRequest` / `UpdateProjectMemberRoleDTO`
- Service: `App\Services\Project\ProjectService`
- Repository: `ProjectRepositoryInterface` / `ProjectRepository` (bound in `RepositoryServiceProvider`)
- Policy: `App\Policies\ProjectPolicy`
- Resources: `ProjectResource`, `ProjectMemberResource`

## Key Invariants Enforced
1. **Membership Separation:** `ProjectPolicy@view` checks `project_members` (or Admin), preventing unauthorized team members from accessing unjoined projects.
2. **Team Scoping & Candidate Verification:** Only parent team members can be added to a project.
3. **Creation Governance:** Only parent team `OWNER` or `MANAGER` can create projects; creator is atomically assigned project `OWNER`.
4. **Chronological Dates:** Validated at boundary (`due_date >= start_date`).
5. **Last-Owner Safeguard:** Demoting or removing the only `OWNER` fails with `code: 422`.
6. **Task Preservation:** Detaching a project member immediately revokes project access, but preserves task assignment records for audit/history.

## Tests
- `tests/Feature/ProjectTest.php` (16 tests, 78 assertions passing).
- `tests/Feature/OpenApiSpecTest.php` (5 tests, 107 assertions passing).
- Overall Suite: 58 tests, 340 assertions passing with 0 failures.

