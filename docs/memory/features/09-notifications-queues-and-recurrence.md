# Feature Memory: 09 - Background Processing, Notifications & Recurrence

## Context
Implemented Phase 9: Background Processing — Events, Queues, Notifications & Recurrence. Decouples asynchronous event handling from HTTP requests, manages in-app database notifications, automates recurring task generation, and triggers scheduled deadline alerts.

## Implementation Details
- **Architecture Mode:** MODE_A (`Controller -> FormRequest -> DTO -> Service -> Repository -> Model`).
- **Events & Queued Notifications:**
  - `TaskAssigned` event & `SendTaskAssignedNotification` listener -> `TaskAssignedNotification` (implements `ShouldQueue`, database channel).
  - `TaskStatusChanged` event & `SendTaskStatusChangedNotification` listener -> `TaskStatusChangedNotification` (implements `ShouldQueue`, database channel).
  - `TaskDueSoon` event -> `TaskDueSoonNotification` (implements `ShouldQueue`, database channel).
- **Repositories & Service Provider:**
  - `NotificationRepositoryInterface` & `NotificationRepository`.
  - `RecurringTaskRuleRepositoryInterface` & `RecurringTaskRuleRepository`.
  - Bound in `RepositoryServiceProvider`.
- **Services:**
  - `NotificationService`: scoped pagination, single mark-as-read, batch mark-all-as-read.
  - `RecurrenceService`: frequency calculations (DAILY, WEEKLY, MONTHLY), transactional task cloning, assignee preservation, idempotency locks, and expiration deactivation.
- **Console Commands & Scheduler:**
  - `tasks:process-recurring`: automated task generation.
  - `tasks:send-due-reminders`: scans tasks due within lookahead window (default 24h) with cache lock idempotency.
  - Registered in `routes/console.php`.
- **OpenAPI & Apidog:**
  - 4 new paths added to `GenerateOpenApiSpec.php` (total 33 paths documented).
  - Verified in `OpenApiSpecTest.php`.
- **Verification:**
  - `NotificationTest.php`: 6 tests passing.
  - `RecurrenceTest.php`: 5 tests passing.
  - `DeadlineReminderTest.php`: 3 tests passing.
  - Full test suite: 129 passed (692 assertions, 0 failures).
