# Testing Strategy: Phase 9

## Objectives
Validate asynchronous event-driven notifications, queue dispatching, user isolation, idempotency of the recurrence engine, and deadline reminder scheduling without external email dependencies.

## Key Feature Tests

1. `tests/Feature/NotificationTest.php`:
   - `Notification::fake()` / `Event::fake()` assertion when task assignee is added.
   - User lists unread and read notifications with pagination metadata.
   - User cannot view or mark another user's notification as read (returns 404/not_found).
   - Marking single notification as read updates `read_at` timestamp.
   - Marking all notifications as read updates all `read_at` timestamps for that user only.

2. `tests/Feature/RecurrenceTest.php`:
   - Test rule frequency calculations:
     - DAILY: `+interval days`
     - WEEKLY: `+interval weeks`
     - MONTHLY: `+interval months`
   - Command `tasks:process-recurring` creates cloned task instance with proper status (`TODO`), clean subtasks, and matching assignees.
   - Ensures rule is deactivated once `ends_at` is surpassed.
   - Ensures running command twice in the same hour does NOT duplicate task instances.

3. `tests/Feature/DeadlineReminderTest.php`:
   - Tasks due in 12 hours receive notification.
   - Tasks due in 36 hours or already completed (`DONE`) do NOT receive notification.
   - Tasks already notified (using cache tag or metadata) are not notified a second time.
