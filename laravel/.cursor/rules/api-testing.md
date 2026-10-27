---
description: API Feature test structure — full actor flows, success/fail/validation, DB asserts. Use after every API change; run via ./ci.sh. Aligns with test-guard.
globs: tests/Feature/Api/**/*.php,tests/Feature/**/*Api*Test.php,tests/Feature/**/*Test.php
alwaysApply: false
---

# API Feature Testing (Lean)

**Mandatory after every new/changed API endpoint.** Follow test-guard: behavior over mocks, one scenario per test, real models/DB, no framework-only fluff.

Canonical example style: `tests/Feature/Api/User/AuthTest.php`.

## 1. Where tests live

```
tests/Feature/Api/{Actor}/{Feature}Test.php
tests/Feature/Api/{Actor}/{Domain}/{Feature}FlowTest.php   # multi-step journeys
```

- Mirror actor + versioned API (`/api/v1/...` or project prefix).
- One class per feature area; use `#[DataProvider]` for input variants (test-guard Rule 3).
- `RefreshDatabase` + real factories/models — **never mock** User/Order/Product/DTO (Rules 8–9).
- Mock only system boundaries (SMS gateway, payment SDK, FCM, clock) — assert what you do with their result, not call args (Rule 2).

## 2. What every feature must cover

Write tests that catch **distinct bugs**. Skip trivial getters / framework defaults (Rules 4, 7).

### Happy path (full flow)

Chain the real user journey for this feature, for example:

| Domain | Flow to cover |
|---|---|
| Auth | register → (OTP) verify/activate → login → assert token usable on a protected route |
| Profile | auth → update → GET profile reflects DB |
| Catalog/orders | auth → list products → add cart → place order → assert stock/availability + DB rows |
| Any resource | create → show → update/list → side effects in DB |

Prefer **one flow test** that walks the journey + **small focused tests** for edge cases — not 20 near-duplicate copies.

### Failure & validation

- Missing/invalid fields → `key=fail` + `response_status.validation_errors` (or project equivalent)
- Business rejects (blocked, needActive, not_found, unauthorized, insufficient stock, …) → correct **`key`**
- Auth required → unauthenticated / blocked paths when relevant

### DB & side effects

After success (and relevant failures): `assertDatabaseHas` / `assertDatabaseMissing` / stock counts / pivot rows / OTP rows — whatever the feature mutates.

## 3. Assert the API contract

Per `api-building.md`:

```php
$response->assertOk() // HTTP 200 always
    ->assertJsonPath('key', 'success') // or fail / needActive / …
    ->assertJsonPath('response_status.error', false);

$this->assertDatabaseHas('…', […]);
```

- Assert **`key`** (and useful `data.*`), not only HTTP status.
- Do **not** expect HTTP 422/401/403 for business outcomes — those live in `key`.
- Name tests: `test_<scenario>_<expected_outcome>` (Rule 5).

### Response payload keys (mandatory)

Success (and important fail) responses must lock the **public shape** of `data` — so refactors that drop or leak fields fail CI.

1. **Required keys present** — `assertJsonStructure` (or explicit `assertJsonPath`) for every field the client needs.
2. **Removed / forbidden keys absent** — after deleting a field from a Resource (or never exposing secrets), assert it is **gone**:
   - `assertJsonMissingPath('data.user.password')`
   - `assertJsonMissingPath('data.old_field')`
   - for list items: decode and assert `array_key_exists` is false, or use structure that does not include the key
3. When a key is **intentionally ignored** by clients (deprecated, debug-only), either:
   - remove it from Resource and assert missing, or
   - document it as allowed-only-in-local and assert missing in production-shaped tests
4. Envelope always checked: `key`, `msg`, `code`, `response_status`, `data`.

```php
$response->assertOk()
    ->assertJsonPath('key', 'success')
    ->assertJsonStructure([
        'key',
        'msg',
        'code',
        'response_status' => ['error', 'validation_errors'],
        'data' => [
            'user' => ['id', 'phone', 'token'], // required client keys
        ],
    ])
    ->assertJsonMissingPath('data.user.password')
    ->assertJsonMissingPath('data.user.deleted_at') // example: removed/internal keys
    ->assertJsonMissingPath('data.legacy_cart_id'); // example: deleted API key
```

Update these asserts in the **same PR** that adds/renames/deletes Resource fields — do not leave stale structures.
## 4. Auth helper pattern

```php
$token = $this->postJson('/api/v1/…/login', $payload)->json('data.user.token');
$this->withToken($token)->postJson('/api/v1/…/orders', $body)…
```

Or Sanctum/`actingAs` when the suite already uses that — stay consistent with existing Feature tests.

## 5. After tests are written — run CI (mandatory)

```bash
# Feature under test only (preferred while iterating)
./ci.sh --tests-only --test=tests/Feature/Api/{Actor}/{Feature}Test.php

# Optional method filter
./ci.sh --tests-only --test=tests/Feature/Api/{Actor} --filter=test_order_flow

# Broader check before handoff
./ci.sh --tests-only
```

- Fix failures before declaring the API done.
- Do not skip with `--skip-tests` unless the user explicitly asks.
- Agents: prefer `-y` / `--tests-only` as in `./ci.sh -h`.

## 6. Checklist

```
□ Feature test file(s) under tests/Feature/Api/{Actor}/
□ Happy full flow covered (auth + feature steps as needed)
□ Validation + business failure keys covered
□ DB / stock / side-effect asserts where state changes
□ HTTP 200 + assertJsonPath('key', …)
□ Response keys: assertJsonStructure for required fields + assertJsonMissingPath for deleted/secret/internal keys
□ No unjustified mocks of domain models
□ ./ci.sh --tests-only --test=… passed
```
