# Decision: OpenAPI Generation & Apidog Synchronization Workflow

## Context
- TaskFlow API uses Laravel 12 on PHP 8.2 with an architectural HTTP 200 response envelope pattern (`HandlesApiExceptions`, `jsonResponse()` with structured `key`, `code`, `response_status`).
- A repeatable, zero-conflict mechanism was needed to produce OpenAPI 3.1.0 specifications and synchronize with Apidog without risking framework compatibility breaks.

## Decision
- Implemented native Artisan command `php artisan openapi:generate` (`app/Console/Commands/GenerateOpenApiSpec.php`).
- Output files: `docs/openapi.json` (repository root) and `laravel/docs/openapi.json` (mirror).
- Generates OpenAPI 3.1.0 with Sanctum Bearer token security schemes, response envelope schemas, and realistic examples.
- Implemented Apidog sync command `php artisan apidog:sync` (`app/Console/Commands/SyncApidogSpec.php`) with `--dry-run` and explicit `--force` flags.
- Provided cross-platform wrapper scripts: `scripts/sync-apidog.ps1` and `scripts/sync-apidog.sh`.
- Added automated feature tests in `tests/Feature/OpenApiSpecTest.php` ensuring all registered API routes are documented and valid.
- Decided on: 2026-10-27.

