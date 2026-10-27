# TaskFlow --- Advanced Task Management System

> **Project type:** Portfolio-grade Backend API\
> **Technology:** Laravel + MySQL + Laravel Sanctum\
> **API style:** REST, versioned under `/api/v1`\
> **Primary goal:** Build a secure, maintainable task and project
> management backend with teams, permissions, task workflows,
> notifications, reporting, tests, and production deployment.

------------------------------------------------------------------------

## 1. Project Overview

TaskFlow is a task and project management system for individuals and
teams. Users can create teams and projects, invite or add members,
assign tasks, set priorities and deadlines, track progress, discuss work
through comments, attach files, and receive notifications about
important changes.

The project is intentionally more advanced than a basic To-Do CRUD
application. It demonstrates practical backend engineering skills:
relational database design, authentication, authorization, validation,
business rules, background jobs, testing, API documentation,
performance, and deployment.

### Main goals

-   Secure user registration, login, logout, and profile management.
-   Manage teams, projects, and membership roles.
-   Create, assign, filter, and track tasks.
-   Support subtasks and task dependencies.
-   Record status changes and important activities.
-   Support comments, attachments, and notifications.
-   Provide dashboards and project analytics.
-   Support recurring tasks and deadline reminders.
-   Deliver a documented, tested, production-ready API.

### Out of scope for the first release

-   Real-time collaborative editing.
-   Full billing/subscription management.
-   AI task planning.
-   A complete frontend application.

These can be considered future enhancements after the core backend is
stable.

------------------------------------------------------------------------

## 2. Roles and Permissions

TaskFlow has two distinct permission scopes:

1.  **System-level role:** permissions across the application, such as a
    system administrator.
2.  **Project-level role:** permissions within a particular project.

A user may be a `MEMBER` in one project and a `MANAGER` in another. Team
membership does not automatically imply membership in every project
owned by that team unless a deliberate product rule says otherwise.

### Suggested roles

  -----------------------------------------------------------------------
  Role                                Typical permissions
  ----------------------------------- -----------------------------------
  `ADMIN`                             Manage system-level users and
                                      operational settings; all elevated
                                      actions must be explicit and
                                      audited.

  `OWNER`                             Manage a project, project settings,
                                      members, and project lifecycle.

  `MANAGER`                           Manage tasks and project members
                                      within granted limits.

  `MEMBER`                            View permitted project data, work
                                      on assigned tasks, and comment
                                      where allowed.

  `VIEWER`                            Read-only access to permitted
                                      project data.
  -----------------------------------------------------------------------

Use Policies for authorization. Do not rely on frontend controls or on
knowing an object ID to grant access.

------------------------------------------------------------------------

## 3. Technology Stack

-   **PHP:** version supported by the selected Laravel release.
-   **Laravel:** current project-compatible stable release.
-   **MySQL:** relational persistence.
-   **Laravel Sanctum:** API bearer tokens, or cookie-based SPA
    authentication if the deployment architecture calls for it.
-   **Eloquent ORM:** models and relationships.
-   **Form Requests:** request validation and authorization hooks where
    appropriate.
-   **API Resources:** consistent JSON serialization.
-   **Policies and Middleware:** access control.
-   **Queues and Jobs:** asynchronous work.
-   **Laravel Notifications:** database and/or mail notifications.
-   **Scheduler:** recurring tasks and deadline reminders.
-   **PHPUnit or Pest:** automated testing.
-   **Postman:** manual API testing.
-   **OpenAPI/Swagger tooling:** API documentation, selected after the
    API shape stabilizes.

Keep dependencies minimal and compatible with the Laravel version
installed.

------------------------------------------------------------------------

## 4. Architecture

Use a modular Laravel architecture with clear responsibilities. Avoid
adding layers just for the sake of adding them.

``` text
Client (Postman / Web / Mobile)
              |
              v
       API Routes (/api/v1)
              |
              v
 Middleware: authentication, throttling
              |
              v
 Form Requests: input validation
 Policies: authorization
              |
              v
          Controllers
              |
              v
           Services
   business rules / transactions
              |
              v
      Eloquent Models / MySQL
              |
              +---- Events / Listeners
              |             |
              |             v
              |       Jobs / Queues
              |             |
              |             v
              +------ Notifications
```

### Responsibilities

-   **Routes:** map HTTP methods and paths to controllers.
-   **Middleware:** authenticate users, throttle requests, and apply
    cross-cutting checks.
-   **Form Requests:** validate incoming payloads and query parameters.
-   **Policies:** decide whether a user may perform an action on a
    resource.
-   **Controllers:** coordinate request handling and return responses;
    keep them thin.
-   **Services:** contain meaningful business operations, such as safely
    completing a task or changing project membership.
-   **Models:** define persistence and Eloquent relationships.
-   **API Resources:** control the JSON representation returned to
    clients.
-   **Events:** announce that a business event occurred, such as
    `TaskAssigned`.
-   **Listeners:** respond to events.
-   **Jobs:** perform work that can be queued, such as sending
    reminders.
-   **Notifications:** represent messages delivered to users.
-   **Repositories:** add only when there is a clear benefit, such as
    isolating complex data access. Do not create a repository for every
    model automatically.

### Suggested directory structure

``` text
app/
├── Events/
├── Http/
│   ├── Controllers/Api/V1/
│   ├── Middleware/
│   ├── Requests/
│   └── Resources/
├── Jobs/
├── Listeners/
├── Models/
├── Notifications/
├── Policies/
└── Services/

database/
├── factories/
├── migrations/
└── seeders/

routes/
└── api.php

tests/
├── Feature/
└── Unit/
```

Create files incrementally as each feature is implemented.

------------------------------------------------------------------------

## 5. Database Design

Use InnoDB, foreign keys, unique constraints, appropriate indexes, and
consistent timestamps. Use Laravel migrations to manage schema changes.

### 5.1 `users`

Suggested fields:

-   `id`
-   `name`
-   `email` --- unique
-   `password` --- password hash only
-   `is_active` --- boolean, default true
-   `email_verified_at` --- nullable timestamp
-   `remember_token` --- if used by the selected authentication setup
-   `created_at`, `updated_at`

System-level roles can be implemented with a simple `role` field for a
small fixed role set, or a dedicated role/permission package if
requirements justify the added dependency. Do not confuse this with
project membership roles.

### 5.2 `teams`

-   `id`
-   `name`
-   `slug` --- unique or unique within an explicitly defined scope
-   `created_by` --- foreign key to `users.id`
-   `created_at`, `updated_at`

### 5.3 `team_members`

-   `id`
-   `team_id` --- foreign key
-   `user_id` --- foreign key
-   `role` --- e.g. `OWNER`, `MANAGER`, `MEMBER`
-   `joined_at`
-   timestamps if needed

Constraints:

-   Unique (`team_id`, `user_id`).
-   Index `user_id` for membership lookups.
-   Define a clear rule for whether a team must always retain an owner.

### 5.4 `projects`

-   `id`
-   `team_id` --- foreign key
-   `created_by` --- foreign key to `users.id`
-   `name`
-   `description` --- nullable text
-   `status` --- e.g. `ACTIVE`, `ON_HOLD`, `COMPLETED`, `ARCHIVED`
-   `start_date` --- nullable
-   `due_date` --- nullable
-   timestamps
-   `deleted_at` only if soft deletion is deliberately adopted

A project belongs to a team. Decide whether every project must belong to
a team or whether personal projects are supported; do not leave this
ambiguous in implementation.

### 5.5 `project_members`

-   `id`
-   `project_id` --- foreign key
-   `user_id` --- foreign key
-   `role` --- `OWNER`, `MANAGER`, `MEMBER`, `VIEWER`
-   `joined_at`
-   timestamps if needed

Constraints:

-   Unique (`project_id`, `user_id`).
-   Index (`user_id`, `project_id`) as appropriate for common queries.
-   Prevent removing or demoting the final project owner unless
    ownership is transferred in the same operation.

### 5.6 `tasks`

-   `id`
-   `project_id` --- foreign key
-   `parent_task_id` --- nullable self-reference for subtasks
-   `created_by` --- foreign key to `users.id`
-   `title`
-   `description` --- nullable long text
-   `status` --- `TODO`, `IN_PROGRESS`, `IN_REVIEW`, `DONE`, `CANCELLED`
-   `priority` --- `LOW`, `MEDIUM`, `HIGH`, `URGENT`
-   `start_at` --- nullable datetime
-   `due_at` --- nullable datetime
-   `completed_at` --- nullable datetime
-   `position` --- integer for ordering tasks in a board
-   timestamps
-   optional `deleted_at` if soft deletion is adopted

Rules:

-   A due date must not precede the start date when both are present.
-   Subtasks must belong to the same project as their parent.
-   Prevent a task from being its own parent.
-   Define whether a parent can be completed while its subtasks remain
    incomplete.
-   Store datetimes consistently, preferably in UTC, and document API
    timezone expectations.

### 5.7 `task_assignees`

-   `id`
-   `task_id` --- foreign key
-   `user_id` --- foreign key
-   `assigned_by` --- foreign key to `users.id`
-   `assigned_at`
-   Unique (`task_id`, `user_id`)

A task may have multiple assignees. If the product later requires
exactly one assignee, simplify the design only after making that product
decision explicit.

Business rule: every assignee must be a member of the task's project at
assignment time.

### 5.8 `task_dependencies`

-   `id` or a composite primary key
-   `task_id` --- the task that is blocked
-   `depends_on_task_id` --- the prerequisite task
-   timestamps only if useful

Constraints and rules:

-   Unique (`task_id`, `depends_on_task_id`).
-   A task cannot depend on itself.
-   Both tasks must belong to the same project unless cross-project
    dependencies are explicitly supported.
-   Detect cycles, not only direct self-dependencies.
-   A task cannot be marked complete while required dependencies remain
    incomplete, if that is the selected workflow rule.

### 5.9 `task_comments`

-   `id`
-   `task_id` --- foreign key
-   `user_id` --- foreign key
-   `body` --- text
-   timestamps

Only authorized project members should read or create comments. Define
whether users may edit or delete only their own comments, and whether
managers have moderation permissions.

### 5.10 `task_attachments`

-   `id`
-   `task_id` --- foreign key
-   `uploaded_by` --- foreign key to `users.id`
-   `disk`
-   `path`
-   `original_name`
-   `mime_type`
-   `size`
-   timestamps

Store file metadata in MySQL and file bytes on a configured storage
disk. Prefer private storage for confidential project files. Verify file
size and type, generate safe storage names, and authorize every
download.

### 5.11 `task_status_histories`

-   `id`
-   `task_id` --- foreign key
-   `changed_by` --- foreign key to `users.id`
-   `old_status`
-   `new_status`
-   optional `reason`
-   `created_at`

Create history records whenever a status transition succeeds. History is
different from a notification: history is an audit trail; a notification
informs a user.

### 5.12 `activity_logs`

Suggested fields:

-   `id`
-   `actor_id` --- nullable foreign key if system actions are supported
-   `team_id` --- nullable
-   `project_id` --- nullable
-   `subject_type`, `subject_id` --- if polymorphic subjects are
    required
-   `action`
-   `metadata` --- JSON where appropriate
-   `created_at`

Log important actions such as membership changes, assignment changes,
deadline changes, and status transitions. Avoid storing passwords,
tokens, secrets, or unnecessary sensitive payloads.

### 5.13 `recurring_task_rules`

Suggested fields:

-   `id`
-   `task_id` --- reference to a template task, or a separate
    recurrence-template model if recurring instances need independent
    lifecycles
-   `frequency` --- e.g. `DAILY`, `WEEKLY`, `MONTHLY`
-   `interval` --- positive integer
-   `starts_at`
-   `ends_at` --- nullable
-   `next_run_at`
-   `timezone`
-   `is_active`
-   timestamps

Define what happens to the recurrence rule when a generated task is
edited or completed. Avoid creating duplicate instances when a scheduler
retries.

### 5.14 Notifications and queues

-   `notifications`: Laravel's database notification table when database
    notifications are enabled.
-   `personal_access_tokens`: Sanctum token storage.
-   `jobs` and `failed_jobs`: queue tables when using the database queue
    driver.
-   `job_batches`: if batch processing is used.
-   `migrations`: Laravel-managed migration history.

Create the framework tables through the appropriate Artisan commands for
the installed Laravel version.

------------------------------------------------------------------------

## 6. Entity Relationships

Core relationships:

-   User has many teams created.
-   User belongs to many teams through `team_members`.
-   Team has many team members and projects.
-   Project belongs to a team.
-   User belongs to many projects through `project_members`.
-   Project has many tasks.
-   Task belongs to a project.
-   Task may have a parent task and many subtasks.
-   Task belongs to many assignees through `task_assignees`.
-   Task has many comments, attachments, and status history records.
-   Task has many dependency edges through `task_dependencies`.
-   User has many notifications.

Keep team membership and project membership separate. Being on a team
must not automatically grant access to all its projects unless that
behavior is an intentional, tested product rule.

------------------------------------------------------------------------

## 7. API Conventions

Base URL:

``` text
/api/v1
```

### Response shape

Use Laravel API Resources to keep response formats consistent. Example:

``` json
{
  "data": {
    "id": 42,
    "title": "Implement authentication",
    "status": "TODO",
    "priority": "HIGH",
    "due_at": "2026-11-05T15:00:00Z",
    "assignees": [
      {
        "id": 7,
        "name": "Team Member"
      }
    ]
  }
}
```

This is an illustrative response shape. Ensure serialization matches
actual model fields and relationships. Do not expose password hashes,
access tokens, internal secrets, or unnecessary fields.

### HTTP status conventions

-   `200 OK`: successful read or update.
-   `201 Created`: resource created.
-   `204 No Content`: successful deletion where no body is returned.
-   `401 Unauthorized`: missing or invalid authentication.
-   `403 Forbidden`: authenticated user lacks permission.
-   `404 Not Found`: resource not found or deliberately concealed to
    avoid leaking existence.
-   `409 Conflict`: conflict with current state, if used consistently.
-   `422 Unprocessable Content`: validation errors.
-   `429 Too Many Requests`: rate limit exceeded.
-   `500 Internal Server Error`: unexpected server failure, without
    leaking stack traces in production.

Use a consistent error format and Laravel's validation behavior or a
documented custom envelope.

------------------------------------------------------------------------

## 8. API Endpoint Catalog

All endpoints below are under `/api/v1`. Protect private routes with the
appropriate Sanctum authentication middleware and apply Policies or
equivalent authorization checks.

### 8.1 Authentication and profile

  -------------------------------------------------------------------------
  Method                  Endpoint                  Purpose
  ----------------------- ------------------------- -----------------------
  `POST`                  `/auth/register`          Create an account

  `POST`                  `/auth/login`             Authenticate and issue
                                                    a token

  `POST`                  `/auth/logout`            Revoke the current
                                                    token

  `GET`                   `/auth/me`                Get the authenticated
                                                    user

  `PATCH`                 `/auth/me`                Update profile fields

  `POST`                  `/auth/forgot-password`   Request a password
                                                    reset

  `POST`                  `/auth/reset-password`    Reset password using a
                                                    valid reset token
  -------------------------------------------------------------------------

Sanctum bearer tokens suit mobile clients, external API clients, and
many Postman workflows. For a first-party SPA on the same top-level
domain, consider Sanctum's cookie-based SPA authentication and follow
its CSRF and domain configuration requirements.

### 8.2 Teams and memberships

  --------------------------------------------------------------------------------
  Method                  Endpoint                         Purpose
  ----------------------- -------------------------------- -----------------------
  `GET`                   `/teams`                         List teams the user may
                                                           access

  `POST`                  `/teams`                         Create a team

  `GET`                   `/teams/{team}`                  Show team details

  `PATCH`                 `/teams/{team}`                  Update team

  `DELETE`                `/teams/{team}`                  Delete or archive team,
                                                           according to policy

  `GET`                   `/teams/{team}/members`          List team members

  `POST`                  `/teams/{team}/members`          Add a member

  `PATCH`                 `/teams/{team}/members/{user}`   Change team role

  `DELETE`                `/teams/{team}/members/{user}`   Remove a member
  --------------------------------------------------------------------------------

### 8.3 Projects and project memberships

  --------------------------------------------------------------------------------------
  Method                  Endpoint                               Purpose
  ----------------------- -------------------------------------- -----------------------
  `GET`                   `/projects`                            List accessible
                                                                 projects

  `POST`                  `/projects`                            Create project

  `GET`                   `/projects/{project}`                  Show project

  `PATCH`                 `/projects/{project}`                  Update project

  `DELETE`                `/projects/{project}`                  Archive or delete
                                                                 project

  `GET`                   `/projects/{project}/members`          List project members

  `POST`                  `/projects/{project}/members`          Add project member

  `PATCH`                 `/projects/{project}/members/{user}`   Change project role

  `DELETE`                `/projects/{project}/members/{user}`   Remove project member

  `GET`                   `/projects/{project}/analytics`        Project analytics
  --------------------------------------------------------------------------------------

### 8.4 Tasks

  -------------------------------------------------------------------------------------------
  Method                  Endpoint                                    Purpose
  ----------------------- ------------------------------------------- -----------------------
  `GET`                   `/tasks`                                    List tasks visible to
                                                                      the user

  `GET`                   `/projects/{project}/tasks`                 List a project's tasks

  `POST`                  `/projects/{project}/tasks`                 Create a task

  `GET`                   `/tasks/{task}`                             Show task details

  `PATCH`                 `/tasks/{task}`                             Update task fields

  `DELETE`                `/tasks/{task}`                             Archive or delete task

  `PATCH`                 `/tasks/{task}/status`                      Change task status

  `PUT`                   `/tasks/{task}/assignees`                   Replace the assignee
                                                                      list

  `POST`                  `/tasks/{task}/dependencies`                Add a dependency

  `DELETE`                `/tasks/{task}/dependencies/{dependency}`   Remove a dependency

  `GET`                   `/tasks/{task}/history`                     Read status history
                                                                      and/or audit events
  -------------------------------------------------------------------------------------------

Define clearly whether deletion is permanent or soft deletion. Prefer
archive/soft delete when preserving project history is important.

### 8.5 Comments and attachments

  ---------------------------------------------------------------------------------------------------
  Method                  Endpoint                                            Purpose
  ----------------------- --------------------------------------------------- -----------------------
  `GET`                   `/tasks/{task}/comments`                            List comments

  `POST`                  `/tasks/{task}/comments`                            Add a comment

  `PATCH`                 `/comments/{comment}`                               Edit a permitted
                                                                              comment

  `DELETE`                `/comments/{comment}`                               Delete a permitted
                                                                              comment

  `POST`                  `/tasks/{task}/attachments`                         Upload an attachment

  `DELETE`                `/tasks/{task}/attachments/{attachment}`            Delete an attachment

  `GET`                   `/tasks/{task}/attachments/{attachment}/download`   Authorized download
  ---------------------------------------------------------------------------------------------------

### 8.6 Dashboard, notifications, and recurrence

  --------------------------------------------------------------------------------------
  Method                  Endpoint                               Purpose
  ----------------------- -------------------------------------- -----------------------
  `GET`                   `/dashboard`                           User dashboard

  `GET`                   `/notifications`                       List current user's
                                                                 notifications

  `PATCH`                 `/notifications/{notification}/read`   Mark one notification
                                                                 as read

  `POST`                  `/tasks/{task}/recurrence`             Create or enable
                                                                 recurrence

  `PATCH`                 `/tasks/{task}/recurrence`             Update recurrence rule

  `DELETE`                `/tasks/{task}/recurrence`             Disable recurrence
  --------------------------------------------------------------------------------------

Notification endpoints must always scope results to the authenticated
user. Never trust a user ID supplied by the client to choose whose
notifications are returned.

### 8.7 Search, filtering, sorting, and pagination

Example:

``` text
GET /api/v1/tasks?status=IN_PROGRESS&priority=HIGH&assignee_id=7&due_before=2026-11-10&sort_by=due_at&sort_dir=asc&page=1&per_page=20
```

Rules:

-   Validate filter values.
-   Whitelist `sort_by` columns and `sort_dir` values.
-   Set a maximum `per_page` value, such as 100.
-   Scope the query to resources the user can access before returning
    results.
-   Use eager loading for relationships returned by resources.
-   Add indexes based on real query patterns and confirm them with query
    analysis.

------------------------------------------------------------------------

## 9. Authentication and Authorization Flow

Authentication answers: **Who is the caller?** Sanctum and
authentication middleware handle this.

Authorization answers: **May this caller perform this action on this
resource?** Policies and membership rules handle this.

For a request such as `PATCH /api/v1/tasks/42`:

1.  Authenticate the bearer token or applicable SPA session.
2.  Validate request data using a Form Request.
3.  Load the task within an authorized scope where practical.
4.  Authorize the requested action with a Policy.
5.  Apply business rules, including dependency and workflow checks.
6.  Persist changes in a transaction where multiple writes must succeed
    together.
7.  Write history and audit records.
8.  Dispatch events after successful persistence, using after-commit
    behavior when appropriate.
9.  Return an API Resource.

Protect against IDOR: knowing a task or project ID must never grant
access. Check authorization on reads, writes, downloads, comments,
attachments, history, and analytics.

------------------------------------------------------------------------

## 10. Business Logic and Transactions

### 10.1 Task status transitions

Use a dedicated service operation for meaningful workflow transitions,
for example `TaskService::changeStatus()`.

Suggested process:

1.  Check that the requested transition is allowed.
2.  Check whether required dependencies are complete.
3.  Confirm the caller is authorized.
4.  Start a database transaction if several database changes must be
    atomic.
5.  Update `status` and `completed_at`.
6.  Insert a `task_status_histories` row.
7.  Insert an activity log record.
8.  Commit.
9.  Dispatch notifications/events after successful commit.

Do not put complex business rules in controllers or rely only on model
observers. Observers can be useful for simple lifecycle behavior, but
explicit service methods make critical workflows easier to understand
and test.

### 10.2 Dependencies and cycles

Before adding an edge from task A to prerequisite B:

-   Confirm both tasks exist and are in an allowed scope.
-   Confirm A and B are not the same task.
-   Confirm they belong to the same project unless cross-project
    dependencies are supported.
-   Check whether adding the edge would create a cycle.
-   Insert the dependency with a unique constraint.

Consider concurrency: two simultaneous requests can invalidate a check
made before either commits. Use transactions and suitable
locking/constraints where needed, and test the race-sensitive workflow.

### 10.3 Task assignment

When assigning a task:

-   Verify the user is an active project member.
-   Verify the caller may assign tasks.
-   Use a transaction to update assignment records and related audit
    information.
-   Dispatch `TaskAssigned` after the assignment succeeds.
-   Make notification processing safe against duplicate delivery.

### 10.4 Recurring tasks

Use the Laravel Scheduler to identify recurrence rules due to run. A job
should:

-   Check the rule is still active.
-   Generate the next task according to the rule and timezone.
-   Use a unique occurrence key or equivalent idempotency mechanism to
    avoid duplicates on retries.
-   Advance `next_run_at` safely.
-   Handle end dates and daylight-saving behavior where relevant.

Document whether a recurrence creates a new independent task or reopens
a previous task. Creating independent task instances is often easier to
audit.

------------------------------------------------------------------------

## 11. Events, Jobs, Queues, and Notifications

These concepts have different responsibilities:

-   **Event:** announces that something happened, e.g. `TaskAssigned` or
    `TaskCompleted`.
-   **Listener:** responds to an event.
-   **Job:** performs work that can be queued, e.g. send a reminder or
    process an attachment.
-   **Notification:** defines a message and delivery channel, such as
    database or email.

Possible events:

-   `ProjectMemberAdded`
-   `TaskAssigned`
-   `TaskStatusChanged`
-   `TaskCommentCreated`
-   `TaskDueSoon`

Possible queued work:

-   Send assignment notification.
-   Send a deadline reminder.
-   Process uploaded files.
-   Generate a large analytics export.

Operational requirements:

-   Configure a queue driver and worker.
-   Configure retries and backoff intentionally.
-   Monitor failed jobs.
-   Make jobs idempotent where possible.
-   Dispatch events after commit when they depend on committed data.
-   Avoid assuming that a queue is exactly-once; duplicate execution can
    happen.

------------------------------------------------------------------------

## 12. Validation and Error Handling

Use Form Requests for input validation and API Resources for output
shaping.

Examples of validation rules:

-   Registration email must be valid and unique.
-   Password must satisfy a documented password policy.
-   Project name is required and bounded in length.
-   `due_at` must be a valid datetime and not earlier than `start_at`
    when both are provided.
-   Status and priority must be from the allowed set.
-   Assignee IDs must exist and must be active members of the project.
-   Uploaded files must meet size and allowed-type limits.
-   Sort fields and sort directions must be whitelisted.
-   Pagination values must be bounded.

Return useful validation messages without exposing internal exceptions.
In production, do not expose stack traces, SQL errors, filesystem paths,
tokens, or secrets.

Use Laravel's exception handling conventions and a consistent API error
format. Log unexpected failures with a correlation/request identifier
where practical.

------------------------------------------------------------------------

## 13. Implementation Roadmap

Build in this order. Each phase should be working and tested before
moving on.

### Phase 1 --- Project setup

-   Confirm PHP, Composer, MySQL, and the Laravel version.
-   Create the Laravel project.
-   Configure `.env` and MySQL credentials.
-   Run initial migrations.
-   Add Git and a `.gitignore`.
-   Confirm the application boots.

Example commands for a fresh project:

``` bash
composer create-project laravel/laravel taskflow
cd taskflow
php artisan key:generate
php artisan migrate
```

Do not commit `.env` or secrets. If Laravel or PHP is already installed,
verify versions before changing anything.

### Phase 2 --- Sanctum authentication

-   Install/configure Sanctum using the instructions for the installed
    Laravel version.
-   Build register, login, logout, and current-user endpoints.
-   Hash passwords using Laravel's supported hashing APIs.
-   Issue and revoke personal access tokens for token-based clients.
-   Add login throttling and email verification if required.
-   Test authenticated and unauthenticated requests.

For Laravel versions that support it, `php artisan install:api` can set
up API routing and Sanctum. Check whether Sanctum is already installed
before running setup commands repeatedly.

### Phase 3 --- Schema and models

-   Create migrations in dependency order.
-   Add foreign keys, unique constraints, and indexes.
-   Create models and relationships.
-   Create factories and seeders for development data.
-   Run migrations from a clean database.
-   Verify relationships through tests or Tinker.

### Phase 4 --- Teams and projects

-   Implement team creation and membership.
-   Implement project creation and membership.
-   Add Form Requests and API Resources.
-   Add Policies and authorization tests before exposing update/delete
    operations.
-   Test duplicate membership and owner-retention rules.

### Phase 5 --- Task core

-   Implement task CRUD.
-   Implement assignees and subtasks.
-   Add status and priority validation.
-   Implement search, filters, sorting, and pagination.
-   Ensure list queries are scoped and avoid N+1 queries.
-   Test task access across users and projects.

### Phase 6 --- Workflow and audit trail

-   Implement task status transition rules.
-   Implement dependency creation and cycle detection.
-   Implement completion constraints.
-   Add status history and activity logs.
-   Use transactions for operations that require multiple writes.
-   Test invalid transitions and concurrent edge cases where practical.

### Phase 7 --- Collaboration

-   Implement comments.
-   Implement private file uploads and authorized downloads.
-   Define comment edit/delete permissions.
-   Add tests for access control on attachments and comments.

### Phase 8 --- Notifications and recurrence

-   Configure database/mail notifications as required.
-   Add events, listeners, jobs, and queue workers.
-   Implement due-soon reminders using the Scheduler.
-   Implement recurring task rules and idempotent occurrence generation.
-   Test retries and duplicate execution scenarios.

### Phase 9 --- Dashboard and analytics

-   Implement task counts by status and priority.
-   Implement overdue and upcoming task lists.
-   Implement project progress metrics.
-   Define how progress is calculated, especially when subtasks or
    cancelled tasks exist.
-   Use indexed and scoped queries; cache only after measuring need.

### Phase 10 --- Testing and hardening

-   Add Feature Tests for every major endpoint.
-   Add Unit Tests for isolated business rules.
-   Test authorization failures and invalid input.
-   Test queue and notification behavior with fakes.
-   Review file upload security, rate limits, mass assignment, and
    sensitive output.
-   Add API documentation.
-   Run the full test suite from a clean test database.

### Phase 11 --- Deployment

-   Deploy to a server with a supported PHP runtime and MySQL.
-   Set production environment variables securely.
-   Run migrations using a controlled deployment process.
-   Configure web server document root to Laravel's `public` directory.
-   Configure queue workers and the scheduler.
-   Configure HTTPS, logs, backups, monitoring, and recovery procedures.
-   Verify health, login, critical API flows, and background processing
    after deployment.

------------------------------------------------------------------------

## 14. Testing Strategy

Use PHPUnit or Pest, according to the installed project's conventions.

### Feature tests

-   Registration, login, logout, and invalid-token behavior.
-   Guest cannot access protected endpoints.
-   User cannot read or update another project's task.
-   Duplicate team/project membership is rejected.
-   Assignee must belong to the project.
-   Invalid date ranges are rejected.
-   Invalid status transitions are rejected.
-   Task cannot be completed while blocking dependencies remain, when
    that rule is enabled.
-   Status change creates a history record.
-   User cannot read another user's notifications.
-   Attachment download checks authorization.
-   Filters and pagination return only accessible resources.

### Unit tests

-   Status transition policy.
-   Overdue task calculation.
-   Dependency cycle detection.
-   Progress calculation.
-   Recurrence date calculation.

### Testing practices

-   Use factories for reproducible data.
-   Use a dedicated test database.
-   Use `Notification::fake()` and `Event::fake()` when appropriate.
-   Test success and failure paths.
-   Assert both response status and persisted database changes.
-   Do not rely exclusively on manual Postman testing.

------------------------------------------------------------------------

## 15. Security, Performance, and Production Readiness

### Security checklist

-   [ ] Protect private routes with the correct authentication
    mechanism.
-   [ ] Authorize every resource operation.
-   [ ] Prevent IDOR and cross-project data leakage.
-   [ ] Validate all input and whitelist sort fields.
-   [ ] Use mass-assignment protection.
-   [ ] Hash passwords; never log credentials or tokens.
-   [ ] Validate file size and type; store private files outside public
    exposure.
-   [ ] Rate-limit login and expensive endpoints.
-   [ ] Use HTTPS in production.
-   [ ] Keep secrets in environment/configuration management.
-   [ ] Use safe production error responses.
-   [ ] Audit sensitive membership and permission changes.

### Performance checklist

-   [ ] Add indexes based on query patterns.
-   [ ] Use eager loading to avoid N+1 queries.
-   [ ] Paginate list endpoints.
-   [ ] Restrict returned fields and relationship depth.
-   [ ] Analyze slow queries before adding caches.
-   [ ] Queue slow email and file-processing operations.
-   [ ] Monitor queue backlog and failed jobs.

### Operations checklist

-   [ ] Configure application logs.
-   [ ] Configure queue worker supervision.
-   [ ] Configure scheduled tasks.
-   [ ] Back up MySQL and test restoration.
-   [ ] Define deployment and rollback procedures.
-   [ ] Run migrations carefully in production.
-   [ ] Document environment variables and setup requirements.

------------------------------------------------------------------------

## 16. Suggested Eight-Week Schedule

Estimate assumes approximately two hours of focused work per day. The
schedule may need to expand while learning unfamiliar Laravel concepts.

  -----------------------------------------------------------------------
  Week                    Focus                   Deliverable
  ----------------------- ----------------------- -----------------------
  1                       Setup and Sanctum       Working API
                                                  authentication

  2                       Migrations and          Tested schema and
                          relationships           Eloquent models

  3                       Teams and projects      CRUD plus membership
                                                  authorization

  4                       Tasks                   CRUD, assignment,
                                                  filtering, pagination

  5                       Workflow and audit      Policies, dependencies,
                                                  status history

  6                       Collaboration and       Comments, attachments,
                          dashboard               analytics

  7                       Background processing   Events, queues,
                                                  notifications,
                                                  recurrence

  8                       Quality and delivery    Tests, documentation,
                                                  deployment
  -----------------------------------------------------------------------

------------------------------------------------------------------------

## 17. Definition of Done

The project is ready for a portfolio demonstration when:

-   Authentication and authorization are enforced server-side.
-   Core team, project, and task workflows work through the API.
-   Database constraints protect important invariants.
-   Validation and consistent API responses are implemented.
-   Task history and audit records are created correctly.
-   Queued notifications and scheduled reminders work.
-   Automated tests cover critical happy paths and security failures.
-   API documentation explains endpoints, authentication, request
    payloads, and responses.
-   Production configuration does not expose secrets.
-   The application can be deployed and its critical flows verified.

------------------------------------------------------------------------

## 18. Recommended First Milestone

Start with only the following:

1.  Create the Laravel project and configure MySQL.
2.  Set up Sanctum.
3.  Implement registration, login, logout, and `/auth/me`.
4.  Write Feature Tests for authentication.
5.  Commit the working milestone to Git.

Then move on to schema design and the team/project membership model. Do
not implement all advanced features at once. Build one tested vertical
slice at a time, and understand each command, file, relationship, and
authorization rule before proceeding.
