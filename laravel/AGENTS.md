# TaskFlow — Universal Agent Instructions & Rules

> **Applies to:** All AI coding assistants, agents, and subagents operating in this repository.  
> **Source of Truth:** Codebase > `project.md` > `agent-rules.md` > Modular Rules in `.agents/rules/`.

---

## 1. Project Overview & Context

- **Project:** TaskFlow — Advanced Task Management Backend API.
- **Technology Stack:** Laravel 11/12, PHP 8.2+, MySQL, Laravel Sanctum.
- **API Style:** REST API, versioned under `/api/v1/...` (or next `/api/v2/...` for breaking changes).
- **Core Domain:** Users, Teams (`team_members`), Projects (`project_members`), Tasks (subtasks, dependencies, assignees, priorities, statuses), Comments, Attachments, Activity Logs, Notifications, Reminders & Recurring Tasks, Dashboards & Analytics.

---

## 2. Rule Hierarchy & Modular Rules

Modular rules are located under `.agents/rules/` (and `.cursor/rules/`). All agents must strictly follow them:

1. **[`laravel-agent.md`](.agents/rules/laravel-agent.md)** — Core execution engine:
   - Mode detection: `IMPLEMENT` vs `REVIEW` based on trigger words / existing code.
   - Architecture Mode (`docs/architecture-mode.md`): `MODE_A` (Repository pattern) vs `MODE_B` (Service directly calls Model).
   - Strict 19-step execution order.
   - Mandatory Apidog synchronization after every API endpoint.
   - Quality gate checklist & Git branch/MR workflows.
2. **[`api-building.md`](.agents/rules/api-building.md)** — Versioned API endpoints:
   - Routes mounted under `routes/guards/{version}/{actor}.php`. Never replace/delete `v1` for breaking changes; introduce `v2`.
   - Controllers **always** use `HandlesApiExceptions` + `handleApi()`. No manual `try/catch`.
   - **HTTP status is always `200`**. Business outcome is strictly in body `key` (`success`, `fail`, `unauthenticated`, etc.) and `response_status`.
   - FormRequests for validation only (no DB queries). DTOs are `readonly` with `fromRequest()`.
   - `form-keys` per screen only (first 10 items + pagination URL).
   - Resource `helpers` hold action permissions (`can_*`) and validation metadata (`*_validation`).
3. **[`api-testing.md`](.agents/rules/api-testing.md)** — Feature testing standard:
   - Tests under `tests/Feature/Api/{Actor}/{Feature}Test.php`.
   - Cover full journeys (e.g. auth → create → show → update → DB assert) and distinct failure paths.
   - No mocking of domain models (`User`, `Task`, etc.); mock only external boundary services (SMS, Payment, FCM, clock).
   - Assert `key` and full payload shape using `assertJsonStructure` (required keys) and `assertJsonMissingPath` (forbidden/removed/secret keys).
   - Run verification via `./ci.sh --tests-only --test=...`.
4. **[`clean-code-guard.md`](.agents/rules/clean-code-guard.md)** — Code quality and AI guardrails:
   - Single responsibility, clear naming (no generic `data`, `temp`, `helper`, `process_*`).
   - Short functions (≤20 lines), ≤4 params, Command-Query Separation.
   - Catch specific exceptions only; no broad catch-alls; no dead code or speculative abstractions.
   - No hallucinated APIs; verify every import and method against installed packages.
5. **[`agent-memory-store.md`](.agents/rules/agent-memory-store.md)** — Project memory & caching:
   - Fast reference in `docs/memory/` (`api/`, `features/`, `domain/`, `decisions/`, `fixes/`).
   - Read `docs/memory/INDEX.md` before starting tasks.
   - Update memory immediately after creating or modifying endpoints, features, or architectural decisions.

Detailed project rules and domain policies are documented in [`agent-rules.md`](agent-rules.md) and [`project.md`](project.md).

---

## 3. Non-Negotiable Hard Constraints

- ✗ **Never return HTTP 4xx/5xx for business flow**: Transport is always HTTP 200; use body `key` + `response_status`.
- ✗ **No `response()->json()`**: Always use `jsonResponse()` provided by `HandlesApiExceptions`.
- ✗ **No manual `try/catch` in API Controllers**: Wrap actions inside `handleApi(fn () => ...)`.
- ✗ **No Eloquent queries in Controllers**: Delegate all data operations to Services.
- ✗ **No lazy loading in Resources**: Use `whenLoaded()` exclusively to prevent N+1 issues.
- ✗ **No business logic in FormRequests**: Validation only.
- ✗ **Do not create new models for core types**: Reuse `User` for all human actors, `Intro` for splash/onboarding, `Image` for sliders/banners.
- ✗ **Never store secrets or credentials**: Never log or output tokens, passwords, or `.env` content.
- ✗ **Do not skip tests or documentation**: Update `api-docs/frontend-docs/{actor}/{domain}.md`, `docs/memory/`, and sync Apidog after passing tests.

---

## 4. Verification & Quality Gate Before Completion

Before marking any task complete:
1. Ensure all new and affected routes, controllers, services, and resources follow the exact layer pattern.
2. Feature tests pass with real database assertions: `./ci.sh --tests-only --test=...`.
3. Client documentation (`api-docs/frontend-docs/`) and agent memory (`docs/memory/`) are up to date.
4. Provide concise summary: Implemented features, files changed, database/API changes, test commands & results, and any pending decisions.

