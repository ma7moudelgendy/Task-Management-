---
description: How to build versioned API endpoints — routes, controllers, services, form-keys, helpers, frontend docs, HandlesApiExceptions, HTTP 200 + key contract.
globs: app/Http/Controllers/Api/**/*.php,app/Http/Requests/Api/**/*.php,app/Http/Resources/Api/**/*.php,app/Services/**/*.php,routes/api.php,routes/guards/**/*.php,routes/Api/**/*.php,api-docs/**/*.md
alwaysApply: false
---

# API Building (Lean)

Scan existing controllers/services for the actor before inventing paths. **Never introduce new response or layer patterns.**

Client contract bible: `api-docs/base.md`. Per-endpoint examples: `api-docs/frontend-docs/{actor}/{domain}.md`.

## 1. Versioning & routes (mandatory — product rule)

**APIs are versioned. This is not optional and not a “nice-to-have feature.”**  
All new actor endpoints ship under `/api/v{N}/…`. Breaking or incompatible updates get a **new version**; older versions stay until clients migrate.

```
routes/api.php
  → Route::group(['prefix' => 'v1'|'v2'|…], …)
    → require routes/guards/{version}/{actor}.php
```

| Rule | Do |
|---|---|
| New endpoint (compatible) | Add under **current** version (`routes/guards/v1/…` today) |
| Breaking change (shape/`key`/auth/path) | Add **`v2`** (or next) group + `routes/guards/v2/{actor}.php` — **keep `v1` working** |
| Deprecate | Mark in `api-docs/frontend-docs/…` — do **not** delete the old version in the same drop |
| Legacy unversioned `/api/user`, `/api/general` | Freeze / maintain only — **no new product features** there; port new work to `v{N}` |

```php
// routes/api.php — keep every live version mounted
Route::prefix('v1')->group(fn () => require …/guards/v1/…);
Route::prefix('v2')->group(fn () => require …/guards/v2/…); // when v2 exists

// ✗ Do not replace v1 in place for a breaking change
// ✗ Do not delete routes/guards/v1 when introducing v2
```

```php
// routes/guards/v1/{actor}.php
Route::group(['middleware' => ['guest:sanctum']], fn () => /* auth */);
Route::group(['middleware' => ['OptionalSanctumMiddleware']], fn () => /* public */);
Route::group(['middleware' => ['auth:{actor}', 'is_blocked']], fn () => /* protected */);
```

- Actor file owns that actor’s groups only (`user`, `provider`, …). Shared partials: `routes/Api/*.php`. Shared public may stay under `/api/general` until moved into `v{N}/general` — still document version in frontend-docs.
- Controllers: `app/Http/Controllers/Api/{Actor|Domain}/` (version-specific controller/namespace only if responses diverge).
- Token env var for docs: `{{actor_token}}`.
- Client docs path must include the versioned URL (`/api/v1/...`). See `branches-instructions.md` for git branches (separate from API versions).

## 2. Layer flow

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

## 3. Controllers — always `HandlesApiExceptions`

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

## 4. Response contract (non-negotiable)

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

Full tables + examples: **`api-docs/base.md`**.

## 5. Validation

- Extend `BaseApiRequest`.
- Add every input key to `lang/{locale}/validation.php` → `attributes` (translated labels, never raw `is_active` in messages).
- Prefer `__('apis.*')` for service/controller messages.

## 6. Form bootstrap (`form-keys`) — per screen only

**No global** `GET /api/v1/general/form-keys`. When a **specific** create/edit/filter screen needs lookups:

1. Add **`form-keys`** on that resource only (e.g. `GET /api/v1/user/hotels/{id}/form-keys`).
2. Return the **first 10** of **each** related collection for that screen — nested `data` + `has_more` + `load_more.path` (no `total_items` on nested keys). See `api-docs/base.md`.
3. Load-more via versioned list URLs, e.g. `GET /api/v1/general/countries/{country_id}/cities?page=2&per_page=10` (default **10**, max **100**).
4. Do **not** force the client to call many lookup APIs to open one form.

## 7. Capability `helpers` + client validation meta

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

## 8. Frontend docs (mandatory with every API change)

Update **`api-docs/frontend-docs/{actor}/{domain}.md`** in the **same task** as the endpoint:

| Actor docs | Examples |
|---|---|
| `frontend-docs/user/auth.md` | login, register, OTP, logout |
| `frontend-docs/user/profile.md` | profile, phone/email change |
| `frontend-docs/user/orders.md` | orders + helpers |
| `frontend-docs/general/lookups.md` | countries, cities |
| `frontend-docs/provider/…` | provider domains |

Each endpoint section must include: method, full path, headers, path/query/body fields, **all response `key` cases** with full JSON examples, `helpers` (incl. nested `*_validation`), pagination notes.

Also:

- Keep **`api-docs/base.md`** accurate if you change envelope/pagination/helpers conventions.
- Register new enums in **`api-docs/enums.md`**.
- Index / how-to: `api-docs/frontend-docs/README.md`.

## 9. Checklist before done

```
□ Route under routes/guards/{version}/{actor}.php — never delete older API versions for a breaking change
□ Controller uses HandlesApiExceptions + handleApi()
□ Service owns business logic; Resource uses whenLoaded()
□ HTTP 200 transport; clients use key (+ envelope code hint)
□ Form needing lookups → form-keys (first 10 each) + paginated list URLs
□ Show/action resources → helpers.can_* + helpers.*_validation where needed (not on data root)
□ Feature tests per api-testing.md + ./ci.sh --tests-only
□ Apidog synced after tests pass
□ api-docs/frontend-docs/{actor}/{domain}.md updated (all keys + examples)
□ api-docs/enums.md updated if new/changed enums
□ Memory: docs/memory/api/… (+ INDEX.md if new)
```

Order: implement → tests (`api-testing.md` + `./ci.sh`) → Apidog → **frontend-docs** → memory (`agent-memory-store.md`).
