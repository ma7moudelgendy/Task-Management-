# Phase 8: Collaboration — Comments & Attachments

> **Spec Reference:** Section 5.9 (`task_comments`), Section 5.10 (`task_attachments`), Section 8.5 (Endpoints), Section 13 (Phase 7), Section 15 (Security Checklist)  
> **Primary Goal:** Implement secure discussions via task comments, validated multi-type file uploads stored on private disks, and authorized streaming downloads with IDOR prevention.

---

## High-Level Objective
Enable team collaboration on tasks through comments and file attachments. Security is paramount: uploaded files must never be exposed publicly, file types and sizes must be validated, and every download must pass through strict policy authorization.

---

## Detailed Task Breakdown

### Task 8.1: Comment Policies & Authorization
- [ ] **Step 1:** Create `app/Policies/CommentPolicy.php`:
  - `create(User $user, Task $task)`: User must be a member of the task's project (`OWNER`, `MANAGER`, `MEMBER`).
  - `update(User $user, TaskComment $comment)`: User must be the author of the comment (`$comment->user_id === $user->id`).
  - `delete(User $user, TaskComment $comment)`: User must be the author, OR hold `OWNER` / `MANAGER` role in the project (moderation).

### Task 8.2: Comment Endpoints & Controller
- [ ] **Step 1:** Create `app/Http/Requests/Comment/StoreCommentRequest.php`:
  - `body`: `['required', 'string', 'max:5000']`
- [ ] **Step 2:** Create `app/Http/Requests/Comment/UpdateCommentRequest.php`:
  - `body`: `['required', 'string', 'max:5000']`
- [ ] **Step 3:** Create `app/Http/Resources/CommentResource.php`:
  - Exposes: `id`, `task_id`, `user` (`UserResource`), `body`, `created_at`, `updated_at`.
- [ ] **Step 4:** Create `app/Http/Controllers/Api/V1/CommentController.php`:
  - `index(Task $task)`: Authorizes task view, returns paginated comments with authors eager loaded.
  - `store(StoreCommentRequest $request, Task $task)`: Creates comment, dispatches `TaskCommentCreated` event, returns 201 with `CommentResource`.
  - `update(UpdateCommentRequest $request, TaskComment $comment)`: Authorizes with `update`, modifies body.
  - `destroy(TaskComment $comment)`: Authorizes with `delete`, removes row, returns 204.

### Task 8.3: Attachment Security & Storage Configuration
- [ ] **Step 1:** Configure private storage disk in `config/filesystems.php`:
  - Ensure private disk (e.g., `local` disk pointing to `storage/app/private/task_attachments`).
  - Verify that directory is **outside** `public/` web root.
- [ ] **Step 2:** Create `app/Policies/AttachmentPolicy.php`:
  - `upload(User $user, Task $task)`: Must be member of project.
  - `download(User $user, TaskAttachment $attachment)`: Must have `view` permission on the task's project.
  - `delete(User $user, TaskAttachment $attachment)`: Must be uploader or project `OWNER`/`MANAGER`.

### Task 8.4: Attachment Upload, Download & Streaming
- [ ] **Step 1:** Create `app/Http/Requests/Attachment/UploadAttachmentRequest.php`:
  - `file`: `['required', 'file', 'max:10240', 'mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg,zip,txt']` (Max 10MB, strictly no executable or script uploads).
- [ ] **Step 2:** Create `app/Http/Resources/AttachmentResource.php`:
  - Exposes: `id`, `task_id`, `uploader` (`UserResource`), `original_name`, `mime_type`, `size`, `created_at`.
  - Omits internal storage `path` or `disk` to prevent path disclosure.
- [ ] **Step 3:** Create `app/Http/Controllers/Api/V1/AttachmentController.php`:
  - `upload(UploadAttachmentRequest $request, Task $task)`:
    - Authorizes with policy.
    - Generates a UUID filename: `$storedPath = $request->file('file')->store('attachments/' . $task->id, 'local')`.
    - Persists record in `task_attachments`:
      ```php
      $task->attachments()->create([
          'uploaded_by' => $request->user()->id,
          'disk' => 'local',
          'path' => $storedPath,
          'original_name' => $request->file('file')->getClientOriginalName(),
          'mime_type' => $request->file('file')->getMimeType(),
          'size' => $request->file('file')->getSize(),
      ]);
      ```
    - Returns 201 with `AttachmentResource`.
  - `download(Task $task, TaskAttachment $attachment)`:
    - Verifies `$attachment->task_id === $task->id`.
    - Authorizes caller with `download` policy.
    - Returns streamed download:
      ```php
      return Storage::disk($attachment->disk)->download(
          $attachment->path,
          $attachment->original_name,
          ['Content-Type' => $attachment->mime_type]
      );
      ```
  - `destroy(Task $task, TaskAttachment $attachment)`:
    - Authorizes with `delete`.
    - Deletes file from storage disk.
    - Deletes database record.
    - Returns 204.

### Task 8.5: Automated Feature Tests
- [ ] **Step 1:** Create `tests/Feature/CommentTest.php`:
  - Member can comment on project task.
  - Non-member is blocked (403).
  - User cannot edit another user's comment (403).
  - Project manager can delete inappropriate comments (moderation).
- [ ] **Step 2:** Create `tests/Feature/AttachmentTest.php`:
  - File upload stores binary in private storage.
  - Uploading disallowed extensions (e.g. `.php`, `.sh`) fails with 422.
  - Authorized project member can download file.
  - Non-member receives 403 on download attempt (IDOR prevention).
  - Deleting attachment removes physical file from disk.
- [ ] **Step 3:** Run `php artisan test --filter=AttachmentTest`.

---

## Acceptance Criteria
- [x] Comments allow multi-user collaboration with author-exclusive editing and manager moderation.
- [x] File uploads enforce strict MIME type and 10MB size limits.
- [x] Files are saved to private storage outside the web root.
- [x] Download endpoint prevents IDOR and verifies project membership.
- [x] All comment and attachment tests pass.

