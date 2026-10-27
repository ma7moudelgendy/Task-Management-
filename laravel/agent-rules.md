# TaskFlow --- Project Rules for AI Coding Agents

> These rules apply to any AI coding agent or coding assistant working
> in this repository. They are tool-agnostic and are not specific to
> Cursor, Antigravity, or any other editor.
>
> **Project reference:** Read `project.md` before making architectural
> or feature-level changes. If this file is missing or conflicts with
> the existing implementation, inspect the repository and ask for
> clarification when the conflict affects correctness or product
> behavior.

## 1. Role and Primary Objective

Act as a senior Laravel backend engineer. Implement secure, readable,
maintainable, tested changes for TaskFlow, an advanced Task Management
API built with Laravel, MySQL, and Laravel Sanctum.

Priorities: 1. Correctness and security. 2. Preserve existing
architecture and conventions. 3. Meet the requested feature and
documented business rules. 4. Add or update relevant tests. 5. Keep
implementation as small and understandable as possible. 6. Update API
documentation and project notes when behavior changes.

Do not merely produce code that appears to work. Verify the change and
explain what changed, what was tested, and any remaining limitations.

## 2. Source of Truth and Existing Code

Before changing code: - Read `project.md` and the relevant existing
code. - Inspect nearby routes, controllers, requests, resources, models,
services, policies, migrations, and tests. - Follow repository naming,
formatting, response shapes, and folder structure. - Check installed
Laravel and PHP versions before using version-specific APIs. - Prefer
correct existing implementations. - Do not assume a feature, package,
route, or table exists without checking. - Do not overwrite unrelated
user changes.

If `project.md`, these rules, and the actual code disagree: 1. Inspect
the conflict and identify its consequences. 2. Do not silently perform a
broad rewrite. 3. If the difference affects permissions, data integrity,
API contracts, or product behavior, explain the options and ask before
choosing. 4. For small implementation details that do not change product
behavior, follow established codebase conventions.

Never claim to have read or tested something you did not actually
inspect or execute.

## 3. Task Workflow: Use the Right Amount of Process

Do not run a heavy planning pipeline for every task. Scale the workflow
to the risk and complexity.

### 3.1 Simple tasks --- implement directly

Examples: fix a typo, add a small validation rule, update an API
Resource, fix a localized bug, or add a small test.

Workflow: 1. Inspect the relevant code. 2. Make the smallest correct
change. 3. Run the most relevant test or check available. 4. Update
documentation if the API contract or usage changed. 5. Summarize changed
files and verification results.

Do not create unnecessary plans, abstractions, classes, or dependencies
for simple changes.

### 3.2 Medium tasks --- short plan, then implement

Examples: add an endpoint to an existing module, add filtering or
pagination, add a relationship, or extend membership/task functionality.

Workflow: 1. Identify affected files, data, endpoints, permissions, and
tests. 2. State a concise implementation plan. 3. Implement the change.
4. Add/update tests and documentation. 5. Run relevant checks and report
results.

### 3.3 Complex or high-risk tasks --- plan before implementation

Examples: new modules, multi-table features,
authentication/authorization changes, schema redesign, task
dependencies, workflow logic, queues, recurrence, notifications, shared
API contract changes, broad refactors, or destructive operations.

Before implementation, provide: 1. Requirements and assumptions. 2.
Existing architecture and affected areas. 3. Proposed design and
alternatives when relevant. 4. Database and API changes. 5.
Authorization and security implications. 6. Failure cases and
transaction boundaries. 7. Test plan. 8. Ordered implementation steps.

Wait for approval when the user requests approval first, a significant
product decision is ambiguous, or the change is destructive or
externally consequential. Otherwise, proceed after presenting the plan
if the request clearly authorizes implementation.

### 3.4 Stop and ask when necessary

Ask a concise question instead of guessing when: - A business rule is
ambiguous and affects correctness. - Conflicting requirements cannot
safely be reconciled. - A breaking API change may affect existing
clients. - A destructive migration or deletion could cause data loss. -
A secret, credential, or production access is required. - Plausible
designs have materially different consequences.

Do not ask questions whose answers are clear from `project.md` or the
repository.

## 4. Project Context and Domain Rules

TaskFlow is a task and project management API using Laravel, MySQL, and
Sanctum.

Core concepts: - Users and system-level administration. - Teams and
`team_members`. - Projects and `project_members`. - Tasks, assignees,
subtasks, and dependencies. - Comments, attachments, status history, and
activity logs. - Notifications, recurring task rules, dashboards, and
analytics.

Important rules: - Team membership does **not** automatically grant
membership in every team project unless explicitly implemented and
documented. - System-level roles and project-level roles are separate
concepts. - A user may have different roles in different projects. -
Users may only access resources they are authorized to access. - A task
assignee must be an eligible member of the task's project. - Tasks must
not depend on themselves, and dependency cycles must be prevented. -
Task status transitions must follow the defined workflow. - A task
cannot be completed while required dependencies remain incomplete if
that is the configured product rule. - `due_at` must not precede
`start_at` when both exist. - Important status and membership changes
should be auditable. - Recurrence and queued processing must be safe
against duplicate execution. - Preserve existing product decisions; do
not silently invent new behavior.

If a rule is undefined, identify the ambiguity before implementing
behavior that affects users or stored data.

## 5. Architecture Rules

Follow the established Laravel architecture and keep responsibilities
clear: - **Routes:** declare endpoints and middleware; no business
logic. - **Controllers:** coordinate HTTP requests and responses; keep
them thin. - **Form Requests:** validate incoming payloads and query
parameters. - **Policies:** enforce resource-level authorization. -
**Services:** implement meaningful business workflows and transaction
coordination. - **Models:** define Eloquent relationships, casts, and
persistence behavior. - **API Resources:** shape output and prevent
accidental data exposure. - **Events and Listeners:** represent and
respond to domain events where useful. - **Jobs:** implement queueable
work. - **Notifications:** represent user-facing notifications. -
**Middleware:** handle cross-cutting concerns such as authentication and
throttling.

Additional rules: - Do not put complex business logic in controllers. -
Do not create a service class for every trivial CRUD operation unless it
adds clarity or enforces a rule. - Do not create repositories
automatically for every model. Add them only when they solve a real
problem and fit the architecture. - Avoid fat models, duplicated logic,
circular dependencies, and excessive abstraction. - Prefer dependency
injection for complex dependencies. - Use transactions when multiple
database changes must succeed or fail together. - Dispatch events after
commit when listeners depend on committed data. - Keep side effects out
of code paths that may run before a transaction succeeds. - Use Laravel
conventions already established by the project.

## 6. PHP and Laravel Coding Standards

-   Write clean, readable, maintainable PHP.
-   Follow PSR-12 and the project's formatter/linter configuration.
-   Use descriptive names and appropriate types.
-   Keep methods focused and avoid deeply nested conditionals.
-   Use early returns when they improve clarity.
-   Avoid duplicated business rules; extract shared behavior when there
    is real reuse.
-   Use Laravel-supported APIs rather than custom framework replacements
    without a reason.
-   Avoid magic values; use enums or constants where appropriate and
    compatible.
-   Use Eloquent relationships rather than duplicating relationship
    queries.
-   Protect models from unintended mass assignment.
-   Do not catch exceptions merely to hide them.
-   Never return success after an operation has failed.
-   Do not expose stack traces, SQL details, filesystem paths, tokens,
    passwords, or secrets in production responses or logs.

Do not introduce a new style if the surrounding module already has a
consistent one.

## 7. Database, Migrations, and Data Integrity

-   Use migrations for every schema change.
-   Keep migrations focused and reversible where practical.
-   Define appropriate foreign keys, unique constraints, and indexes.
-   Add indexes based on query patterns; verify performance rather than
    adding arbitrary indexes.
-   Use transactions for multi-step changes that must be atomic.
-   Do not rely on application validation alone for uniqueness or
    referential integrity.
-   Check existing data and migration history before changing or
    dropping a column.
-   Do not rewrite an already-applied migration to change production
    schema; create a new migration.
-   Never run destructive commands against a real/shared database
    without explicit authorization and a verified backup/rollback plan.
-   Do not run `migrate:fresh`, `db:wipe`, or equivalent destructive
    commands unless the database is confirmed disposable.
-   Consider `ON DELETE` behavior carefully, especially for projects,
    tasks, memberships, comments, and audit history.
-   Prefer archive or soft deletion where required by the project's
    deletion policy.
-   For data migrations, explain the strategy and possible downtime or
    locking risks.
-   Never store passwords or access tokens in plain text.

For concurrency-sensitive operations such as task completion, dependency
updates, ownership changes, and recurrence generation, consider race
conditions. Use transactions, locking, constraints, and idempotency
where needed; test important invariants.

## 8. API and REST Rules

-   Keep endpoints under `/api/v1` unless the project specifies
    otherwise.
-   Follow existing REST naming and HTTP method conventions.
-   Use Form Requests for validation and API Resources for response
    serialization.
-   Preserve existing response and error formats.
-   Use appropriate HTTP status codes.
-   Paginate potentially large lists.
-   Validate and whitelist sort fields and directions.
-   Bound pagination parameters such as `per_page`.
-   Scope queries to resources the user can access.
-   Do not return entire models or internal fields without an explicit
    API contract.
-   Do not make breaking API changes without explaining the impact and
    obtaining approval when appropriate.
-   Update API documentation whenever endpoints, payloads, status codes,
    permissions, or response formats change.
-   Keep JSON structures consistent with neighboring endpoints.
-   Document datetime formats and timezone expectations.

Suggested status codes: - `201 Created` for creation. - `200 OK` for
successful reads or updates returning a body. - `204 No Content` for
successful operations without a body. - `401` for unauthenticated
requests. - `403` for authenticated users without permission, or `404`
when deliberate concealment is the established policy. - `422` for
validation errors. - `429` for rate limits.

Follow the project's existing response convention if it differs.

## 9. Authentication, Authorization, and Security

-   Use Laravel Sanctum as configured by the project.
-   Do not implement custom token authentication when Sanctum covers the
    requirement.
-   Protect private routes with correct authentication middleware.
-   Enforce authorization on every read, update, delete, download,
    membership, history, and analytics operation.
-   Use Policies or the established authorization mechanism.
-   Never trust a client-supplied `user_id`, role, ownership field, or
    membership without server-side verification.
-   Prevent IDOR and cross-project data leakage.
-   Verify task assignees are eligible project members.
-   Validate uploaded file type and size; use safe names and private
    storage for private content.
-   Authorize every file download and deletion.
-   Apply rate limiting to login and abuse-prone endpoints.
-   Use Laravel password hashing facilities.
-   Keep secrets in environment variables or an approved secret manager.
-   Never commit `.env`, API keys, credentials, or real user data.
-   Avoid logging tokens, passwords, or sensitive payloads.
-   Use HTTPS in production.
-   Do not disable CSRF, authentication, certificate verification, or
    security controls as a quick workaround.
-   Add regression tests for security fixes.

## 10. Validation and Error Handling

-   Validate all user-controlled input, including query parameters, IDs,
    sort fields, dates, and uploaded files.
-   Use clear validation rules and useful messages.
-   Validate cross-table business rules in an appropriate service or
    request authorization layer.
-   Do not rely on frontend validation.
-   Handle expected domain errors consistently.
-   Use Laravel's exception handling and logging mechanisms for
    unexpected errors.
-   Never suppress errors silently or expose internal exception details
    in production.
-   Keep logs actionable without exposing secrets.
-   Update tests and API documentation when error behavior changes.

## 11. Events, Queues, Notifications, and Scheduler

-   Use events when they separate domain changes from reactions.
-   Use queued jobs for slow work that should not block HTTP responses.
-   Use Laravel Notifications for user-facing messages.
-   Use the Scheduler for reminders and recurrence processing.
-   Dispatch side effects after successful database commits where
    appropriate.
-   Make queue jobs safe to retry.
-   Handle failed jobs and configure retry/backoff intentionally.
-   Avoid sending notifications for failed transactions.
-   Scope notifications to the authenticated user.
-   Make recurrence generation idempotent so retries do not create
    duplicate tasks.
-   Do not add queues/events for trivial synchronous logic without a
    clear benefit.

## 12. Testing Rules

Every meaningful behavior change should include or update tests.

### Feature tests

Cover endpoint success, validation failures, authentication,
authorization, cross-project isolation, membership and assignment
constraints, status transitions, dependencies, audit history,
notifications, pagination, filtering, sorting, and file access where
relevant.

### Unit tests

Cover isolated business rules such as status transitions, dependency
cycle detection, progress calculations, and recurrence date
calculations.

### Testing workflow

1.  Identify relevant existing tests.
2.  Add tests for expected behavior and failure paths.
3.  Run the narrowest relevant test first.
4.  Run broader tests when practical.
5.  Report exact commands and results.
6.  If a test cannot run, state why; never claim it passed.

Use the project's testing framework and conventions. Use factories and
fakes where appropriate. Do not use real production services or data in
automated tests.

## 13. Documentation Rules

When a change affects API behavior, update the repository's existing API
documentation, such as `api-docs` or an OpenAPI specification.

Document as appropriate: - Method and path. - Authentication and
permission requirements. - Parameters and request payload. - Success
response. - Validation and error responses. - Important business
constraints. - Useful examples.

Keep `project.md` aligned with major architecture and product decisions.
Do not rewrite the whole project document for a small endpoint change.

If the user explicitly asks to update API docs or project notes, include
that in the task. Never claim documentation was updated unless it was
actually changed.

## 14. Dependencies and Refactoring

-   Do not add a package when Laravel or existing code provides a
    suitable solution.
-   Before adding a dependency, explain why it is needed and check
    PHP/Laravel compatibility.
-   Avoid unnecessary abstractions, speculative flexibility, and
    overengineering.
-   Do not refactor unrelated code while implementing a feature.
-   Do not rename routes, fields, classes, or response keys without a
    reason.
-   Preserve backward compatibility unless a breaking change is
    explicitly requested and documented.
-   Keep necessary refactors scoped and tested.
-   Do not silently change project architecture.
-   Avoid generating unused boilerplate.

## 15. Git and Change Management

-   Inspect repository status before editing when Git is available.
-   Do not discard, overwrite, or revert unrelated user changes.
-   Do not run destructive Git commands such as hard reset or forced
    checkout unless explicitly authorized.
-   Keep changes focused and reviewable.
-   Do not commit or push unless requested or explicitly authorized by
    the established workflow.
-   Never include secrets or local environment files.
-   Summarize migrations and environment/deployment steps.

## 16. Completion Checklist

Before declaring a task complete, verify as much as the environment
permits:

-   [ ] Requested behavior is implemented.
-   [ ] Existing architecture and naming conventions are preserved.
-   [ ] Authorization and project boundaries are enforced.
-   [ ] Validation and error handling are appropriate.
-   [ ] Database constraints and transactions are correct where needed.
-   [ ] Relevant tests were added or updated.
-   [ ] Relevant tests/checks were run and results reported accurately.
-   [ ] API documentation was updated if the contract changed.
-   [ ] No unrelated files or user changes were overwritten.
-   [ ] No secrets or sensitive data were introduced.
-   [ ] Required migration, queue, scheduler, or environment steps are
    documented.

### Final response format

After implementation, provide: 1. **Implemented:** behavior added or
fixed. 2. **Files changed:** key files and purpose. 3. **Database/API
changes:** migrations, endpoints, or contract changes. 4.
**Verification:** exact tests/commands run and outcomes. 5.
**Follow-up:** unresolved decisions, deployment steps, or limitations.

Never say a task is fully tested, secure, or complete unless available
evidence supports that claim.


---

## 17. Agent Memory Store (`docs/memory/`)

Use `docs/memory/` as a **fast reference** (read first, write after changes). Prefer memory over re-reading large trees when a note already exists.

### When to READ (start of task)
Before implementing or debugging:
1. `docs/memory/INDEX.md` (if present) — what exists
2. Matching notes under the folders below (actor/feature/job name)
If no note → scan code once, then **write** the note after you finish.

### When to WRITE (end of task — mandatory)
Update or create a memory file after you **create or change** any of:
- API endpoint / actor flow: `docs/memory/api/{version}-{actor}-{domain}.md`
- Frontend client docs: also update `api-docs/frontend-docs/{actor}/{domain}.md` (not memory — public client docs)
- Feature folder (`docs/features/…`): `docs/memory/features/{feature_folder}.md`
- Admin screen/module: `docs/memory/admin/{module}.md`
- Job / notification / listener: `docs/memory/jobs/{kebab-job-name}.md`
- Model / enum / important service: `docs/memory/domain/{name}.md`
- Architecture / product decision: `docs/memory/decisions/{slug}.md`
- Non-obvious bugfix: `docs/memory/fixes/{yyyy-mm}-{short-slug}.md`
Also: append one line to `docs/memory/INDEX.md` when adding a **new** file.

### Folder Layout
```
docs/memory/
  INDEX.md
  api/           # versioned HTTP flows
  features/      # feature-folder summaries
  admin/         # dashboard modules
  jobs/          # queues, notifications, listeners
  domain/        # models, enums, shared services
  decisions/     # locked choices (MODE_A/B, payments, …)
  fixes/         # regression notes worth keeping
```
Filenames: **kebab-case**, stable, searchable (e.g., `api/v1-user-auth.md`, `features/28_client_settlement_request.md`, `jobs/send-firebase-notification.md`, `domain/wallet.md`, `decisions/architecture-mode.md`).

### File Format (Keep short, max ~40–60 lines, bullets only)
```markdown
# {Title}

## Summary
One sentence.

## Routes / entry
- `METHOD /api/v1/…` → `{Controller}@{method}` (auth: {guard|guest})

## Flow
1. …
2. …

## Keys / response
- success → `key=success`, data: …
- fail → `key=fail` | `needActive` | …

## Files
- routes: `routes/guards/v1/…`
- service / resource / request: …

## Tests
- `tests/Feature/Api/…` | `./ci.sh --tests-only --test=…`

## Notes
- gotchas, OTP, stock rules, …
```
Rules:
- ✗ Do not store secrets, tokens, passwords, or `.env` values
- ✗ Do not duplicate full constitution / OpenAPI — link paths only
- ✓ Memory is the map; code is the source of truth if they disagree → fix memory
- ✓ After API work: write `api/…` memory **and** update `api-docs/frontend-docs/{actor}/{domain}.md` (+ `api-docs/enums.md` if needed); ensure tests noted (see `api-testing.md`)
- ✓ After feature implement/review: write `features/{FEATURE_FOLDER}.md` before marking done

---

## 18. API Building (Lean) Contract & Guidelines

Scan existing controllers/services for the actor before inventing paths. **Never introduce new response or layer patterns.** Client contract bible: `api-docs/base.md`. Per-endpoint examples: `api-docs/frontend-docs/{actor}/{domain}.md`.

### 18.1 Versioning & Routes (Mandatory — Product Rule)
**APIs are versioned. This is not optional and not a “nice-to-have feature.”**  
All new actor endpoints ship under `/api/v{N}/…`. Breaking or incompatible updates get a **new version**; older versions stay until clients migrate.
```
routes/api.php
  → Route::group(['prefix' => 'v1'|'v2'|…], …)
    → require routes/guards/{version}/{actor}.php
```
- New endpoint (compatible): Add under **current** version (`routes/guards/v1/…` today)
- Breaking change (shape/`key`/auth/path): Add **`v2`** (or next) group + `routes/guards/v2/{actor}.php` — **keep `v1` working**
- Deprecate: Mark in `api-docs/frontend-docs/…` — do **not** delete the old version in the same drop
- Legacy unversioned `/api/user`, `/api/general`: Freeze / maintain only — **no new product features** there; port new work to `v{N}`
- Actor file owns that actor’s groups only (`user`, `provider`, …). Shared partials: `routes/Api/*.php`.
- Controllers: `app/Http/Controllers/Api/{Actor|Domain}/` (version-specific controller/namespace only if responses diverge).
- Token env var for docs: `{{actor_token}}`.

### 18.2 Layer Flow
```
BaseApiRequest → (DTO if input) → Service → Model
Controller (HandlesApiExceptions) → Resource → jsonResponse()
```
| Piece | Path pattern |
|---|---|
| Request | `app/Http/Requests/Api/{Domain}/{Name}Request.php` extends `BaseApiRequest` |
| Service | `app/Services/{Domain}/{Name}Service.php` extends `BaseService` |
| Resource | `app/Http/Resources/Api/{Domain}/{Name}Resource.php` — `whenLoaded()` only |
| Controller | `app/Http/Controllers/Api/{Actor}/{Name}Controller.php` |
- No Eloquent in controllers — Service only.
- FormRequest = validation only (no DB/business rules).
- Skip empty FormRequest/DTO when endpoint has no input.
- Service returns `['key' => …, 'msg' => …, 'data' => …]`.

### 18.3 Controllers — Always `HandlesApiExceptions`
Trait: `app/Traits/Api/HandlesApiExceptions.php` (includes `ResponseTrait`).
```php
use App\Traits\Api\HandlesApiExceptions;

class ExampleController extends Controller
{
    use HandlesApiExceptions;

    public function store(ExampleRequest $request)
    {
        return $this->handleApi(function () use ($request) {
            $r = $this->service->store($request->validated());
            return $this->jsonResponse(
                msg: $r['msg'],
                data: ExampleResource::make($r['data']),
                key: $r['key'],
            );
        });
    }
}
```
- ✗ Manual `try/catch` in API controllers
- ✗ `response()->json(...)` — use `jsonResponse()` / helpers on the trait
- `handleApi()` maps exceptions → `key`: `fail`, `unauthenticated`, `unauthorized`, `not_found`, `exception`

### 18.4 Response Contract (Non-Negotiable)
**HTTP status is always `200`.** Clients branch on body **`key`** (see `api-docs/base.md`). Envelope **`code`** may follow `ResponseTrait::getCode($key)` (203/400/401/…) as a hint — still return HTTP 200 from the transport layer.
| Body field | Role |
|---|---|
| `key` | Client flow: `success`, `fail`, `unauthenticated`, `unauthorized`, `blocked`, `needActive`, `go_to_complete_data`, `pendingApproval`, `not_found`, `exception` |
| `code` | Envelope code from `getCode($key)` — **not** a substitute for `key` |
| `response_status.error` | `true` on non-success |
| `response_status.validation_errors` | Field errors map (or empty) |
| `msg` / `data` | Message + payload |

```php
// ✓ outcome in key; do not use HTTP 4xx/5xx for product flow
$this->jsonResponse(msg: $msg, data: $data, key: 'success');
$this->jsonResponse(msg: $msg, key: 'fail', error: true, errors: $errors);

// ✗ never encode outcome only in HTTP status
response()->json([...], 422);
```

### 18.5 Validation
- Extend `BaseApiRequest`.
- Add every input key to `lang/{locale}/validation.php` → `attributes` (translated labels, never raw `is_active` in messages).
- Prefer `__('apis.*')` for service/controller messages.

### 18.6 Form Bootstrap (`form-keys`) — Per Screen Only
**No global** `GET /api/v1/general/form-keys`. When a **specific** create/edit/filter screen needs lookups:
1. Add **`form-keys`** on that resource only (e.g. `GET /api/v1/user/hotels/{id}/form-keys`).
2. Return the **first 10** of **each** related collection for that screen — nested `data` + `has_more` + `load_more.path` (no `total_items` on nested keys). See `api-docs/base.md`.
3. Load-more via versioned list URLs, e.g. `GET /api/v1/general/countries/{country_id}/cities?page=2&per_page=10` (default **10**, max **100**).
4. Do **not** force the client to call many lookup APIs to open one form.

### 18.7 Capability `helpers` + Client Validation Meta
On **show / detail / form** resources where the user may act (update, cancel, delete, deactivate, …), return ability flags **and** any `*_validation` blocks **inside `helpers`** (never at `data` root):
```json
{
  "helpers": {
    "can_update": true,
    "can_cancel": false,
    "can_delete": false,
    "can_deactivate": false,
    "amount_validation": { "min": 1, "max": 5000 }
  }
}
```
- Only for actions that exist — not spam on every tiny list row unless the UI needs it.
- `helpers.*_validation` (`amount_validation`, `qty_validation`, …) mirrors FormRequest/business min/max/length for client UX (server still enforces).
- ✗ Do not put `amount_validation` (or other `*_validation`) as a sibling of `helpers` on `data`.
- Explain every `helpers` field in `api-docs/frontend-docs/...`.

### 18.8 Frontend Docs (Mandatory with Every API Change)
Update **`api-docs/frontend-docs/{actor}/{domain}.md`** in the **same task** as the endpoint:
Each endpoint section must include: method, full path, headers, path/query/body fields, **all response `key` cases** with full JSON examples, `helpers` (incl. nested `*_validation`), pagination notes.
- Keep **`api-docs/base.md`** accurate if you change envelope/pagination/helpers conventions.
- Register new enums in **`api-docs/enums.md`**.
- Order: implement → tests (`api-testing.md` + `./ci.sh`) → Apidog → **frontend-docs** → memory (`agent-memory-store.md`).

---

## 19. API Feature Testing (Lean) & Response Key Asserts

**Mandatory after every new/changed API endpoint.** Follow test-guard: behavior over mocks, one scenario per test, real models/DB, no framework-only fluff. Canonical style: `tests/Feature/Api/User/AuthTest.php`.

### 19.1 Where Tests Live
```
tests/Feature/Api/{Actor}/{Feature}Test.php
tests/Feature/Api/{Actor}/{Domain}/{Feature}FlowTest.php   # multi-step journeys
```
- Mirror actor + versioned API (`/api/v1/...` or project prefix).
- One class per feature area; use `#[DataProvider]` for input variants.
- `RefreshDatabase` + real factories/models — **never mock** User/Order/Product/DTO.
- Mock only system boundaries (SMS gateway, payment SDK, FCM, clock) — assert what you do with their result, not call args.

### 19.2 What Every Feature Must Cover
- **Happy path (full flow):** Chain the real user journey (e.g., register → verify → login → use token; or create → show → update → DB verify). Prefer one flow test + small focused edge case tests.
- **Failure & validation:** Missing/invalid fields → `key=fail` + `response_status.validation_errors`. Business rejects → correct `key`.
- **DB & side effects:** `assertDatabaseHas` / `assertDatabaseMissing` / counts / rows mutated.

### 19.3 Assert the API Contract
```php
$response->assertOk() // HTTP 200 always
    ->assertJsonPath('key', 'success') // or fail / needActive / …
    ->assertJsonPath('response_status.error', false);

$this->assertDatabaseHas('…', […]);
```
- Assert **`key`** (and useful `data.*`), not only HTTP status.
- Do **not** expect HTTP 422/401/403 for business outcomes — those live in `key`.
- Name tests: `test_<scenario>_<expected_outcome>`.

### 19.4 Response Payload Keys (Mandatory)
Success (and important fail) responses must lock the **public shape** of `data`:
1. **Required keys present:** `assertJsonStructure` (or explicit `assertJsonPath`) for every field client needs.
2. **Removed / forbidden keys absent:** `assertJsonMissingPath('data.user.password')`, `assertJsonMissingPath('data.old_field')`.
3. Envelope always checked: `key`, `msg`, `code`, `response_status`, `data`.
```php
$response->assertOk()
    ->assertJsonPath('key', 'success')
    ->assertJsonStructure([
        'key',
        'msg',
        'code',
        'response_status' => ['error', 'validation_errors'],
        'data' => [
            'user' => ['id', 'phone', 'token'],
        ],
    ])
    ->assertJsonMissingPath('data.user.password')
    ->assertJsonMissingPath('data.user.deleted_at');
```

### 19.5 Running CI
```bash
# Feature under test only (preferred while iterating)
./ci.sh --tests-only --test=tests/Feature/Api/{Actor}/{Feature}Test.php

# Optional method filter
./ci.sh --tests-only --test=tests/Feature/Api/{Actor} --filter=test_order_flow

# Broader check before handoff
./ci.sh --tests-only
```

---

## 20. Clean Code & AI Guardrails

### 20.1 Functions & Naming
- Names reveal intent — no `data`, `result`, `temp`, `helper`, `manager`, `utils`, `handle_*`, `process_*`
- ≤20 lines, one level of abstraction, one thing per function
- ≤4 params (use DTO/config object beyond that)
- No boolean flags — split into two functions
- Command-Query Separation: return value XOR side effect, never both

### 20.2 Comments
- Explain *why*, never *what* — delete paraphrasing comments
- Delete commented-out code, step-number scaffolding, banner comments
- Match file's existing style (casing, imports, error handling, logging)

### 20.3 SOLID
- **SRP**: One actor per module (Accounting, Auth, Reporting each get their own class)
- **OCP**: Extend via new code, not edits — no type-tag branches for new variants
- **LSP**: No subclass refuses parent's contract (no "not implemented" overrides)
- **DIP**: Abstractions live with the client, not the implementation

### 20.4 DRY / KISS / YAGNI
- DRY = don't duplicate *knowledge*, not text. Similar functions with different rules are not a violation.
- Wrong abstraction > duplication — re-inline before re-abstracting
- Cyclomatic complexity ≤10, nesting depth ≤5
- No speculative anything: no optional params, config flags, feature toggles, or base classes without a current caller

### 20.5 Error Handling
- Catch only specific error types you can recover from — never broad catch-all handlers
- No null/empty returns from catch handlers unless the contract documents it
- No defensive guards for impossible cases (trust the type system)

### 20.6 AI-Specific Guardrails
- Verify every import/external call against the actual installed library — do not hallucinate APIs
- No hardcoded "success" returns or fixture data in production code
- Re-derive from spec, don't copy-from-similar (off-by-one enters through copy-paste)
- Enumerate boundary cases (null/empty/one/many, off-by-one, even/odd) before writing them
- Strip dead code before delivery (unused imports, unreachable branches)

### 20.7 Refactoring
- Preserve observable behavior — refactors and bug fixes are two separate operations, never bundle them.

---

## 21. Laravel Agent Execution Protocol (Trigger, Modes, Quality Gate & Apidog Sync)

### 21.1 Trigger & Mode Detection
When folder name + trigger word is detected:
- **IMPLEMENT**: `نفذ` / `اعمل` / `شغل` / `ابدا` / `execute` / `run`
- **REVIEW**: `راجع` / `افحص` / `تحقق` / `review` / `audit` / `enhance`
- **Auto-detection**: If no existing code for `{FEATURE_FOLDER}` → IMPLEMENT MODE. If existing code found → REVIEW MODE. Silent detection, start immediately.

### 21.2 Architecture Mode (`docs/architecture-mode.md`)
- If file exists: read `MODE_A` or `MODE_B` and lock it.
- If not: prompt developer once:
  - `MODE_A` (Full Repository Pattern): `Controller → FormRequest → DTO → Service → Repository → Model`
  - `MODE_B` (Lean Service + Controller): `Controller → FormRequest → DTO → Service → Model`
  - Lock selection in `docs/architecture-mode.md`.

### 21.3 Hard Constraints (Non-Negotiable)
- ✗ No `response()->json()` in API — always `HandlesApiExceptions` + `jsonResponse()`
- ✗ No manual `try/catch` in API controllers — use `handleApi()`
- ✗ **API HTTP status always `200`** — failure only via body `key` + `response_status`
- ✗ API routes must live under `routes/guards/{v1|v2|…}/{actor}.php`
- ✗ No Eloquent directly in Controllers — ever
- ✗ No Eloquent directly in Services — (MODE_A only; MODE_B Services may call Model directly)
- ✗ No lazy loading in Resources — use `whenLoaded()` only
- ✗ No business logic or DB queries in FormRequests
- ✗ Admin: no HTML `required` — validate via FormRequest; every field in `validation.attributes` translated
- ✗ DTO must be `readonly` with `fromRequest()` static method; controller uses `SomeDTO::fromRequest($request)`
- ✗ Do NOT create empty FormRequests or empty DTOs when endpoint has no input
- ✗ Reuse core entities: `User` for human actors, `Intro` for onboarding/splash, `Image` for sliders/banners

### 21.4 Execution Order
1. Migrations (`[NEW]` entities only)
2. Enums + `lang/{locale}/enums.php`
3. DTOs (when input exists)
4. Repository contracts (MODE_A only)
5. Repository Eloquent implementations (MODE_A only)
6. Services (calls Repo in MODE_A / Model in MODE_B)
7. Factories (`[NEW]` models only)
8. Seeders (`[NEW]` models only)
9. FormRequests + `validation.php` attributes (when input exists)
10. Resources
11. Controllers
12. Routes (under `routes/guards/{version}/{actor}.php`)
13. **API Documentation Sync (Apidog)**: sync after every endpoint via Apidog API with env vars for Base URL and tokens.
14. Admin routes / controllers / views / translations
15. Feature tests (per `api-testing.md`) + `./ci.sh --tests-only`
16. Frontend docs (`api-docs/frontend-docs/{actor}/{domain}.md`) + Memory (`docs/memory/`)

### 21.5 Quality Gate Checklist
Before declaring complete:
- [ ] Architecture mode locked and respected
- [ ] No Eloquent in Controllers; Services follow MODE_A/B
- [ ] No N+1 risk; `whenLoaded()` in Resources
- [ ] API controllers use `HandlesApiExceptions` + `handleApi()`
- [ ] Responses always HTTP 200 via `jsonResponse()`; outcome in `key` + `response_status`
- [ ] API routes under `routes/guards/{version}/{actor}.php`
- [ ] DTO readonly with `fromRequest()` (no empty DTO/FormRequest)
- [ ] Entities properly reused (`User`, `Intro`, `Image`)
- [ ] Apidog synced for all endpoints
- [ ] API feature tests cover happy path + failure keys + DB asserts
- [ ] Required response keys tested via `assertJsonStructure`; deleted/secret keys absent via `assertJsonMissingPath`
- [ ] `./ci.sh --tests-only --test=…` passed
- [ ] `docs/memory/` and `api-docs/frontend-docs/` updated

---

## 22. Active Rule Files Reference

The modular rule files are actively maintained in `.agents/rules/` and `.cursor/rules/`:
1. `.agents/rules/agent-memory-store.md` — Short project memory store in `docs/memory/`
2. `.agents/rules/api-building.md` — Lean API building contract, versioned routing, envelope
3. `.agents/rules/api-testing.md` — Lean API feature testing, journey tests, response keys
4. `.agents/rules/clean-code-guard.md` — Clean code rules, SOLID, KISS/DRY/YAGNI, AI guardrails
5. `.agents/rules/laravel-agent.md` — Full execution pipeline, mode detection, quality gate, Apidog sync
