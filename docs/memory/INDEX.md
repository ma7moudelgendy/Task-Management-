# Project Memory Index

## Decisions
- [`decisions/architecture-mode.md`](decisions/architecture-mode.md) — MODE_A locked for all features.
- [`decisions/openapi-apidog-sync.md`](decisions/openapi-apidog-sync.md) — OpenAPI 3.1.0 generator and Apidog sync workflow.

## Features
- [`features/01-project-setup-and-sanctum.md`](features/01-project-setup-and-sanctum.md) — Phase 1: Project setup, database, Sanctum, base API layer.
- [`features/02-authentication-and-profile.md`](features/02-authentication-and-profile.md) — Phase 2: Auth and user profile endpoints.
- [`features/03-database-schema-and-models.md`](features/03-database-schema-and-models.md) — Phase 3: Relational schema, 12 migrations, models, seeders.
- [`features/04-teams-and-memberships.md`](features/04-teams-and-memberships.md) — Phase 4: Teams and team memberships module.
- [`features/05-projects-and-memberships.md`](features/05-projects-and-memberships.md) — Phase 5: Projects and project memberships module.
- [`features/06-tasks-core-crud-and-assignees.md`](features/06-tasks-core-crud-and-assignees.md) — Phase 6: Tasks core CRUD, subtasks & assignees module.
- [`features/07-task-workflow-dependencies-and-audit.md`](features/07-task-workflow-dependencies-and-audit.md) — Phase 7: Task workflow, status transitions, dependencies & audit trail.
- [`features/08-comments-and-attachments.md`](features/08-comments-and-attachments.md) — Phase 8: Comments & attachments collaboration module.
- [`features/09-notifications-queues-and-recurrence.md`](features/09-notifications-queues-and-recurrence.md) — Phase 9: Background processing, notifications & recurrence module.
- [`features/10-dashboard-and-analytics.md`](features/10-dashboard-and-analytics.md) — Phase 10: Dashboards & project analytics module.

## Domain
- [`domain/models-and-relations.md`](domain/models-and-relations.md) — Core models, relationships, and invariants.

## API Endpoints
- [`api/v1-health.md`](api/v1-health.md) — GET `/api/v1/health`
- [`api/v1-user-auth.md`](api/v1-user-auth.md) — `/api/v1/auth/*` (register, login, logout, me, updateProfile, forgotPassword, resetPassword)
- [`api/v1-teams.md`](api/v1-teams.md) — `/api/v1/teams/*` (CRUD, members, roles, permissions)
- [`api/v1-projects.md`](api/v1-projects.md) — `/api/v1/projects/*` (CRUD, members, roles, filters)
- [`api/v1-tasks.md`](api/v1-tasks.md) — `/api/v1/tasks/*` & `/api/v1/projects/{project}/tasks` (CRUD, subtasks, assignees, filters)
- [`api/v1-task-workflow.md`](api/v1-task-workflow.md) — `/api/v1/tasks/{task}/status`, `dependencies`, `history` (workflow, DAG, audit)
- [`api/v1-comments-and-attachments.md`](api/v1-comments-and-attachments.md) — `/api/v1/tasks/{task}/comments` & `attachments` (discussions, private file storage, streaming)
- [`api/v1-notifications.md`](api/v1-notifications.md) — `/api/v1/notifications` & `tasks/{task}/recurrence` (user notifications & recurrence engine)
- [`api/v1-dashboard-and-analytics.md`](api/v1-dashboard-and-analytics.md) — `/api/v1/dashboard` & `/api/v1/projects/{project}/analytics` (personal dashboard & project analytics)
