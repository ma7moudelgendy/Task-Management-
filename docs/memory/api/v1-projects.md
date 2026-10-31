# API: V1 Projects & Project Members

## Endpoints

| Method | URI | Name | Action | Policy Check |
|---|---|---|---|---|
| GET | `/api/v1/projects` | `projects.index` | `ProjectController@index` | Authenticated |
| POST | `/api/v1/projects` | `projects.store` | `ProjectController@store` | `create` (Team Owner / Manager / Admin) |
| GET | `/api/v1/projects/{project}` | `projects.show` | `ProjectController@show` | `view` (Project Member / Admin) |
| PATCH | `/api/v1/projects/{project}` | `projects.update` | `ProjectController@update` | `update` (Project Owner / Manager / Admin) |
| DELETE | `/api/v1/projects/{project}` | `projects.destroy` | `ProjectController@destroy` | `delete` (Project Owner / Admin) |
| GET | `/api/v1/projects/{project}/members` | - | `ProjectController@members` | `view` (Project Member / Admin) |
| POST | `/api/v1/projects/{project}/members` | - | `ProjectController@addMember` | `manageMembers` (Project Owner / Manager / Admin) |
| PATCH | `/api/v1/projects/{project}/members/{user}` | - | `ProjectController@updateMemberRole` | `changeRole` (Project Owner / Admin) |
| DELETE | `/api/v1/projects/{project}/members/{user}` | - | `ProjectController@removeMember` | `manageMembers` (Project Owner / Manager / Admin) |

## Key Invariants
- **Membership Separation:** Being in `team_members` does not grant access to projects in that team.
- **Candidate Eligibility:** Candidate must belong to parent `team_members` to be added to `project_members`.
- **Chronological Dates:** `due_date >= start_date`.
- **Last-Owner Safeguard:** Cannot remove or demote the sole remaining project `OWNER` (`code: 422`).
- **Task Preservation:** Detaching a member preserves existing `task_assignees` records for audit/history, but revokes project access immediately.

