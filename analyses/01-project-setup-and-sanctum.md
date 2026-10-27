# Phase 1: Project Setup and Sanctum Configuration

> **Spec Reference:** Section 3 (Technology Stack), Section 4 (Architecture), Section 13 (Phase 1 & Phase 2)  
> **Primary Goal:** Prepare the Laravel 12 application for API-first development with MySQL, install API routing, configure Laravel Sanctum, and define a consistent API response structure.

---

## High-Level Objective
Establish a clean, robust baseline for the TaskFlow API. This involves ensuring MySQL database credentials are configured, publishing API routes via Laravel 12's `install:api`, setting up Laravel Sanctum for bearer token authentication, and creating standard base controller responses.

---

## Detailed Task Breakdown

### Task 1.1: Environment & Database Configuration
- [ ] **Step 1:** Verify `.env` file exists and matches environment specifications (`APP_NAME=TaskFlow`, `APP_ENV=local`, `APP_URL=http://localhost:8000`).
- [ ] **Step 2:** Configure database parameters for MySQL in `.env`:
  ```ini
  DB_CONNECTION=mysql
  DB_HOST=127.0.0.1
  DB_PORT=3306
  DB_DATABASE=taskflow
  DB_USERNAME=root
  DB_PASSWORD=
  ```
- [ ] **Step 3:** Test database connection using `php artisan db:show` or a dry-run migration.
- [ ] **Step 4:** Ensure `APP_KEY` is generated via `php artisan key:generate`.
- [ ] **Step 5:** Set application timezone to UTC (`config/app.php`).

### Task 1.2: API Route Scaffolding in Laravel 12
- [ ] **Step 1:** Check if `routes/api.php` exists. If not, execute Laravel 12's API setup:
  ```bash
  php artisan install:api
  ```
- [ ] **Step 2:** Verify that `bootstrap/app.php` properly registers API routing and prefix:
  ```php
  ->withRouting(
      web: __DIR__.'/../routes/web.php',
      api: __DIR__.'/../routes/api.php',
      apiPrefix: 'api/v1',
      commands: __DIR__.'/../routes/console.php',
      health: '/up',
  )
  ```
- [ ] **Step 3:** Ensure route prefix is explicitly versioned as `/api/v1`.

### Task 1.3: Laravel Sanctum Installation & Setup
- [ ] **Step 1:** Verify `laravel/sanctum` is present in `composer.json` (or installed via `install:api`).
- [ ] **Step 2:** Check `config/sanctum.php` configuration:
  - Token expiration setting (e.g., `expiration => 60 * 24 * 7` for 7 days or null if indefinite).
- [ ] **Step 3:** Add `HasApiTokens` trait to `app/Models/User.php`.
- [ ] **Step 4:** Verify `personal_access_tokens` migration is staged and runs cleanly:
  ```bash
  php artisan migrate
  ```

### Task 1.4: Base API Controller & Consistent Response Helper
- [ ] **Step 1:** Create `app/Http/Controllers/Api/V1/ApiController.php` extending `App\Http\Controllers\Controller`.
- [ ] **Step 2:** Implement a standardized JSON response trait `app/Traits/ApiResponse.php`:
  - `successResponse($data, $message = null, $code = 200)`
  - `errorResponse($message, $code = 400, $errors = null)`
- [ ] **Step 3:** Register standard exception handling in `bootstrap/app.php` for:
  - `ModelNotFoundException` -> 404 with custom JSON message
  - `AuthenticationException` -> 401 with `"Unauthenticated."`
  - `AuthorizationException` -> 403 with `"Forbidden."`
  - `ValidationException` -> 422 with structured errors array.

### Task 1.5: Verification Smoke Test
- [ ] **Step 1:** Create a simple health check or version endpoint in `routes/api.php`:
  ```php
  Route::get('/health', function () {
      return response()->json([
          'status' => 'ok',
          'version' => 'v1',
          'timestamp' => now()->toIso8601String(),
      ]);
  });
  ```
- [ ] **Step 2:** Write a feature test `tests/Feature/ApiBootTest.php`:
  ```php
  public function test_api_health_endpoint_returns_success()
  {
      $response = $this->getJson('/api/v1/health');
      $response->assertStatus(200)
               ->assertJson(['status' => 'ok', 'version' => 'v1']);
  }
  ```
- [ ] **Step 3:** Run `php artisan test --filter=ApiBootTest` and confirm it passes.

---

## Acceptance Criteria
- [x] Application successfully boots on PHP 8.2+.
- [x] MySQL database connection is established and verified.
- [x] API routes are versioned at `/api/v1`.
- [x] Sanctum migrations are executed; `users` table is ready for API tokens.
- [x] `ApiBootTest` passes with HTTP 200.

