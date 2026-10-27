# Feature: 01 Project Setup and Sanctum

## Summary
Laravel 12 API scaffolding, MySQL database connection, Laravel Sanctum token auth, and base API response architecture established.

## Components Configured
- **Database:** MySQL on port 3307, database `taskflow`.
- **Routing:** API prefix `/api/v1` in `bootstrap/app.php`.
- **Sanctum:** Package installed, migration executed, `HasApiTokens` added to `User` model, expiration set to 7 days.
- **Base Controller:** `ApiController` with `HandlesApiExceptions` and `ApiResponse` traits.
- **Provider:** `RepositoryServiceProvider` created and registered for MODE_A.
- **Exception Handling:** Unified JSON responses with HTTP 200 and structured `key` in `bootstrap/app.php`.

## Tests
- `tests/Feature/ApiBootTest.php` (health check, unauthenticated, 404 handler)
