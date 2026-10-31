# Phase 08: Collaboration — Comments & Attachments Plan

**Date:** 2026-10-31  
**Feature:** Task Discussions (Comments), Private File Uploads & Secure Streaming Downloads  
**Status:** In Progress  
**Architecture Mode:** MODE_A (`Controller -> FormRequest -> DTO -> Service -> Repository -> Model`)

---

## 1. Goal
Implement team collaboration capabilities on tasks with strict security controls:
- **Task Comments:**
  - Create, read, update, and soft-delete comments on tasks.
  - Granular authorization:
    - Member of the task's project (`OWNER`, `MANAGER`, `MEMBER`) can comment (`VIEWER` denied).
    - Only the author can update their comment.
    - The author OR project managers/owners can delete comments (moderation support).
  - Activity logging for comment creation.
- **Task Attachments:**
  - Secure multi-type file upload (`pdf`, `doc`, `docx`, `xls`, `xlsx`, `png`, `jpg`, `jpeg`, `zip`, `txt`).
  - Size constraint: Max 10MB per file (`10240 KB`).
  - Strictly no executable files, scripts, or PHP files allowed.
  - Stored strictly on private storage disk (`local` at `storage/app/private`), completely outside the public web root.
  - Authorized streaming download preventing IDOR: callers must be verified project members.
  - Soft-delete removes database record and deletes physical file from private disk.
  - Safe API responses: omits internal physical storage path from API payloads to prevent information disclosure.
- **Transport Standards:**
  - Transport HTTP Status is ALWAYS 200 with structured JSON envelopes (`key`, `code`, `msg`, `data`, `response_status`).
  - For streaming binary file downloads (`/attachments/{attachment}/download`), direct stream response with appropriate MIME headers (`Content-Disposition: attachment; filename="..."`).
  - OpenAPI 3.1.0 documentation and automated test verification.

---

## 2. Design Decisions & Architecture Mode (MODE_A)

### Layer Architecture
- **Controllers:**
  - `App\Http\Controllers\Api\V1\CommentController`:
    - `index(Task $task)`
    - `store(StoreCommentRequest $request, Task $task)`
    - `update(UpdateCommentRequest $request, TaskComment $comment)`
    - `destroy(TaskComment $comment)`
  - `App\Http\Controllers\Api\V1\AttachmentController`:
    - `index(Task $task)`
    - `upload(UploadAttachmentRequest $request, Task $task)`
    - `download(Task $task, TaskAttachment $attachment)`
    - `destroy(Task $task, TaskAttachment $attachment)`
- **Form Requests & DTOs:**
  - `StoreCommentRequest` -> `StoreCommentDTO` (`body`: required, string, max:5000)
  - `UpdateCommentRequest` -> `UpdateCommentDTO` (`body`: required, string, max:5000)
  - `UploadAttachmentRequest` -> handles multipart file validation: `file` (required, file, max:10240, mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg,zip,txt).
- **Services:**
  - `App\Services\Collaboration\CommentService`:
    - `getTaskComments(Task $task, int $perPage = 15): LengthAwarePaginator`
    - `createComment(Task $task, User $author, StoreCommentDTO $dto): TaskComment`
    - `updateComment(TaskComment $comment, UpdateCommentDTO $dto): TaskComment`
    - `deleteComment(TaskComment $comment, User $actor): void`
  - `App\Services\Collaboration\AttachmentService`:
    - `getTaskAttachments(Task $task, int $perPage = 15): LengthAwarePaginator`
    - `uploadAttachment(Task $task, User $uploader, UploadedFile $file): TaskAttachment`
    - `deleteAttachment(TaskAttachment $attachment, User $actor): void`
- **Repository Contracts & Implementations (MODE_A):**
  - `App\Repositories\Contracts\CommentRepositoryInterface` -> `App\Repositories\Eloquent\CommentRepository`
  - `App\Repositories\Contracts\AttachmentRepositoryInterface` -> `App\Repositories\Eloquent\AttachmentRepository`
  - Registered in `RepositoryServiceProvider`
- **Policies:**
  - `App\Policies\CommentPolicy`: `create`, `update`, `delete`.
  - `App\Policies\AttachmentPolicy`: `upload`, `download`, `delete`.
- **Resources:**
  - `App\Http\Resources\Api\CommentResource`
  - `App\Http\Resources\Api\AttachmentResource`

---

## 3. API Changes

| Method | URI | Summary | Auth |
|---|---|---|---|
| `GET` | `/api/v1/tasks/{task}/comments` | List task comments (paginated) | BearerAuth |
| `POST` | `/api/v1/tasks/{task}/comments` | Add comment to task | BearerAuth |
| `PATCH` | `/api/v1/comments/{comment}` | Update comment (author only) | BearerAuth |
| `DELETE` | `/api/v1/comments/{comment}` | Delete comment (author or manager/owner) | BearerAuth |
| `GET` | `/api/v1/tasks/{task}/attachments` | List task attachments | BearerAuth |
| `POST` | `/api/v1/tasks/{task}/attachments` | Upload file attachment to task | BearerAuth |
| `GET` | `/api/v1/tasks/{task}/attachments/{attachment}/download` | Stream private file download | BearerAuth |
| `DELETE` | `/api/v1/tasks/{task}/attachments/{attachment}` | Delete attachment | BearerAuth |

---

## 4. Risks & Mitigations
- **Unrestricted File Upload:** Enforce strict MIME verification (`mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg,zip,txt`), size limit 10MB.
- **Public Directory Leakage:** Files stored in `storage/app/private/attachments/{task_id}/...`, never accessible via web direct URL.
- **IDOR on File Downloads:** `AttachmentPolicy::download` verifies caller belongs to the task's project; checks `$attachment->task_id === $task->id`.

