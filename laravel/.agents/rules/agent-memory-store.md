---
alwaysApply: true
description: "Read/write docs/memory after APIs, features, jobs, fixes — short project memory to avoid re-scanning the codebase."
---

# Agent Memory Store

Use `docs/memory/` as a **fast reference** (read first, write after changes). Prefer memory over re-reading large trees when a note already exists.

## When to READ (start of task)

Before implementing or debugging:

1. `docs/memory/INDEX.md` (if present) — what exists
2. Matching notes under the folders below (actor/feature/job name)

If no note → scan code once, then **write** the note after you finish.

## When to WRITE (end of task — mandatory)

Update or create a memory file after you **create or change** any of:

| Work | Memory path |
|---|---|
| API endpoint / actor flow | `docs/memory/api/{version}-{actor}-{domain}.md` |
| Frontend client docs | also update `api-docs/frontend-docs/{actor}/{domain}.md` (not memory — public client docs) |
| Feature folder (`docs/features/…`) | `docs/memory/features/{feature_folder}.md` |
| Admin screen/module | `docs/memory/admin/{module}.md` |
| Job / notification / listener | `docs/memory/jobs/{kebab-job-name}.md` |
| Model / enum / important service | `docs/memory/domain/{name}.md` |
| Architecture / product decision | `docs/memory/decisions/{slug}.md` |
| Non-obvious bugfix | `docs/memory/fixes/{yyyy-mm}-{short-slug}.md` |

Also: append one line to `docs/memory/INDEX.md` when adding a **new** file.

## Folder layout

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

Filenames: **kebab-case**, stable, searchable. Examples:

- `api/v1-user-auth.md`
- `api/v1-user-orders.md`
- `features/28_client_settlement_request.md`
- `jobs/send-firebase-notification.md`
- `domain/wallet.md`
- `decisions/architecture-mode.md`

## File format (keep short)

Max ~40–60 lines. Bullets only — no essays, no pasted code dumps.

```md
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

Omit empty sections. **Update in place** when the same API/feature changes — do not create duplicates.

## Rules

- ✗ Do not store secrets, tokens, passwords, or `.env` values
- ✗ Do not duplicate full constitution / OpenAPI — link paths only
- ✓ Memory is the map; code is the source of truth if they disagree → fix memory
- ✓ After API work: write `api/…` memory **and** update `api-docs/frontend-docs/{actor}/{domain}.md` (+ `api-docs/enums.md` if needed); ensure tests noted (see `api-testing.md`)
- ✓ After feature implement/review: write `features/{FEATURE_FOLDER}.md` before marking done
