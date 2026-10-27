# API: V1 User Auth Flow

## Summary
Sanctum token-based authentication and profile management for users.

## Routes / entry
- `POST /api/v1/auth/register` → `AuthController@register` (guest)
- `POST /api/v1/auth/login` → `AuthController@login` (guest, throttled)
- `POST /api/v1/auth/logout` → `AuthController@logout` (auth:sanctum)
- `GET /api/v1/auth/me` → `AuthController@me` (auth:sanctum)
- `PATCH /api/v1/auth/me` → `AuthController@updateProfile` (auth:sanctum)
- `POST /api/v1/auth/forgot-password` → `AuthController@forgotPassword` (guest, throttled)
- `POST /api/v1/auth/reset-password` → `AuthController@resetPassword` (guest, throttled)

## Keys / response
- success → `key=success`, `data: { user: ..., token: ... }`
- fail → `key=fail` | `key=unauthenticated` | `key=needActive`

## Files
- Controller: `app/Http/Controllers/Api/V1/AuthController.php`
- Service: `app/Services/Auth/AuthService.php`
- Repository: `app/Repositories/Eloquent/UserRepository.php`
- Requests: `app/Http/Requests/Api/Auth/*`
- Tests: `tests/Feature/AuthTest.php`
