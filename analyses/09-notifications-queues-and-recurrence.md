# Phase 9: Background Processing — Events, Queues, Notifications & Recurrence

> **Spec Reference:** Section 5.13 (`recurring_task_rules`), Section 8.6 (Notification & Recurrence Endpoints), Section 10.4 (Recurrence Logic), Section 11 (Events/Jobs/Notifications), Section 13 (Phase 8)  
> **Primary Goal:** Implement asynchronous queued notifications for task assignments and status updates, due-soon deadline reminders, and an idempotent recurring task engine triggered by the Laravel Scheduler.

---

## High-Level Objective
Decouple time-consuming operations from the HTTP request cycle using Laravel Queues and Events. Manage user in-app/email notifications, and automate recurring task creation and deadline alerts using scheduled background workers.

---

## Detailed Task Breakdown

### Task 9.1: Queue & Notification Infrastructure
- [ ] **Step 1:** Configure queue driver in `.env` (`QUEUE_CONNECTION=database`).
- [ ] **Step 2:** Ensure queue tables exist (`php artisan queue:table`, `php artisan queue:failed-table`, `php artisan migrate`).
- [ ] **Step 3:** Ensure notifications table exists (`php artisan notifications:table`, `php artisan migrate`).
- [ ] **Step 4:** Define Domain Events in `app/Events/`:
  - `TaskAssigned(Task $task, User $assignee, User $assigner)`
  - `TaskStatusChanged(Task $task, string $oldStatus, string $newStatus, User $changer)`
  - `TaskDueSoon(Task $task)`

### Task 9.2: Queued Notifications
- [ ] **Step 1:** Create `app/Notifications/TaskAssignedNotification.php`:
  - Implements `ShouldQueue`.
  - Channels: `['database', 'mail']` (or `database` only in local environment).
  - Stores formatted payload: task ID, title, project name, assigner name.
- [ ] **Step 2:** Create `app/Notifications/TaskStatusChangedNotification.php`:
  - Implements `ShouldQueue`.
  - Informs creator and other assignees of state change.
- [ ] **Step 3:** Create `app/Notifications/TaskDueSoonNotification.php`:
  - Implements `ShouldQueue`.
  - Alerts assignees of upcoming deadline.
- [ ] **Step 4:** Attach Event Listeners in `EventServiceProvider` or via automatic discovery:
  - `SendTaskAssignedNotification` -> dispatches notification.
  - `SendTaskStatusChangedNotification` -> dispatches notification.

### Task 9.3: Notification API Endpoints
- [ ] **Step 1:** Create `app/Http/Resources/NotificationResource.php`:
  - Exposes: `id`, `type`, `data`, `read_at`, `created_at`.
- [ ] **Step 2:** Create `app/Http/Controllers/Api/V1/NotificationController.php`:
  - `index(Request $request)`:
    - Scoped strictly to `$request->user()->notifications()->paginate(20)`.
    - Never accept user ID from payload.
  - `markAsRead(Request $request, string $id)`:
    - Queries `$request->user()->notifications()->findOrFail($id)`.
    - Calls `markAsRead()`.
    - Returns 200 OK.
  - `markAllAsRead(Request $request)`:
    - Calls `$request->user()->unreadNotifications->markAsRead()`.

### Task 9.4: Recurrence Rule Engine & Service
- [ ] **Step 1:** Create `app/Http/Requests/Recurrence/StoreRecurrenceRuleRequest.php`:
  - `frequency`: `['required', Rule::in(['DAILY', 'WEEKLY', 'MONTHLY'])]`
  - `interval`: `['required', 'integer', 'min:1', 'max:365']`
  - `starts_at`: `['required', 'date']`
  - `ends_at`: `['nullable', 'date', 'after:starts_at']`
  - `timezone`: `['sometimes', 'string', 'timezone']`
- [ ] **Step 2:** Create `app/Services/RecurrenceService.php`:
  - `calculateNextRun(Carbon $current, string $frequency, int $interval): Carbon`:
    - Daily: `$current->addDays($interval)`.
    - Weekly: `$current->addWeeks($interval)`.
    - Monthly: `$current->addMonths($interval)`.
  - `processDueRules(): int`:
    - Finds active rules where `next_run_at <= now()`.
    - For each rule inside a transaction:
      - Checks if `ends_at` is reached; deactivates rule if expired.
      - Generates a **new independent clone** of the template task:
        - Same `project_id`, `title`, `description`, `priority`.
        - Calculated `start_at` and `due_at`.
        - Clones assignees list.
      - Advances `next_run_at = calculateNextRun($rule->next_run_at, $rule->frequency, $rule->interval)`.
      - Prevents duplicate occurrence generation using an idempotency key or atomic lock.
- [ ] **Step 3:** Create Artisan Command `app/Console/Commands/ProcessRecurringTasksCommand.php`:
  - Signature: `tasks:process-recurring`.
  - Invokes `RecurrenceService::processDueRules()`.

### Task 9.5: Deadline Reminders Console Command
- [ ] **Step 1:** Create `app/Console/Commands/SendDeadlineRemindersCommand.php`:
  - Signature: `tasks:send-due-reminders`.
  - Scans tasks with `status != DONE`, `due_at` between `now()` and `now()->addHours(24)`.
  - Dispatches `TaskDueSoon` event or queues `TaskDueSoonNotification`.
  - Ensures a reminder is sent at most once per task (using a `reminded_at` flag or cache tag).

### Task 9.6: Laravel Scheduler Registration
- [ ] **Step 1:** In `routes/console.php`:
  ```php
  Schedule::command('tasks:process-recurring')->hourly()->withoutOverlapping();
  Schedule::command('tasks:send-due-reminders')->dailyAt('08:00')->withoutOverlapping();
  ```

### Task 9.7: Feature Tests for Queues & Recurrence
- [ ] **Step 1:** Create `tests/Feature/NotificationTest.php`:
  - Assigning a user queues `TaskAssignedNotification` (`Notification::fake()`).
  - User can view their notifications.
  - User cannot view another user's notifications (404/403).
  - Marking notification as read updates `read_at`.
- [ ] **Step 2:** Create `tests/Feature/RecurrenceTest.php`:
  - Recurring rule generates new task instance when `tasks:process-recurring` runs.
  - `next_run_at` advances correctly based on interval.
  - Scheduler does not create duplicate tasks on retry.
- [ ] **Step 3:** Run `php artisan test --filter=NotificationTest` and `RecurrenceTest`.

---

## Acceptance Criteria
- [x] Assignment and status updates queue asynchronous notifications.
- [x] Notifications are strictly isolated to the authenticated recipient.
- [x] Scheduled recurrence creates independent cloned tasks without duplication.
- [x] Deadline reminders identify approaching due dates within 24 hours.
- [x] Full test coverage with `Notification::fake()` and mock schedules.

