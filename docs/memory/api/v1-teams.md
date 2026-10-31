# API: V1 Teams & Team Members

## Endpoints

| Method | URI | Name | Action | Policy Check |
|---|---|---|---|---|
| GET | `/api/v1/teams` | `teams.index` | `TeamController@index` | Authenticated |
| POST | `/api/v1/teams` | `teams.store` | `TeamController@store` | Authenticated |
| GET | `/api/v1/teams/{team}` | `teams.show` | `TeamController@show` | `view` (Member / Admin) |
| PATCH | `/api/v1/teams/{team}` | `teams.update` | `TeamController@update` | `update` (Owner / Manager / Admin) |
| DELETE | `/api/v1/teams/{team}` | `teams.destroy` | `TeamController@destroy` | `delete` (Owner / Admin) |
| GET | `/api/v1/teams/{team}/members` | - | `TeamController@members` | `view` (Member / Admin) |
| POST | `/api/v1/teams/{team}/members` | - | `TeamController@addMember` | `manageMembers` (Owner / Manager / Admin) |
| PATCH | `/api/v1/teams/{team}/members/{user}` | - | `TeamController@updateMemberRole` | `changeRole` (Owner / Admin) |
| DELETE | `/api/v1/teams/{team}/members/{user}` | - | `TeamController@removeMember` | `manageMembers` (Owner / Manager / Admin) |

## Response Formats
Standard TaskFlow envelope (`jsonResponse`) with HTTP 200 transport status.
- `key`: `success`, `fail`, `unauthorized`, `unauthenticated`, `not_found`.
- `code`: Envelope code matching semantic status (200, 201, 401, 403, 404, 422, 500).
- `data`: Payload, resource or null.
- `response_status`: `{ error: bool, validation_errors: object }`.

