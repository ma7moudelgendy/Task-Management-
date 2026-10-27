# Feature: 03 Database Schema and Models

## Summary
Relational schema with foreign keys, compound unique constraints, indexes, soft-deletes, and typed Eloquent relationships across all core TaskFlow domains.

## Tables Created
1. `teams`
2. `team_members` (unique team_id, user_id)
3. `projects` (index team_id, status)
4. `project_members` (unique project_id, user_id)
5. `tasks` (self-referencing parent_task_id, indexes project_id, status, due_at)
6. `task_assignees` (unique task_id, user_id)
7. `task_dependencies` (unique task_id, depends_on_task_id)
8. `task_comments`
9. `task_attachments`
10. `task_status_histories`
11. `activity_logs`
12. `recurring_task_rules`

## Tests
- `tests/Unit/DatabaseSchemaTest.php` (5 tests, 14 assertions passing)
