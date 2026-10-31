# Testing Plan: Comments & Attachments

## Test Structure
Tests in `tests/Feature/CommentTest.php` and `tests/Feature/AttachmentTest.php` with `DatabaseTransactions` and `Storage::fake('local')`.

---

## 1. Comment Lifecycle & Moderation
1. **Comment Creation:**
   - Project `MEMBER` posts a comment on task.
   - Assert HTTP 200, envelope `key: 'success'`, `code: 201`.
   - Assert `task_comments` has row with `task_id`, `user_id`, `body`.
2. **Access Control:**
   - Non-project member attempts to post comment -> 403 Forbidden.
   - Project `VIEWER` attempts to post comment -> 403 Forbidden.
3. **Editing:**
   - Author updates comment body -> 200 Success.
   - Another member attempts to update author's comment -> 403 Forbidden.
4. **Deletion & Moderation:**
   - Another member attempts to delete author's comment -> 403 Forbidden.
   - Project `OWNER` or `MANAGER` deletes comment -> 200 Success (moderation).
   - Author deletes own comment -> 200 Success.
   - Assert soft-deleted in database (`deleted_at` not null).

---

## 2. File Upload, Storage & Streaming Downloads
1. **Valid Upload:**
   - Fake `local` storage disk.
   - Upload `document.pdf` (1MB).
   - Assert HTTP 200, envelope `key: 'success'`, `code: 201`.
   - Assert file exists in `attachments/{task_id}/...` on private disk.
   - Assert response omits storage path, includes `original_name`, `mime_type`, `size`.
2. **Security & Validation:**
   - Upload file with disallowed extension (`malicious.php`, `script.sh`) -> 422 Unprocessable.
   - Upload file exceeding 10MB -> 422 Unprocessable.
3. **Download & IDOR Prevention:**
   - Project member requests `GET /api/v1/tasks/{task}/attachments/{attachment}/download`.
   - Assert 200 with streamed binary content and `Content-Disposition: attachment; filename="document.pdf"`.
   - Outsider requests download for the same attachment -> 403 Forbidden.
   - Request download with mismatched `task_id` -> 404/403.
4. **Attachment Deletion:**
   - Project manager or uploader deletes attachment.
   - Assert database row soft-deleted.
   - Assert physical file removed from storage disk.

