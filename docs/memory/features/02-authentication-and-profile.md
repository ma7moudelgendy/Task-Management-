# Feature: 02 Authentication & Profile

## Summary
Complete user authentication lifecycle via Sanctum tokens with registration, login with brute-force rate-limiting, logout token revocation, profile fetch/update, and secure password reset.

## Layer Structure (MODE_A)
- Controller: `AuthController` (handles exceptions via `handleApi`)
- FormRequests & DTOs: `RegisterDTO`, `LoginDTO`, `UpdateProfileDTO`, `ForgotPasswordDTO`, `ResetPasswordDTO`
- Service: `AuthService`
- Repository: `UserRepositoryInterface` / `UserRepository`
- Resource: `UserResource`

## Tests
- `tests/Feature/AuthTest.php` (10 tests, 46 assertions passing)

