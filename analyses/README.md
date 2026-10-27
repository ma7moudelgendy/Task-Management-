# TaskFlow Analysis & Modular Task Breakdown

This folder (`analyses/`) contains the complete architectural analysis, domain decomposition, and granular, step-by-step implementation tasks derived from `laravel/project.md`.

---

## 1. Executive Summary & Architectural Overview

**TaskFlow** is an enterprise-grade RESTful Task and Project Management API built with **Laravel 12, MySQL, and Laravel Sanctum**. It models complex organizational workflows including multi-tier role authorization (system-level vs. project-level), team isolation, hierarchical tasks with subtasks, directed acyclic graph (DAG) task dependencies with cycle detection, secure file attachments, automated recurrence, and asynchronous notification queues.

### Core Architectural Layers

```
                     HTTP Client (Postman / SPA / Mobile)
                                       │
                                       ▼
                       API Routing (`/api/v1/*`)
                                       │
                                       ▼
             Middleware (Sanctum Auth, Rate Limiting, Active Check)
                                       │
                                       ▼
             Form Requests (Validation & Custom Error Messages)
                                       │
                                       ▼
                      Policies (Granular Authorization)
                                       │
                                       ▼
                       Thin API Controllers (Api/V1)
                                       │
                                       ▼
                   Domain Service Layer (DB Transactions)
                                       │
                        ┌──────────────┴──────────────┐
                        ▼                             ▼
                 Eloquent Models               Events & Listeners
                        │                             │
                        ▼                             ▼
                   MySQL Database                Queued Jobs
                                                      │
                                                      ▼
                                           Notifications & Mailers
```

---

## 2. Domain Entities & Database Relationship Matrix

| Entity | Primary Table | Key Relationships & Foreign Keys | Critical Constraints & Invariants |
| :--- | :--- | :--- | :--- |
| **User** | `users` | Has many Teams, Projects, Tasks, Comments, Notifications | Unique email; `is_active` flag; soft delete / token revocation. |
| **Team** | `teams` | `created_by -> users.id`; Has many `team_members`, `projects` | Unique `slug`; must maintain at least one `OWNER`. |
| **Team Member** | `team_members` | `team_id`, `user_id`, `role` (`OWNER`, `MANAGER`, `MEMBER`) | Composite Unique (`team_id`, `user_id`); Index on `user_id`. |
| **Project** | `projects` | `team_id -> teams.id`, `created_by -> users.id` | Belongs to a team; status enum; `due_date >= start_date`. |
| **Project Member**| `project_members` | `project_id`, `user_id`, `role` (`OWNER`, `MANAGER`, `MEMBER`, `VIEWER`) | Composite Unique (`project_id`, `user_id`); Last owner protection rule. |
| **Task** | `tasks` | `project_id -> projects.id`, `parent_task_id -> tasks.id`, `created_by -> users.id` | Subtasks must belong to same project; no self-parenting; `due_at >= start_at`. |
| **Task Assignee** | `task_assignees` | `task_id -> tasks.id`, `user_id -> users.id`, `assigned_by -> users.id` | Composite Unique (`task_id`, `user_id`); Assignees MUST be members of project. |
| **Dependency** | `task_dependencies` | `task_id -> tasks.id`, `depends_on_task_id -> tasks.id` | Same project; no self-dependency; cycle detection (DAG); blocks completion. |
| **Comment** | `task_comments` | `task_id -> tasks.id`, `user_id -> users.id` | Only authorized project members; author-only edit/delete. |
| **Attachment** | `task_attachments`| `task_id -> tasks.id`, `uploaded_by -> users.id` | Private storage; mime/size validation; authorized streaming downloads. |
| **Status History**| `task_status_histories` | `task_id -> tasks.id`, `changed_by -> users.id` | Immutable audit trail on status transitions. |
| **Activity Log** | `activity_logs` | `actor_id`, `team_id`, `project_id`, `subject_type`, `subject_id` | Audit tracking for membership, assignments, and critical updates. |
| **Recurrence** | `recurring_task_rules` | `task_id -> tasks.id` | Daily, weekly, monthly intervals; idempotent generation via scheduler. |

---

## 3. Implementation Roadmap Index

The project is broken down into **11 sequential, modular task files**. Each phase carries its own set of bite-sized tasks, acceptance criteria, and test definitions.

| File | Phase Name | Focus & Key Deliverables |
| :--- | :--- | :--- |
| **[`01-project-setup-and-sanctum.md`](./01-project-setup-and-sanctum.md)** | Phase 1: Setup & Environment | Laravel 12 API scaffolding, `.env`, MySQL connectivity, Sanctum installation. |
| **[`02-authentication-and-profile.md`](./02-authentication-and-profile.md)** | Phase 2: Auth & Profile | Register, login, logout, profile update, password reset, rate-limiting. |
| **[`03-database-schema-and-models.md`](./03-database-schema-and-models.md)** | Phase 3: Schema & Models | Complete migrations in foreign-key order, Eloquent relationships, factories, seeders. |
| **[`04-teams-and-memberships.md`](./04-teams-and-memberships.md)** | Phase 4: Teams Module | Team CRUD, slug generation, team member roles, policies, owner demotion rules. |
| **[`05-projects-and-memberships.md`](./05-projects-and-memberships.md)** | Phase 5: Projects Module | Project CRUD, status lifecycle, project member roles, ownership transfer, isolation. |
| **[`06-tasks-core-crud-and-assignees.md`](./06-tasks-core-crud-and-assignees.md)** | Phase 6: Tasks Core | Task CRUD, subtask hierarchy, project-scoped assignees, filtering, pagination. |
| **[`07-task-workflow-dependencies-and-audit.md`](./07-task-workflow-dependencies-and-audit.md)** | Phase 7: Workflow & Dependencies | Status transitions, graph cycle detection (DAG), completion gates, status histories. |
| **[`08-comments-and-attachments.md`](./08-comments-and-attachments.md)** | Phase 8: Collaboration | Task discussions, policy moderation, private disk uploads, streaming downloads. |
| **[`09-notifications-queues-and-recurrence.md`](./09-notifications-queues-and-recurrence.md)** | Phase 9: Background Processing | Domain events, queued notifications, due-soon reminders, recurrence scheduler. |
| **[`10-dashboard-and-analytics.md`](./10-dashboard-and-analytics.md)** | Phase 10: Dashboard & Analytics | Aggregated metrics, project progress calculation, overdue/upcoming queries. |
| **[`11-testing-security-and-deployment.md`](./11-testing-security-and-deployment.md)** | Phase 11: Testing & Production | Comprehensive feature test suite, security hardening, IDOR review, OpenAPI spec. |

---

## 4. Execution Guidelines

1. **Follow Dependency Order:** Build phase by phase. Never build task status transitions (Phase 7) before Task Core (Phase 6) and Schema (Phase 3).
2. **Test-Driven Delivery:** Every phase defines required Feature and Unit tests. Run `php artisan test` after every task.
3. **Keep Controllers Thin:** Always delegate multi-write logic, audit logging, and cycle detection to Dedicated Domain Services (`TaskService`, `ProjectService`, `DependencyService`).
4. **Enforce Authorization via Policies:** Never verify roles manually inside controller methods. Always use `$this->authorize()` or Form Request `authorize()`.

