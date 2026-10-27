# System Enums Reference

## UserRole
Values:
- `ADMIN`: Full administrative privileges across all teams and projects.
- `USER`: Regular system user.

## TeamRole
Values:
- `OWNER`: Team creator/owner with full team settings management.
- `MANAGER`: Can manage projects and members within the team.
- `MEMBER`: Regular team contributor.

## ProjectRole
Values:
- `OWNER`: Project administrator.
- `MANAGER`: Can assign tasks, edit project settings.
- `MEMBER`: Can create and be assigned to tasks.
- `VIEWER`: Read-only access to the project.

## ProjectStatus
Values:
- `ACTIVE`: Project is ongoing.
- `ON_HOLD`: Work is temporarily paused.
- `COMPLETED`: Project objectives fulfilled.
- `ARCHIVED`: Archived project.

## TaskStatus
Values:
- `TODO`: Backlog or pending start.
- `IN_PROGRESS`: Actively worked on.
- `IN_REVIEW`: Ready for code/work review.
- `DONE`: Task completed.
- `CANCELLED`: Abandoned task.

## TaskPriority
Values:
- `LOW`: Low urgency.
- `MEDIUM`: Standard operational priority.
- `HIGH`: Priority deliverable.
- `URGENT`: Blocker / immediate action required.

## RecurrenceFrequency
Values:
- `DAILY`: Recurs once per day or every N days.
- `WEEKLY`: Recurs every week on scheduled intervals.
- `MONTHLY`: Recurs every month.
