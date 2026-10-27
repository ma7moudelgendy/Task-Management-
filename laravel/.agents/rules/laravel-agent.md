---
description: "Laravel agent — execute immediately when folder name + trigger word detected. Enforces architecture, constitution compliance, and Postman or apidog sync after every endpoint."
alwaysApply: true
---

# TRIGGER — EXECUTE IMMEDIATELY

## STEP T — MODE DETECTION (run before anything else)

When the user writes a folder name + any trigger word → run this detection first:

**Trigger words (all modes):**
- IMPLEMENT: `نفذ` / `اعمل` / `شغل` / `ابدا` / `execute` / `run`
- REVIEW: `راجع` / `افحص` / `تحقق` / `review` / `audit` / `enhance`

**Auto-detection (when trigger word is ambiguous or missing):**

1. Search the codebase for any files related to `{FEATURE_FOLDER}`:
   - Migrations, Models, Controllers, Services, Repositories, Routes
   - `docs/features/{FEATURE_FOLDER}/` contents

2. **No existing code found** → **IMPLEMENT MODE** → go to STEP 0
3. **Existing code found** → **REVIEW MODE** → go to REVIEW FLOW

→ **DO NOT ask any clarifying questions.**
→ **DO NOT say "I'm ready" or "I'll start now."**
→ **Detect mode silently and start immediately.**

---

# ═══════════════════════════════
# IMPLEMENT MODE
# ═══════════════════════════════

---

# STEP -1 — ARCHITECTURE MODE (MANDATORY — before reading anything else)

1. Check if `docs/architecture-mode.md` exists.

**File EXISTS:**
- Read it. It contains exactly one word: `MODE_A` or `MODE_B`.
- Lock that mode for this entire task. Never ask again.

**File DOES NOT EXIST (first task on this project):**
- Stop. Ask the developer exactly this — no rewording:

  > **Before we start, I need one architectural decision that will apply to the entire project and cannot be changed later.**
  >
  > **Option A — Full (Repository Pattern):**
  > `Controller → FormRequest → DTO → Service → Repository → Model`
  >
  > **Option B — Lean (Service + Controller only):**
  > `Controller → FormRequest → DTO → Service → Model`
  > No Repository layer. Service calls Model directly.
  >
  > **Type A or B:**

- Wait for the answer.
- Create `docs/architecture-mode.md` with one line: `MODE_A` or `MODE_B`.
- Confirm: *"Architecture mode locked as [A/B]. Applying to all future features."*
- Then continue.

→ **Carry `{ARCH_MODE}` = `MODE_A` or `MODE_B` through every step below.**

---

# STEP 0 — PRE-IMPLEMENTATION READ (IMPLEMENT MODE — MANDATORY, SILENT)

Read ALL of the following silently before writing any code:

1. `docs/agent/laravel_constitution.md`
2. `docs/features/{FEATURE_FOLDER}/feature_name.md`
3. `docs/features/{FEATURE_FOLDER}/figma_fragment.json`
4. `docs/features/{FEATURE_FOLDER}/analysis_fragment.md`
5. Stack rules (silent — follow, do not paste into chat):
   - Shared base: `.cursor/rules/base-architecture.md`
   - Any API work: `.cursor/rules/api-building.md` (versioned `routes/guards/v1|v2`, `HandlesApiExceptions`, HTTP 200 + `key`)
   - After API endpoints: `.cursor/rules/api-testing.md` then `./ci.sh --tests-only --test=…`
   - Any Admin work: `.cursor/rules/admin-dashboard.md` (FormRequest only, no HTML `required`, translated attributes, card/grid UI)

**STEP 0-A — Existing Models Check (before writing any code):**

For every entity in the feature, check against this map:

| Business Concept | Existing Model | Existing Table |
|---|---|---|
| User / Client / Customer / Driver / any human actor | `User` | `users` |
| Splash / Intro / Onboarding / Walkthrough screens | `Intro` | `intros` |
| Homepage slider / Banner / Carousel / Rotating images | `Image` | `images` |

Mark each entity as `[REUSE]` or `[NEW]`. Only `[NEW]` entities get a migration.

**STEP 0-B — RepositoryServiceProvider Check (MODE_A — first task only):**

```
□ Does app/Providers/RepositoryServiceProvider.php exist?
  NO → create it and register it before any other file
  YES → continue
```

Then output ONLY this summary block — then proceed immediately to code:

```
=== IMPLEMENTATION SUMMARY ===
Feature      : {FEATURE_FOLDER}
Arch Mode    : {ARCH_MODE}
Entities     : [list — mark each REUSE or NEW]
Enums        : [list or NONE]
Endpoints    : [METHOD /path — purpose — input: YES/NO]
Admin Screens: [list or NONE]
Tests        : [list]
Trans Keys   : [list]
Factories    : [NEW models only]
Requests/DTOs: [endpoints with input:YES only]
==============================
```

→ After printing the summary, **proceed immediately to STEP 0b then start coding.**

---

# STEP 0b — GIT BRANCH SETUP (MANDATORY — before writing any file)

Follow `.cursor/rules/branches-instructions.md` (branch names + clear commits).

```bash
# 1. Switch to default branch and pull latest
git checkout {DEFAULT_BRANCH}
git pull origin {DEFAULT_BRANCH}

# 2. Create and switch to feature branch
git checkout -b feature/{FEATURE_FOLDER}
```

- `{DEFAULT_BRANCH}` → detect from `git symbolic-ref refs/remotes/origin/HEAD`, or use the branch the user is currently on before triggering
- `{FEATURE_FOLDER}` → exact folder name as given (e.g. `28_client_settlement_request`)
- Fix-only work → `fix/{short-slug}` per `branches-instructions.md`
- API breaking changes → new **API** version (`v2`) on this feature branch — **do not** delete `v1` routes (`api-building.md`)

→ Only after the branch is created, **start writing files.**

---

# EXECUTION ORDER (MANDATORY — DO NOT REORDER OR SKIP)

> Steps marked **(MODE_A only)** are skipped entirely in MODE_B.
> Steps marked **(when input exists)** are skipped for endpoints with no user input.

1.  migrations — `[NEW]` entities only
2.  enums (if any) + lang/{locale}/enums.php
3.  DTOs — **(when input exists)** — never create an empty DTO
4.  repository contracts (interfaces) — **(MODE_A only)**
5.  repository Eloquent implementations — **(MODE_A only)**
6.  services — call Repository in MODE_A / call Model directly in MODE_B
7.  factories — `[NEW]` models only
8.  seeders — `[NEW]` models only
9.  API FormRequests + validation.php attributes — **(when input exists)** — never create an empty FormRequest
10. API Resources
11. API Controllers
12. API routes — under `routes/guards/{version}/{actor}.php` (see `api-building.md`); controllers use `HandlesApiExceptions` + `handleApi()`

**12b. API DOCUMENTATION SYNC (MANDATORY — after every API endpoint)**

Immediately after creating or modifying any API endpoint:

→ Use **Apidog** (via its API).

Workflow:

```
Endpoint completed
    ↓
Detect actor
    ↓
Discover Apidog project/collection
    ↓
Read existing environment variables
    ↓
Create or update request via Apidog API
    ↓
Add parameter descriptions
    ↓
Add enum descriptions
    ↓
Add request/response examples
    ↓
Save
```

Rules:

- Update existing requests whenever possible.
- Never create duplicate requests.
- Always use Environment Variables for:
  - Base URL
  - Tokens
  - Secret keys
  - Language
- Never hardcode tokens.
- If the Base URL already ends with `/api`, never append `/api` again.
- Every request parameter must have a clear description.
- Every enum must document every possible value with a human-readable description.
- Keep examples synchronized with the backend implementation.

Only continue to Step 13 after synchronization succeeds.

Confirm with:

```
API documentation synchronized.

Platform : Apidog
Actor    : {actor}
Endpoint : {METHOD} {PATH}

✓ Environment variables used
✓ Parameter descriptions added
✓ Enum descriptions added
✓ Examples updated
✓ Documentation updated
```

13. Admin routes (parent metadata + routes-links)
14. Admin controllers
15. Admin Blade views (index / table / create / edit / show) — follow `admin-dashboard.md` (cards/grid, no HTML `required`)
16. Translation keys: admin.php, apis.php, enums.php, validation.php (`attributes` for every form field)
17. Feature tests (API + Admin) — **API: follow `api-testing.md`** (full actor flow, fail/validation, DB asserts); then run `./ci.sh --tests-only --test=tests/Feature/Api/…`
18. Documentation: README/OpenAPI if used + **`api-docs/frontend-docs/{actor}/{domain}.md`** (full request/response/`key` cases) + enums in `api-docs/enums.md` if needed + **memory** `docs/memory/features/…` and/or `docs/memory/api/…` (`agent-memory-store.md`); update `docs/memory/INDEX.md` if new
19. Final quality gate checklist

---

# HARD CONSTRAINTS (NON-NEGOTIABLE)

✗ No `response()->json()` in API — always `HandlesApiExceptions` + `jsonResponse()` (`app/Traits/Api/HandlesApiExceptions.php`)
✗ No manual `try/catch` in API controllers — use `handleApi()`
✗ **API HTTP status always `200`** — failure only via body `key` + `response_status` (never `code: 4xx/5xx` on `jsonResponse()`)
✗ API routes must live under `routes/guards/{v1|v2|…}/{actor}.php` per `api-building.md`
✗ No Eloquent directly in Controllers — ever
✗ No Eloquent directly in Services — **(MODE_A only; MODE_B Services may call Model directly)**
✗ No lazy loading in Resources — use `whenLoaded()` only
✗ No business logic in FormRequests — validation only
✗ No DB queries in FormRequests
✗ Admin: **no HTML `required`** — validate via FormRequest; every field in `validation.attributes` (translated; never raw `is_active` in messages)
✗ Admin UI: calm card/grid layout per `admin-dashboard.md` — reuse `x-admin.*`
✗ Do NOT modify permissions system or SideBarTrait
✗ Do NOT invent business rules not in spec files
✗ DTO must be `readonly` with `fromRequest()` static method
✗ Controller must use `SomeDTO::fromRequest($request)` — never `new SomeDTO(...)` inline
✗ Do NOT create an empty FormRequest (no rules) — skip entirely if endpoint has no input
✗ Do NOT create an empty DTO (no properties) — skip entirely if endpoint has no input
✗ Do NOT create a new Model/table for user types (client, customer…) — always reuse `User` + `users`
✗ Do NOT create a new Model/table for onboarding/splash — always reuse `Intro` + `intros`
✗ Do NOT create a new Model/table for sliders/banners — always reuse `Image` + `images`
✗ Do NOT register repository bindings in `AppServiceProvider` — use `RepositoryServiceProvider` **(MODE_A only)**
✗ Do NOT mix modes — MODE_A = all features use Repository; MODE_B = no feature uses Repository

---

# ARCHITECTURE FLOW

**MODE_A — Full (Repository Pattern):**
```
API:   FormRequest → DTO → Service → Repository → Model
       Controller (HandlesApiExceptions::handleApi) → Resource → jsonResponse()  // HTTP 200
Admin: FormRequest → DTO → Service → AdminBasicController  // + admin-dashboard.md UI/validation
```

**MODE_B — Lean (Service + Controller only):**
```
API:   FormRequest → DTO → Service → Model
       Controller (HandlesApiExceptions::handleApi) → Resource → jsonResponse()  // HTTP 200
Admin: FormRequest → DTO → Service → AdminBasicController  // + admin-dashboard.md UI/validation
```

> FormRequest and DTO are created **only when the endpoint receives user input.**
> Details: `api-building.md` · `admin-dashboard.md` · `base-architecture.md`

---

# QUALITY GATE — OUTPUT AT THE END

```
□ docs/architecture-mode.md exists and was read before any code was written
□ docs/README.md updated
□ docs/openapi.json updated
□ No Eloquent in Controllers
□ [MODE_A] Repository layer exists (contract + implementation)
□ [MODE_A] No Eloquent in Services
□ [MODE_A] RepositoryServiceProvider exists and all bindings registered there
□ [MODE_B] No Repository files created
□ No N+1 risk
□ No lazy loading in Resources — whenLoaded() used
□ API controllers use HandlesApiExceptions + handleApi()
□ All API responses via jsonResponse(); HTTP status always 200; outcome in key + response_status
□ No code: 4xx/5xx passed to jsonResponse()
□ API routes under routes/guards/{version}/{actor}.php
□ OpenAPI has real example values (no empty strings, no null)
□ OpenAPI has x-postman-test on every endpoint
□ DTO is readonly with fromRequest() — only for endpoints with input
□ No empty DTO or empty FormRequest created
□ FormRequest has no DB queries or business checks
□ Admin forms: no HTML required; validation.attributes translated (ar+en)
□ Admin views: card/grid layout, translated chrome, reuse x-admin.*
□ SoftDeletes on all new Models
□ SOLID principles respected
□ Permissions system untouched
□ No new Model created for user/client/customer types — User model reused
□ No new Model created for onboarding/splash — Intro model reused
□ No new Model created for sliders/banners — Image model reused
□ Factories + Seeders exist for all [NEW] models only
□ Seeder has duplicate guard + DB::transaction() + 50 records
□ Apidog requests synced for all endpoints
□ API Feature tests under tests/Feature/Api/{Actor}/ per api-testing.md
□ Full flow + failure/validation + DB asserts covered (no unjustified domain mocks)
□ Response key contract: required keys via assertJsonStructure; deleted/secret keys via assertJsonMissingPath
□ ./ci.sh --tests-only --test=… passed for new/changed API tests
□ docs/memory updated (api/ and/or features/) + INDEX.md if new file
□ api-docs/frontend-docs/{actor}/{domain}.md updated (all keys + examples); enums.md if needed
□ form-keys / helpers.can_* / helpers.*_validation added where needed (under helpers only — api-building.md)
```

Every unchecked box → fix before declaring complete.

---

# STEP 19b — GIT COMMIT, PUSH & MR (MANDATORY — after quality gate passes)

```bash
# 1. Stage all changes
git add .

# 2. Commit
git commit -m "feat({FEATURE_FOLDER}): implement feature"

# 3. Push the feature branch
git push origin feature/{FEATURE_FOLDER}

# 4. Open a Merge Request on GitLab
glab mr create \
  --target-branch {DEFAULT_BRANCH} \
  --source-branch feature/{FEATURE_FOLDER} \
  --title "feat({FEATURE_FOLDER}): implement feature" \
  --description "Implements feature {FEATURE_FOLDER} following laravel_constitution.md"

# 5. Switch back to default branch
git checkout {DEFAULT_BRANCH}
```

- Use `glab` CLI for the MR (GitLab CLI — must be installed and authenticated)
- If `glab` is not available → output the MR URL manually:
  `https://gitlab.com/{owner}/{repo}/-/merge_requests/new?merge_request[source_branch]=feature/{FEATURE_FOLDER}&merge_request[target_branch]={DEFAULT_BRANCH}`
- After switching back to default branch → confirm:
  `"✓ MR opened. Switched back to {DEFAULT_BRANCH}. Feature {FEATURE_FOLDER} complete."`

# STEP 19c — MOVE TO 00_FINISHED

```bash
# Create 00_finished folder if it does not exist and move the feature folder into it
mkdir -p docs/features/00_finished
mv docs/features/{FEATURE_FOLDER} docs/features/00_finished/{FEATURE_FOLDER}
```

---

# ═══════════════════════════════
# REVIEW MODE
# ═══════════════════════════════

## REVIEW FLOW — AUDIT, REFACTOR & COMPLETE

### REVIEW STEP 0 — READ SPECS & SCAN CODEBASE (SILENT)

**First: resolve architecture mode** — read `docs/architecture-mode.md` (same as Step -1 in IMPLEMENT MODE).

Read ALL of the following silently:

1. `docs/agent/laravel_constitution.md`
2. `docs/features/{FEATURE_FOLDER}/feature_name.md`
3. `docs/features/{FEATURE_FOLDER}/figma_fragment.json`
4. `docs/features/{FEATURE_FOLDER}/analysis_fragment.md`

Then scan ALL existing files related to this feature:
- Migrations, Models, Enums, DTOs
- Repository contracts + implementations *(MODE_A only)*
- Services
- API FormRequests, Resources, Controllers, Routes
- Admin Controllers, Routes, Blades
- Factories, Seeders
- Tests
- Docs (README + OpenAPI)

Output this audit summary before doing anything:

```
=== AUDIT SUMMARY: {FEATURE_FOLDER} ===
Arch Mode        : {ARCH_MODE}
Files found      : [list every file found]
Files missing    : [list every file that SHOULD exist but doesn't]
Violations found : [list every rule violation with file + line description]
Compliant files  : [list files with no issues]
========================================
```

---

### REVIEW STEP 0b — GIT BRANCH SETUP

Follow `.cursor/rules/branches-instructions.md`.

```bash
git checkout {DEFAULT_BRANCH}
git pull origin {DEFAULT_BRANCH}
git checkout -b review/{FEATURE_FOLDER}
```

---

### REVIEW STEP 1 — REFACTOR VIOLATIONS

For every violation found in the audit:

- **Fix it immediately** — do not ask for confirmation
- Apply the exact rule from `laravel_constitution.md`
- Do NOT rewrite compliant code
- Do NOT change business logic — only fix architecture/structure violations

**Common violations to check:**

```
✗ response()->json() / manual try/catch in API → HandlesApiExceptions::handleApi() + jsonResponse()
✗ Non-200 HTTP or code: 4xx/5xx on API jsonResponse → force HTTP 200; set key + response_status
✗ API route outside routes/guards/{version}/{actor}.php → move under active version
✗ Eloquent in Controller → move to Service (both modes)
✗ Eloquent in Service → [MODE_A] move to Repository / [MODE_B] allowed
✗ Lazy loading in Resource → replace with whenLoaded()
✗ DB queries in FormRequest → move to Service
✗ Admin HTML required / data-validation-required-message → remove; FormRequest + validation.attributes
✗ Raw field keys in validation messages (is_active) → add translated validation.attributes
✗ Busy admin layout → split into cards/grid per admin-dashboard.md
✗ DTO not readonly → fix class declaration
✗ DTO missing fromRequest() → add static method
✗ Controller using new SomeDTO(...) → replace with SomeDTO::fromRequest($request)
✗ Empty FormRequest with no rules → delete it (endpoint needs no FormRequest)
✗ Empty DTO with no properties → delete it (endpoint needs no DTO)
✗ Wrong namespace → fix to match constitution pattern
✗ Missing SoftDeletes on Model → add trait + migration column
✗ Missing indexes on migration → add
✗ Hardcoded enum strings → replace with EnumClass::values()
✗ [MODE_A] Repository bindings in AppServiceProvider → move to RepositoryServiceProvider
✗ [MODE_A] Missing RepositoryServiceProvider → create and register it
✗ [MODE_B] Repository files exist → remove, move logic into Service
✗ New Model created for user/client types → merge into User model
✗ New Model created for onboarding/splash → merge into Intro model
✗ New Model created for sliders/banners → merge into Image model
```

---

### REVIEW STEP 2 — GENERATE MISSING FILES

For every file in the MISSING list from the audit — generate it from scratch following the constitution exactly.

Use the same EXECUTION ORDER as IMPLEMENT MODE (mode-aware):

```
Missing migrations    → generate ([NEW] entities only)
Missing enums         → generate
Missing DTOs          → generate (only for endpoints with input)
Missing repo contract → generate (MODE_A only)
Missing repo impl     → generate (MODE_A only)
Missing service       → generate
Missing factory       → generate ([NEW] models only — 50 records)
Missing seeder        → generate ([NEW] models only — registered in DatabaseSeeder.php)
Missing FormRequest   → generate (only for endpoints with input)
Missing Resource      → generate
Missing Controller    → generate
Missing routes        → generate
Missing admin files   → generate
Missing translations  → generate
Missing tests         → generate
Missing docs          → generate
```

---

### REVIEW STEP 3 — APIDOG SYNC

For every API endpoint that was added or changed → sync via Apidog API immediately.

---

### REVIEW QUALITY GATE

Run the same quality gate as IMPLEMENT MODE — every box must pass before committing.

---

### REVIEW STEP 4 — GIT COMMIT, PUSH & MR

```bash
git add .
git commit -m "review({FEATURE_FOLDER}): audit, refactor and complete"
git push origin review/{FEATURE_FOLDER}

glab mr create \
  --target-branch {DEFAULT_BRANCH} \
  --source-branch review/{FEATURE_FOLDER} \
  --title "review({FEATURE_FOLDER}): audit, refactor and complete" \
  --description "Audited and completed feature {FEATURE_FOLDER} per laravel_constitution.md"

git checkout {DEFAULT_BRANCH}
```

Confirm: `"✓ MR opened. Switched back to {DEFAULT_BRANCH}. Review of {FEATURE_FOLDER} complete."`

### REVIEW STEP 5 — MOVE TO 00_FINISHED

```bash
mkdir -p docs/features/00_finished
mv docs/features/{FEATURE_FOLDER} docs/features/00_finished/{FEATURE_FOLDER}
```

---

# BEHAVIOR RULES

- **No stalling.** Never say "I'm ready when you are" or "Should I begin?"
- **No confirmation loops.** Trigger detected → execute immediately.
- **No partial stops.** Complete all steps in one run unless user interrupts.
- **Silent reads.** Do not narrate file reading — just read and proceed.
- **One summary, then code.** Print summary once, then generate files non-stop.
- **Apidog is mandatory.** Every API endpoint must be synced before moving on.
