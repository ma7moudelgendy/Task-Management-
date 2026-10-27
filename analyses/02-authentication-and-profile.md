# Phase 2: Authentication & User Profile Module

> **Spec Reference:** Section 5.1 (`users`), Section 7 (API Conventions), Section 8.1 (Auth & Profile Endpoints), Section 13 (Phase 2)  
> **Primary Goal:** Implement secure Sanctum token-based authentication, user profile management, password resets, login rate-limiting, and account status checks.

---

## High-Level Objective
Build the entry point for all API users. Users must be able to register, authenticate (receiving a bearer token), invalidate tokens upon logout, inspect/update their profile, and securely reset forgotten passwords. Inactive accounts must be barred from authenticating.

---

## Detailed Task Breakdown

### Task 2.1: User Model & Database Enhancements
- [ ] **Step 1:** Review `database/migrations/0001_01_01_000000_create_users_table.php`. Add missing fields:
  - `is_active` (boolean, default true).
  - `role` (enum or string, default `USER`, can be `ADMIN`).
- [ ] **Step 2:** Ensure password column stores bcrypt/argon2 hashes, never plaintext.
- [ ] **Step 3:** Update `app/Models/User.php`:
  - Fillable: `['name', 'email', 'password', 'is_active', 'role']`.
  - Hidden: `['password', 'remember_token']`.
  - Casts: `['email_verified_at' => 'datetime', 'password' => 'hashed', 'is_active' => 'boolean']`.
  - Add query scope `scopeActive($query)`.

### Task 2.2: Form Requests & Validation Rules
- [ ] **Step 1:** Create `app/Http/Requests/Auth/RegisterRequest.php`:
  - `name`: `['required', 'string', 'max:255']`
  - `email`: `['required', 'string', 'email', 'max:255', 'unique:users,email']`
  - `password`: `['required', 'string', Password::min(8)->letters()->numbers()->mixedCase()]`
  - `password_confirmation`: `['required', 'same:password']`
- [ ] **Step 2:** Create `app/Http/Requests/Auth/LoginRequest.php`:
  - `email`: `['required', 'email']`
  - `password`: `['required', 'string']`
  - `device_name`: `['nullable', 'string', 'max:100']`
- [ ] **Step 3:** Create `app/Http/Requests/Auth/UpdateProfileRequest.php`:
  - `name`: `['sometimes', 'string', 'max:255']`
  - `email`: `['sometimes', 'email', 'max:255', Rule::unique('users')->ignore($this->user()->id)]`
  - `current_password`: `['required_with:password', 'current_password']`
  - `password`: `['sometimes', 'string', Password::min(8)->letters()->numbers()]`

### Task 2.3: API Resources
- [ ] **Step 1:** Create `app/Http/Resources/UserResource.php`:
  - Expose: `id`, `name`, `email`, `role`, `is_active`, `created_at`, `updated_at`.
  - Explicitly omit: `password`, `remember_token`, tokens.

### Task 2.4: Auth Controller Implementation
- [ ] **Step 1:** Create `app/Http/Controllers/Api/V1/AuthController.php`.
- [ ] **Step 2:** Implement `register(RegisterRequest $request)`:
  - Create user, issue default Sanctum token: `$user->createToken('auth-token')->plainTextToken`.
  - Return `201 Created` with token and `UserResource`.
- [ ] **Step 3:** Implement `login(LoginRequest $request)`:
  - Apply rate limiting (5 attempts per minute per IP/email).
  - Verify credentials with `Hash::check()`.
  - Verify `$user->is_active === true`; return `403 Forbidden` if suspended/deactivated.
  - Revoke old tokens if single-session is enforced, or issue new named token.
  - Return `200 OK` with token and `UserResource`.
- [ ] **Step 4:** Implement `logout(Request $request)`:
  - Revoke current token: `$request->user()->currentAccessToken()->delete()`.
  - Return `200 OK` with confirmation message.
- [ ] **Step 5:** Implement `me(Request $request)`:
  - Return `UserResource` for `$request->user()`.
- [ ] **Step 6:** Implement `updateProfile(UpdateProfileRequest $request)`:
  - Update verified attributes and return fresh `UserResource`.

### Task 2.5: Password Reset Flow
- [ ] **Step 1:** Create `app/Http/Requests/Auth/ForgotPasswordRequest.php` and `ResetPasswordRequest.php`.
- [ ] **Step 2:** Implement `forgotPassword(ForgotPasswordRequest $request)`:
  - Use `Password::sendResetLink()` or custom secure token generation.
- [ ] **Step 3:** Implement `resetPassword(ResetPasswordRequest $request)`:
  - Validate token, hash new password, revoke existing tokens to enforce re-login.

### Task 2.6: Route Registration & Middleware
- [ ] **Step 1:** In `routes/api.php`, register auth routes under `prefix('auth')`:
  - Public: `/register`, `/login`, `/forgot-password`, `/reset-password` (apply `throttle:6,1` on login/password routes).
  - Protected (`middleware('auth:sanctum')`): `/logout`, `/me`, `PATCH /me`.

### Task 2.7: Automated Feature Tests
- [ ] **Step 1:** Create `tests/Feature/AuthTest.php`.
- [ ] **Step 2:** Test scenarios:
  - Successful registration returns 201 and token.
  - Duplicate email registration fails with 422.
  - Successful login returns 200 and token.
  - Invalid password returns 422 or 401.
  - Inactive user cannot log in (returns 403).
  - Unauthenticated access to `/auth/me` returns 401.
  - Authenticated user can fetch their profile via `/auth/me`.
  - Logout revokes token (subsequent request returns 401).
- [ ] **Step 3:** Run `php artisan test --filter=AuthTest`.

---

## Acceptance Criteria
- [x] All 7 authentication endpoints implemented under `/api/v1/auth/`.
- [x] Login rate limiting blocks brute-force attempts after 5 failures.
- [x] Passwords are securely hashed with modern bcrypt algorithms.
- [x] Token revocation properly prevents replay attacks upon logout.
- [x] 100% of auth feature tests execute and pass.

