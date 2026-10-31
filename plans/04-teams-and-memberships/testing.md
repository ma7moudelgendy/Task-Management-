# Testing Plan: Teams & Team Memberships

## Test Structure
Tests will be placed in `tests/Feature/TeamTest.php` and run using Laravel test suite with real database assertions.

---

## 1. End-to-End User Journeys

### Journey A: Full Team Lifecycle
1. User A registers / logs in, obtains Bearer token.
2. User A creates team "Alpha Engineering".
   - Assert HTTP 200, envelope `key: 'success'`, `code: 201`.
   - Assert `slug` auto-generated as `alpha-engineering`.
   - Assert User A is attached to `team_members` as `OWNER`.
   - Assert activity log recorded `created_team`.
3. User A views team details.
   - Assert `TeamResource` includes `member_count: 1`.
4. User A updates team name to "Alpha Core".
   - Assert database reflects updated name.
5. User A deletes team.
   - Assert team is soft-deleted.

### Journey B: Team Membership & Role Hierarchy
1. User A creates team "DevOps Squad".
2. User B and User C are registered.
3. User A adds User B as `MANAGER` by email (`devops-b@example.com`).
   - Assert User B is attached to `team_members` with role `MANAGER`.
4. User B (as `MANAGER`) adds User C as `MEMBER` by email.
   - Assert User C added successfully.
5. User B tries to delete team or change User C role to `OWNER`.
   - Assert HTTP 200, envelope `key: 'unauthorized'`, `code: 403`.
6. User A (as `OWNER`) changes User B role to `OWNER`.
   - Team now has two owners (User A and User B).
7. User A demotes User B back to `MEMBER`.
   - Allowed because User A remains as owner.
8. User A tries to demote or remove self (the only remaining owner).
   - Assert HTTP 200, envelope `key: 'fail'`, `code: 422`, message `"Team must retain at least one owner."`.
9. User A removes User C.
   - User C detached successfully.

---

## 2. Distinct Edge Cases & Negative Paths
- Unauthenticated requests to any `/teams*` route return `key: 'unauthenticated'`, `code: 401`.
- Non-member querying `/teams/{team}` returns `key: 'unauthorized'`, `code: 403`.
- Attempting to add an email that does not exist in `users` returns validation failure `code: 422`.
- Attempting to add an existing member again returns validation failure `code: 422` ("User is already a member of this team.").
- Attempting to use an invalid role (e.g. `'SUPER_ADMIN'`) returns validation error `code: 422`.
- Attempting to remove the last owner fails with `code: 422`.
- Querying a non-existent team ID returns `key: 'not_found'`, `code: 404`.

