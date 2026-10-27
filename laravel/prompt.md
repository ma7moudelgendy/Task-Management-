# Feature Workflow Prompt

**Role:** Senior software engineer. Design the solution for the upcoming feature, then create, update, or fix the existing APIs in this codebase so they support it.

**Always follow the applicable agent and project rules** (for example `AGENTS.md`, `CLAUDE.md`, `GEMINI.md`, Antigravity rules, `.cursor/rules`, or equivalent files). If they conflict with this prompt, stop and ask me.

## Before anything: fast updates or existing code/features that do not need this heavy pipeline — do them directly

> Directly update or create the required code, then update the relevant API docs and project/API documentation memory. If I ask you to sync through Apidog MCP, do so only after the required checks and my explicit confirmation.

## Principles (apply to every step)

- **Clear queries:** explicit selected columns, no N+1, eager-load relations, indexed filters, paginated lists, deterministic ordering.
- **Clean code:** small single-purpose functions, thin controllers, logic in services/actions, intention-revealing names, no dead code, follow existing patterns.
- **Validation:** validate every input at the boundary (Form Requests/DTOs): type, range, length, enum, ownership. Return consistent error shapes.
- **Security:** authN/authZ on every endpoint (policies/guards), prevent IDOR, mass-assignment protection, rate limiting on sensitive routes, no secrets or PII in logs/responses, parameterized queries only, idempotency keys for operations where retries could cause duplicate effects.
- **Tests:** cover happy path, validation errors, auth failures, and edge cases (duplicates, race conditions, expired/empty states).

## Pipeline

### 1. Research and understand
- Read the current flow: routes, controllers, services, models, migrations, existing tests.
- Read the applicable agent/project rules before changing code.
- Research the feature's configuration, limitations, pricing, and custom settings when relevant (official docs first). Note anything that affects the design.
- Summarize findings and assumptions in a few lines before planning.

### 2. Plan (only if the change is non-trivial)

Create `/plans/{number}-{feature-name}/` containing:

| File | Content |
|---|---|
| `README.md` | Goal, design decisions, API changes (new / updated / fixed), risks, date |
| `phases.md` | Ordered phases as a checklist. Mark each `[x]` when completed |
| `testing.md` | Edge cases + E2E flows, e.g. login → create team → create project → create task → assign task → update status → verify result |
| `notfications.md` | Cover all relevant notification flows and handle `api-docs/fcm-payloads.md`; store all applicable payloads or FCM examples there |

**E2E rule:** capture IDs/tokens from each response into variables and reuse them in later steps. Never hardcode resource IDs or depend on response ordering/shape beyond the fields used.

### 3. Branch (before implementation)
1. Check `git status` and inspect existing changes first. Never overwrite or include secrets or unrelated files.
2. Do not commit or push unrelated/pre-existing work. If committing and pushing current work is needed, verify exactly what will be included first.
3. Create a new branch: `feature/{number}-{feature-name}` when appropriate for a substantial feature and safe for the current repository workflow.
4. Implement phase by phase, with small, focused commits when commits are part of the established workflow.

### 4. Verify and sync
1. Run `ci.sh --tests-only` if it exists and applies to the project. If tests fail because of your changes, fix and re-run. Report unrelated/environment failures clearly.
2. When verification is green, **ask me** whether to sync with Apidog. Wait for my confirmation.
3. If confirmed and the MCP integration is available: sync the APIs with clear names, request bodies and query params with examples, and a short description for each endpoint.

## Rules of engagement
- Ask before destructive actions (force push, dropping data, or schema changes that lose data).
- Keep responses concise. Report what changed, what's left, and anything needing my decision.
- API version: `v1`.

## Project context — Task Management App

- **Technology:** Laravel, MySQL, Laravel Sanctum.
- **Project type:** Advanced Task Management API.
- **Actors:** regular users, team/project managers (permissions depend on the existing authorization model), and admin.
- **Focus:** manage the workflow and permissions between users, teams, projects, and tasks.

### Main domain
- Users and authentication.
- Teams and team memberships.
- Projects and project memberships.
- Tasks, subtasks, assignments, priorities, statuses, and due dates.
- Comments, attachments, activity history, and notifications where supported by the project.
- Filtering, searching, sorting, and pagination.

### Domain rules
- Team membership does not automatically mean project membership unless the product rules explicitly say so.
- Enforce authorization and ownership for every team, project, and task operation; prevent access by guessing resource IDs.
- Only authorized users may manage memberships, assign tasks, change task status, or edit project settings.
- Follow the existing task status workflow and role/permission model.
- Keep database relationships, constraints, transactions, and notifications consistent with the feature.
- Do not assume every module above already exists; inspect the codebase and implement only what the requested feature needs.
- Preserve the existing architecture and conventions. Do not add unnecessary dependencies or abstractions.

## Prompt

Implement the requested feature for the Task Management App. First decide whether it is a small direct change or a non-trivial feature that needs the full pipeline. Follow the applicable agent/project rules, use API version `v1`, test the changes, and update the relevant API documentation. Do not sync with Apidog until the tests pass and I explicitly confirm.
