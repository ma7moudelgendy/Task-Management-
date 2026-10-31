# API Memory: `/api/v1/tasks/{task}/comments` & `attachments`

## Endpoints

| Method | Path | Summary | Role/Auth | Envelope |
|---|---|---|---|---|
| `GET` | `/api/v1/tasks/{task}/comments` | List task comments (paginated) | BearerAuth (Project member/ADMIN) | 200 `key: success` |
| `POST` | `/api/v1/tasks/{task}/comments` | Post comment on task | BearerAuth (OWNER, MANAGER, MEMBER) | 200 `key: success`, `code: 201` |
| `PATCH` | `/api/v1/comments/{comment}` | Update own comment | BearerAuth (Author exclusive / ADMIN) | 200 `key: success` |
| `DELETE` | `/api/v1/comments/{comment}` | Delete comment | BearerAuth (Author, OWNER, MANAGER) | 200 `key: success` |
| `GET` | `/api/v1/tasks/{task}/attachments` | List task file attachments | BearerAuth (Project member/ADMIN) | 200 `key: success` |
| `POST` | `/api/v1/tasks/{task}/attachments` | Upload file to private storage | BearerAuth (OWNER, MANAGER, MEMBER) | 200 `key: success`, `code: 201` |
| `GET` | `/api/v1/tasks/{task}/attachments/{attachment}/download` | Stream binary file download | BearerAuth (Project member/ADMIN) | 200 StreamedResponse |
| `DELETE` | `/api/v1/tasks/{task}/attachments/{attachment}` | Delete file & storage asset | BearerAuth (Uploader, OWNER, MANAGER) | 200 `key: success` |

## Business Invariants & Rules
- **Comment Permissions:**
  - Author-exclusive editing: Non-authors (even managers/owners) cannot edit another user's comment text.
  - Moderation deletion: Project `OWNER` and `MANAGER` can delete offensive/inappropriate comments from any user.
  - Viewers are read-only: Cannot create comments.
- **Attachment Storage & Security:**
  - Private disk: Files are uploaded and stored outside the public document root (`storage/app/private/attachments/{task_id}/...`).
  - Safe representation: Internal storage paths and disk names are never exposed in JSON responses.
  - Validation: Strict mime-type and extension checking (`pdf,doc,docx,xls,xlsx,png,jpg,jpeg,zip,txt`), maximum size 10MB (`10240 KB`). Dangerous executable scripts (`.php`, `.sh`, `.exe`, etc.) are blocked.
  - IDOR Protection: Downloads require project membership verification on the specific task.
- **Activity Logging:**
  - `added_comment` and `deleted_comment` on `Task` subject.
  - `uploaded_attachment` and `deleted_attachment` on `Task` subject.

