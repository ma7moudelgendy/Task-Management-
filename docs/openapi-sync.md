# Laravel ↔ Apidog OpenAPI Synchronization Guide

This guide describes the automated, repeatable workflow to synchronize the TaskFlow Laravel API with Apidog using OpenAPI 3.1.0 specifications.

---

## 1. Overview & Architecture

- **Project:** TaskFlow Backend API
- **Framework:** Laravel 12 (PHP 8.2+)
- **Specification:** OpenAPI 3.1.0
- **Primary Spec Path:** `docs/openapi.json`
- **Mirror Spec Path:** `laravel/docs/openapi.json`
- **Authentication:** Laravel Sanctum (`auth:sanctum`), Bearer token (`BearerAuth`)
- **Transport Standard:** All business responses use HTTP 200 with structured JSON envelopes:
  ```json
  {
    "key": "success",
    "code": 200,
    "msg": "Operation successful.",
    "data": {},
    "response_status": {
      "error": false,
      "validation_errors": {}
    }
  }
  ```

---

## 2. Command Reference

### A. Generate OpenAPI Specification
To generate or regenerate the OpenAPI 3.1.0 specification from the current codebase:

```bash
# From within the laravel directory:
php artisan openapi:generate

# With optional custom output path:
php artisan openapi:generate --output=../docs/custom-spec.json
```

### B. Dry-Run Synchronization Preview
To inspect endpoints, methods, tags, and authentication without modifying Apidog:

```bash
# Using Artisan:
php artisan apidog:sync --dry-run

# Or using the PowerShell script:
.\scripts\sync-apidog.ps1

# Or using the Bash script:
./scripts/sync-apidog.sh
```

### C. Live Apidog Synchronization (Requires Approval & Credentials)
To programmatically push the specification to your Apidog project:

```bash
# 1. Set environment variables (never commit or hardcode secrets)
export APIDOG_PROJECT_ID="your_project_id"
export APIDOG_ACCESS_TOKEN="your_personal_access_token"

# On Windows PowerShell:
$env:APIDOG_PROJECT_ID="your_project_id"
$env:APIDOG_ACCESS_TOKEN="your_personal_access_token"

# 2. Run sync with explicit confirmation:
php artisan apidog:sync --force

# Or specify overwrite strategy:
php artisan apidog:sync --force --overwrite
```

---

## 3. Endpoints Documented

| Method | Endpoint | Summary | Tags | Security |
|---|---|---|---|---|
| `GET` | `/api/v1/health` | System health check | General | Public |
| `POST` | `/api/v1/auth/register` | Register new user | Authentication | Public |
| `POST` | `/api/v1/auth/login` | User login | Authentication | Public |
| `POST` | `/api/v1/auth/logout` | Revoke session token | Authentication | `BearerAuth` (Sanctum) |
| `GET` | `/api/v1/auth/me` | Get profile | User Profile | `BearerAuth` (Sanctum) |
| `PATCH` | `/api/v1/auth/me` | Update profile | User Profile | `BearerAuth` (Sanctum) |
| `POST` | `/api/v1/auth/forgot-password` | Send reset link | Authentication | Public |
| `POST` | `/api/v1/auth/reset-password` | Reset password | Authentication | Public |
| `GET` | `/api/v1/user` | Get raw user | User Profile | `BearerAuth` (Sanctum) |
| `GET` | `/api/v1/teams` | List user teams | Teams | `BearerAuth` (Sanctum) |
| `POST` | `/api/v1/teams` | Create a new team | Teams | `BearerAuth` (Sanctum) |
| `GET` | `/api/v1/teams/{team}` | Get team details | Teams | `BearerAuth` (Sanctum) |
| `PATCH` | `/api/v1/teams/{team}` | Update team settings | Teams | `BearerAuth` (Sanctum) |
| `DELETE` | `/api/v1/teams/{team}` | Soft delete team | Teams | `BearerAuth` (Sanctum) |
| `GET` | `/api/v1/teams/{team}/members` | List team members | Team Members | `BearerAuth` (Sanctum) |
| `POST` | `/api/v1/teams/{team}/members` | Add member to team | Team Members | `BearerAuth` (Sanctum) |
| `PATCH` | `/api/v1/teams/{team}/members/{user}` | Update member role | Team Members | `BearerAuth` (Sanctum) |
| `DELETE` | `/api/v1/teams/{team}/members/{user}` | Remove member from team | Team Members | `BearerAuth` (Sanctum) |

---

## 4. Conflict Resolution & Merge Strategy

When pushing to Apidog via `POST https://api.apidog.com/v1/projects/{projectId}/import-openapi?locale=en-US`:
- **Default Strategy (`merge`):** Conflicting endpoints update their request schemas and parameters while preserving existing mocks, documentation annotations, and test cases previously set inside Apidog.
- **Overwrite Strategy (`--overwrite`):** Overwrites conflicting endpoint definitions with the fresh OpenAPI schema from Laravel. Existing endpoints not in the specification are retained unless explicitly purged in the Apidog console.

---

## 5. Manual Import Workflow (No Credentials Needed)

If an Apidog Personal Access Token is not configured:

1. Open **[Apidog](https://apidog.com)** and log in to your workspace.
2. Select your **TaskFlow** project.
3. In the left navigation, click the **`+`** icon next to the API search bar, or navigate to **Settings** ➔ **Import Data**.
4. Select **OpenAPI / Swagger** format.
5. Upload or drag-and-drop `docs/openapi.json` from the repository root.
6. Apidog parses the 8 paths and 9 operations, mapping all schemas, enums, parameters, and authentication rules.
7. Choose your merge preference (**Merge** or **Overwrite**) and click **Confirm**.

---

## 6. Automated Testing & Quality Gate

Run the automated specification test suite at any time:

```bash
cd laravel
php artisan test --filter=OpenApiSpecTest
```

Tests verify:
- Specification file existence and validity.
- OpenAPI 3.1.0 versioning.
- 100% route coverage between Laravel router and OpenAPI specification.
- `BearerAuth` security enforcement on protected routes.
- Standard response envelope compliance.
- Dry-run sync execution without errors.

