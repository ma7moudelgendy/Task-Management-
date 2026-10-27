# Phase 3: Database Schema, Migrations, Models & Factories

> **Spec Reference:** Section 5 (Database Design 5.1 - 5.14), Section 6 (Entity Relationships), Section 13 (Phase 3)  
> **Primary Goal:** Construct the relational database schema in exact dependency order with foreign keys, compound indexes, and unique constraints; implement corresponding Eloquent models, relationships, and factories.

---

## High-Level Objective
Establish the relational core of TaskFlow. Because subsequent phases rely on foreign keys and model relationships, every migration must execute cleanly without dependency cycles, and all Eloquent models must expose bidirectional relationships.

---

## Detailed Task Breakdown

### Task 3.1: Migrations Generation (Strict Dependency Order)
Create migrations ensuring foreign key parent tables exist before dependent child tables:

- [x] **Step 1:** `teams` table migration:
  - `id` (bigIncrements)
  - `name` (string)
  - `slug` (string, unique)
  - `created_by` (foreignId -> `users.id` cascadeOnDelete)
  - `timestamps()`, `softDeletes()`
- [x] **Step 2:** `team_members` pivot migration:
  - `id` (bigIncrements)
  - `team_id` (foreignId -> `teams.id` cascadeOnDelete)
  - `user_id` (foreignId -> `users.id` cascadeOnDelete)
  - `role` (enum: `['OWNER', 'MANAGER', 'MEMBER']`, default `'MEMBER'`)
  - `joined_at` (timestamp, default `now()`)
  - `timestamps()`
  - Unique composite index: `unique(['team_id', 'user_id'])`
  - Index: `index('user_id')`
- [x] **Step 3:** `projects` table migration:
  - `id` (bigIncrements)
  - `team_id` (foreignId -> `teams.id` cascadeOnDelete)
  - `created_by` (foreignId -> `users.id`)
  - `name` (string)
  - `description` (text, nullable)
  - `status` (enum: `['ACTIVE', 'ON_HOLD', 'COMPLETED', 'ARCHIVED']`, default `'ACTIVE'`)
  - `start_date` (date, nullable)
  - `due_date` (date, nullable)
  - `timestamps()`, `softDeletes()`
  - Index: `index(['team_id', 'status'])`
- [x] **Step 4:** `project_members` pivot migration:
  - `id` (bigIncrements)
  - `project_id` (foreignId -> `projects.id` cascadeOnDelete)
  - `user_id` (foreignId -> `users.id` cascadeOnDelete)
  - `role` (enum: `['OWNER', 'MANAGER', 'MEMBER', 'VIEWER']`, default `'MEMBER'`)
  - `joined_at` (timestamp, default `now()`)
  - `timestamps()`
  - Unique composite index: `unique(['project_id', 'user_id'])`
  - Index: `index(['user_id', 'project_id'])`
- [x] **Step 5:** `tasks` table migration:
  - `id` (bigIncrements)
  - `project_id` (foreignId -> `projects.id` cascadeOnDelete)
  - `parent_task_id` (foreignId -> `tasks.id`, nullable, cascadeOnDelete)
  - `created_by` (foreignId -> `users.id`)
  - `title` (string)
  - `description` (longText, nullable)
  - `status` (enum: `['TODO', 'IN_PROGRESS', 'IN_REVIEW', 'DONE', 'CANCELLED']`, default `'TODO'`)
  - `priority` (enum: `['LOW', 'MEDIUM', 'HIGH', 'URGENT']`, default `'MEDIUM'`)
  - `start_at` (dateTime, nullable)
  - `due_at` (dateTime, nullable)
  - `completed_at` (dateTime, nullable)
  - `position` (integer, default 0)
  - `timestamps()`, `softDeletes()`
  - Indexes: `index(['project_id', 'status'])`, `index('due_at')`, `index('parent_task_id')`
- [x] **Step 6:** `task_assignees` pivot migration:
  - `id` (bigIncrements)
  - `task_id` (foreignId -> `tasks.id` cascadeOnDelete)
  - `user_id` (foreignId -> `users.id` cascadeOnDelete)
  - `assigned_by` (foreignId -> `users.id`)
  - `assigned_at` (timestamp, default `now()`)
  - Unique composite index: `unique(['task_id', 'user_id'])`
- [x] **Step 7:** `task_dependencies` migration:
  - `id` (bigIncrements)
  - `task_id` (foreignId -> `tasks.id` cascadeOnDelete, the blocked task)
  - `depends_on_task_id` (foreignId -> `tasks.id` cascadeOnDelete, the prerequisite task)
  - `timestamps()`
  - Unique composite index: `unique(['task_id', 'depends_on_task_id'])`
- [x] **Step 8:** `task_comments` migration:
  - `id` (bigIncrements)
  - `task_id` (foreignId -> `tasks.id` cascadeOnDelete)
  - `user_id` (foreignId -> `users.id` cascadeOnDelete)
  - `body` (text)
  - `timestamps()`, `softDeletes()`
- [x] **Step 9:** `task_attachments` migration:
  - `id` (bigIncrements)
  - `task_id` (foreignId -> `tasks.id` cascadeOnDelete)
  - `uploaded_by` (foreignId -> `users.id`)
  - `disk` (string, default `'local'`)
  - `path` (string)
  - `original_name` (string)
  - `mime_type` (string)
  - `size` (unsignedBigInteger)
  - `timestamps()`, `softDeletes()`
- [x] **Step 10:** `task_status_histories` migration:
  - `id` (bigIncrements)
  - `task_id` (foreignId -> `tasks.id` cascadeOnDelete)
  - `changed_by` (foreignId -> `users.id`)
  - `old_status` (string)
  - `new_status` (string)
  - `reason` (string, nullable)
  - `created_at` (timestamp)
- [x] **Step 11:** `activity_logs` migration:
  - `id` (bigIncrements)
  - `actor_id` (foreignId -> `users.id`, nullable)
  - `team_id` (foreignId -> `teams.id`, nullable, cascadeOnDelete)
  - `project_id` (foreignId -> `projects.id`, nullable, cascadeOnDelete)
  - `subject_type` (string, nullable)
  - `subject_id` (unsignedBigInteger, nullable)
  - `action` (string)
  - `metadata` (json, nullable)
  - `created_at` (timestamp)
  - Index: `index(['subject_type', 'subject_id'])`, `index('created_at')`
- [x] **Step 12:** `recurring_task_rules` migration:
  - `id` (bigIncrements)
  - `task_id` (foreignId -> `tasks.id` cascadeOnDelete)
  - `frequency` (enum: `['DAILY', 'WEEKLY', 'MONTHLY']`)
  - `interval` (unsignedInteger, default 1)
  - `starts_at` (dateTime)
  - `ends_at` (dateTime, nullable)
  - `next_run_at` (dateTime)
  - `timezone` (string, default `'UTC'`)
  - `is_active` (boolean, default true)
  - `timestamps()`

### Task 3.2: Eloquent Models & Relationship Declarations
Create/update models in `app/Models/`:
- [x] **Step 1: `Team` Model:**
  - `creator()` -> `belongsTo(User::class, 'created_by')`
  - `members()` -> `belongsToMany(User::class, 'team_members')->withPivot('role', 'joined_at')->withTimestamps()`
  - `projects()` -> `hasMany(Project::class)`
- [x] **Step 2: `Project` Model:**
  - `team()` -> `belongsTo(Team::class)`
  - `creator()` -> `belongsTo(User::class, 'created_by')`
  - `members()` -> `belongsToMany(User::class, 'project_members')->withPivot('role', 'joined_at')->withTimestamps()`
  - `tasks()` -> `hasMany(Task::class)`
- [x] **Step 3: `Task` Model:**
  - `project()` -> `belongsTo(Project::class)`
  - `creator()` -> `belongsTo(User::class, 'created_by')`
  - `parentTask()` -> `belongsTo(Task::class, 'parent_task_id')`
  - `subtasks()` -> `hasMany(Task::class, 'parent_task_id')`
  - `assignees()` -> `belongsToMany(User::class, 'task_assignees')->withPivot('assigned_by', 'assigned_at')`
  - `dependencies()` -> `belongsToMany(Task::class, 'task_dependencies', 'task_id', 'depends_on_task_id')` (prerequisites)
  - `dependents()` -> `belongsToMany(Task::class, 'task_dependencies', 'depends_on_task_id', 'task_id')` (blocked tasks)
  - `comments()` -> `hasMany(TaskComment::class)`
  - `attachments()` -> `hasMany(TaskAttachment::class)`
  - `statusHistories()` -> `hasMany(TaskStatusHistory::class)`
  - `recurrenceRule()` -> `hasOne(RecurringTaskRule::class)`
- [x] **Step 4: Additional Models:**
  - Create `TaskComment`, `TaskAttachment`, `TaskStatusHistory`, `ActivityLog`, `RecurringTaskRule`.

### Task 3.3: Factories and Seeders
- [x] **Step 1:** Create Model Factories for: `UserFactory`, `TeamFactory`, `ProjectFactory`, `TaskFactory`, `TaskCommentFactory`.
- [x] **Step 2:** Create `DatabaseSeeder.php` orchestrating:
  - 1 System Admin user (`admin@taskflow.local`).
  - 2 Organization Teams with mixed Owners, Managers, and Members.
  - 4 Projects across teams with active/completed statuses.
  - 20 Tasks with parent-child relationships, assignees, and dependencies.
  - Sample comments and status histories.

### Task 3.4: Migration Verification Test
- [x] **Step 1:** Run `php artisan migrate:fresh --seed`.
- [x] **Step 2:** Write `tests/Unit/DatabaseSchemaTest.php` asserting that:
  - Duplicate `(team_id, user_id)` insertion throws `QueryException`.
  - Duplicate `(project_id, user_id)` insertion throws `QueryException`.
  - Duplicate `(task_id, user_id)` assignee throws `QueryException`.
  - Foreign key cascade properly cleans up subtasks/comments when a project/task is deleted.
- [x] **Step 3:** Run `php artisan test --filter=DatabaseSchemaTest`.

---

## Acceptance Criteria
- [x] All 12 migrations run cleanly on a fresh database without foreign key errors.
- [x] Composite unique indexes prevent duplicate memberships, assignments, and dependencies.
- [x] All Eloquent relationships (`belongsTo`, `hasMany`, `belongsToMany`) return typed collections.
- [x] Database seeder creates a populated development environment in seconds.
