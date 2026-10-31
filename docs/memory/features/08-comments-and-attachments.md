# Feature Memory: 08 - Collaboration: Comments & Attachments

## Context
Implemented Phase 8: Collaboration — Comments & Attachments. Enables secure discussions on tasks with author-exclusive editing and manager moderation, plus multi-type validated file attachments on private disks with authorized streaming downloads and IDOR protection.

## Implementation Details
- **Architecture Mode:** MODE_A (`Controller -> FormRequest -> DTO -> Service -> Repository -> Model`).
- **Comments Management:**
  - `CommentPolicy` enforces:
    - `view`: Project member (`OWNER`, `MANAGER`, `MEMBER`, `VIEWER`).
    - `create`: Project write member (`OWNER`, `MANAGER`, `MEMBER`).
    - `update`: Author-exclusive (`$comment->user_id === $user->id`).
    - `delete`: Author or project `OWNER`/`MANAGER` (moderation).
  - Repositories: `CommentRepositoryInterface` & `CommentRepository`.
  - Service: `CommentService` with atomic transactions, pagination, and activity logs (`added_comment`, `deleted_comment`).
  - DTOs: `StoreCommentDTO` & `UpdateCommentDTO`.
- **Attachments Security & Storage:**
  - Storage configuration: Stored in private disk (`local`) under `storage/app/private/attachments/{task_id}/...`, safely outside the public web root.
  - Validation: Max 10MB (`max:10240`), whitelist of safe extensions and mime types (`pdf,doc,docx,xls,xlsx,png,jpg,jpeg,zip,txt`). Strict blocking of executable scripts (`.php`, `.sh`, `.exe`).
  - Representation: `AttachmentResource` strictly omits internal `path` and `disk` from JSON output.
  - Streaming Downloads: `AttachmentController::download()` authorizes user membership on task's project before streaming via `Storage::disk('local')->download()`, preventing IDOR.
  - File Deletion: `AttachmentService::deleteAttachment()` physically unlinks storage file and soft-deletes DB record in a database transaction.
  - Repositories: `AttachmentRepositoryInterface` & `AttachmentRepository`.
  - Service: `AttachmentService` with activity logs (`uploaded_attachment`, `deleted_attachment`).
- **Repositories & Service Provider:**
  - `CommentRepository` and `AttachmentRepository` bound in `RepositoryServiceProvider`.
- **OpenAPI & Apidog:**
  - 8 new operations across `Comments` and `Attachments` tags added to `GenerateOpenApiSpec`.
  - Specs verified in `OpenApiSpecTest.php` with 29 paths documented.
- **Verification:**
  - Feature tests: `tests/Feature/CommentTest.php` (11 tests passing).
  - Feature tests: `tests/Feature/AttachmentTest.php` (11 tests passing).
  - OpenApi tests: `tests/Feature/OpenApiSpecTest.php` (5 tests passing).
  - Total application test suite: 115 passed (628 assertions, 0 failures).

