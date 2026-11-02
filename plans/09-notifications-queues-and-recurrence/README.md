# Phase 9: Background Processing — Events, Queues, Notifications & Recurrence

## Overview
Phase 9 implements the background processing, events, queues, user notifications, deadline reminders, and recurring tasks engine for TaskFlow.

## Architecture
Strict adherence to **MODE_A**:
`Controller -> FormRequest -> DTO -> Service -> Repository -> Model`

## Key Capabilities
1. **Domain Events & Queued Notifications:**
   - `TaskAssigned` -> `SendTaskAssignedNotification` -> `TaskAssignedNotification` (queued).
   - `TaskStatusChanged` -> `SendTaskStatusChangedNotification` -> `TaskStatusChangedNotification` (queued).
   - `TaskDueSoon` -> `TaskDueSoonNotification` (queued).
2. **Notification API Endpoints:**
   - `GET /api/v1/notifications` (paginated, scoped to `$request->user()`).
   - `POST /api/v1/notifications/{id}/read` (mark single notification as read).
   - `POST /api/v1/notifications/read-all` (mark all unread as read).
3. **Recurring Tasks Engine:**
   - `RecurringTaskRule` model and repository (`RecurringTaskRuleRepositoryInterface`).
   - `RecurrenceService` for calculating next run dates (DAILY, WEEKLY, MONTHLY), cloning template tasks with assignees and dates, and idempotency protection.
   - Artisan Command: `tasks:process-recurring`.
4. **Deadline Reminders:**
   - Artisan Command: `tasks:send-due-reminders` (scans incomplete tasks due within 24 hours, dispatches notifications).
5. **Scheduler:**
   - Registered in `routes/console.php`.
6. **OpenAPI & Apidog:**
   - Endpoints and schemas added to `GenerateOpenApiSpec.php`.
   - Live sync with Apidog Project `1405307`.
