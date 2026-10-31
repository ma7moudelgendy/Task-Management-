# Domain: Models and Relationships

## Models
- `User`: owns Teams, member of Teams/Projects, assigned Tasks, author of Comments.
- `Team`: belongs to creator (`User`), has members (`team_members`), has `projects`.
- `Project`: belongs to `Team` and creator (`User`), has members (`project_members`), has `tasks`.
- `Task`: belongs to `Project`, creator (`User`), parent `Task`; has `subtasks`, `assignees`, `dependencies`, `dependents`, `comments`, `attachments`, `statusHistories`, `recurrenceRule`.
- `TaskComment`: belongs to `Task` and `User`.
- `TaskAttachment`: belongs to `Task` and `User` (uploader).
- `ActivityLog`: polymorphic subject, tracks team/project/actor audits.

