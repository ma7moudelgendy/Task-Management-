# Implementation Phases: Notifications, Queues & Recurrence

## Checklist

- [x] **Phase 1: Events & Queued Notifications Domain**
  - [x] Create Events:
    - `App\Events\TaskAssigned`
    - `App\Events\TaskStatusChanged`
    - `App\Events\TaskDueSoon`
  - [x] Create Queued Notifications:
    - `App\Notifications\TaskAssignedNotification`
    - `App\Notifications\TaskStatusChangedNotification`
    - `App\Notifications\TaskDueSoonNotification`
  - [x] Create Listeners:
    - `App\Listeners\SendTaskAssignedNotification`
    - `App\Listeners\SendTaskStatusChangedNotification`
  - [x] Wire listeners in `EventServiceProvider` / event discovery.
  - [x] Trigger events in `TaskService::syncAssignees()` / `TaskController` and `TaskWorkflowService::changeStatus()`.

- [x] **Phase 2: Repositories & MODE_A Layer for Notifications and Recurrence**
  - [x] Create `NotificationRepositoryInterface` & `NotificationRepository`.
  - [x] Create `RecurringTaskRuleRepositoryInterface` & `RecurringTaskRuleRepository`.
  - [x] Register bindings in `RepositoryServiceProvider`.

- [x] **Phase 3: Form Requests & DTOs**
  - [x] Create `StoreRecurrenceRuleRequest` & `StoreRecurrenceRuleDTO`.

- [x] **Phase 4: Service Layer**
  - [x] Implement `App\Services\NotificationService` (paginated notifications, mark single as read, mark all as read).
  - [x] Implement `App\Services\RecurrenceService` (calculateNextRun, processDueRules with transaction and clone logic).

- [x] **Phase 5: API Resources & Controllers**
  - [x] Create `App\Http\Resources\Api\NotificationResource`.
  - [x] Create `App\Http\Resources\Api\RecurringTaskRuleResource`.
  - [x] Implement `App\Http\Controllers\Api\V1\NotificationController`.
  - [x] Create routes file `routes/guards/v1/notifications.php` and mount in `routes/api.php`.

- [x] **Phase 6: Artisan Console Commands & Scheduler**
  - [x] Implement `App\Console\Commands\ProcessRecurringTasksCommand` (`tasks:process-recurring`).
  - [x] Implement `App\Console\Commands\SendDeadlineRemindersCommand` (`tasks:send-due-reminders`).
  - [x] Register schedule in `routes/console.php`.

- [x] **Phase 7: Feature Tests**
  - [x] Create `tests/Feature/NotificationTest.php`:
    - Assigning task queues notification.
    - Status change queues notification.
    - User can list their own notifications.
    - User cannot access another user's notifications.
    - Mark single notification as read.
    - Mark all notifications as read.
  - [x] Create `tests/Feature/RecurrenceTest.php`:
    - Recurring rule advances `next_run_at`.
    - Cloned task has identical assignees, priority, and projected dates.
    - Idempotency test (no duplicates created on retry).
    - Deactivation when `ends_at` is reached.
  - [x] Create `tests/Feature/DeadlineReminderTest.php`:
    - Identifies tasks due within 24 hours.
    - Dispatches due-soon notification without repeating if already reminded.

- [x] **Phase 8: OpenAPI Specification & Verification**
  - [x] Update `GenerateOpenApiSpec.php` with Notifications and Recurrence schemas & paths.
  - [x] Update `OpenApiSpecTest.php`.
  - [x] Run full test suite with real assertions.

- [x] **Phase 9: Documentation & Memory Synchronization**
  - [x] Create `api-docs/frontend-docs/user/notifications.md`.
  - [x] Create `docs/memory/api/v1-notifications.md`.
  - [x] Create `docs/memory/features/09-notifications-queues-and-recurrence.md`.
  - [x] Update `docs/memory/INDEX.md`.
