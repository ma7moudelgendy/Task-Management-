# Implementation Phases: Comments & File Attachments

## Checklist

- [x] **Phase 1: Domain & Authorization Policies**
  - [x] Implement `App\Policies\CommentPolicy` (`create`, `update`, `delete`).
  - [x] Implement `App\Policies\AttachmentPolicy` (`upload`, `download`, `delete`).
  - [x] Register both policies in `AppServiceProvider`.

- [x] **Phase 2: Repository Layer (MODE_A)**
  - [x] Create `App\Repositories\Contracts\CommentRepositoryInterface`.
  - [x] Create `App\Repositories\Eloquent\CommentRepository`.
  - [x] Create `App\Repositories\Contracts\AttachmentRepositoryInterface`.
  - [x] Create `App\Repositories\Eloquent\AttachmentRepository`.
  - [x] Register bindings in `App\Providers\RepositoryServiceProvider`.

- [x] **Phase 3: Form Requests & DTOs**
  - [x] Create `StoreCommentRequest` and `StoreCommentDTO`.
  - [x] Create `UpdateCommentRequest` and `UpdateCommentDTO`.
  - [x] Create `UploadAttachmentRequest`.

- [x] **Phase 4: Service Layer**
  - [x] Implement `App\Services\Collaboration\CommentService`:
    - `getTaskComments(Task $task, int $perPage = 15)`
    - `createComment(Task $task, User $author, StoreCommentDTO $dto)`
    - `updateComment(TaskComment $comment, UpdateCommentDTO $dto)`
    - `deleteComment(TaskComment $comment, User $actor)`
  - [x] Implement `App\Services\Collaboration\AttachmentService`:
    - `getTaskAttachments(Task $task, int $perPage = 15)`
    - `uploadAttachment(Task $task, User $uploader, UploadedFile $file)`
    - `deleteAttachment(TaskAttachment $attachment, User $actor)`

- [x] **Phase 5: API Resources**
  - [x] Create `App\Http\Resources\Api\CommentResource` (with permissions `helpers`: `can_update`, `can_delete`).
  - [x] Create `App\Http\Resources\Api\AttachmentResource` (safe fields, omitting internal path/disk, with `helpers.can_delete`).

- [x] **Phase 6: Controllers & Routing**
  - [x] Implement `App\Http\Controllers\Api\V1\CommentController`.
  - [x] Implement `App\Http\Controllers\Api\V1\AttachmentController`.
  - [x] Create routes file `routes/guards/v1/collaboration.php` and mount in `routes/api.php`.

- [x] **Phase 7: Comprehensive Feature Testing**
  - [x] Create `tests/Feature/CommentTest.php`:
    - Member can comment on project task.
    - Non-member cannot comment (403).
    - Author can update their comment.
    - Other member cannot update another user's comment (403).
    - Author can delete their comment.
    - Project owner/manager can delete any comment (moderation).
    - Viewer cannot comment (403).
  - [x] Create `tests/Feature/AttachmentTest.php`:
    - File upload stores binary on private disk.
    - Uploading disallowed file extension fails validation (422).
    - File exceeding 10MB fails validation (422).
    - Project member can download file (streams with correct headers).
    - Non-member cannot download file (403 IDOR blocked).
    - Delete attachment removes record and deletes file from disk.

- [x] **Phase 8: OpenAPI Specification & Synchronizations**
  - [x] Update `App\Console\Commands\GenerateOpenApiSpec.php` with Comments and Attachments tags, paths, schemas.
  - [x] Update `tests/Feature/OpenApiSpecTest.php` expected route list and protected operations.
  - [x] Regenerate `docs/openapi.json`.

- [x] **Phase 9: Documentation & Memory Synchronization**
  - [x] Create client documentation `api-docs/frontend-docs/user/comments-and-attachments.md`.
  - [x] Create Agent Memory `docs/memory/api/v1-comments-and-attachments.md`.
  - [x] Create Agent Memory `docs/memory/features/08-comments-and-attachments.md`.
  - [x] Update `docs/memory/INDEX.md`.
