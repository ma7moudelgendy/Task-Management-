# Phase 11: Testing, Security Hardening, API Docs & Deployment

> **Spec Reference:** Section 12 (Validation & Errors), Section 14 (Testing Strategy), Section 15 (Security & Operations Checklist), Section 17 (Definition of Done)  
> **Primary Goal:** Consolidate the automated test suite, execute a security audit (IDOR, mass assignment, rate limits), document API endpoints via OpenAPI/Postman, and prepare production deployment manifests.

---

## High-Level Objective
Ensure TaskFlow meets enterprise production standards. Before considering the project ready for portfolio presentation or production deployment, every critical path must be backed by automated tests, security vulnerabilities must be eliminated, error messages must be sanitized, and deployment operations must be documented.

---

## Detailed Task Breakdown

### Task 11.1: Comprehensive Test Suite Audit
- [ ] **Step 1:** Verify the test suite covers all test categories outlined in Section 14:
  - **Feature Tests:**
    - `AuthTest`: Registration, login, logout, profile update, token revocation.
    - `TeamTest`: Team CRUD, membership roles, last-owner demotion protection.
    - `ProjectTest`: Project CRUD, membership scoping, team isolation.
    - `TaskTest`: Task CRUD, subtask parent validation, project-scoped assignee validation.
    - `TaskWorkflowTest`: State transitions, completion gates, status history audits.
    - `CommentTest`: Discussion permissions and manager moderation.
    - `AttachmentTest`: Private file uploads and authorized streaming downloads.
    - `NotificationTest`: User-scoped notification isolation and mark-as-read.
    - `RecurrenceTest`: Scheduled recurring task generation without duplicates.
    - `DashboardTest`: Scoped personal analytics and overdue calculations.
  - **Unit Tests:**
    - `CycleDetectionTest`: DAG cycle verification algorithms.
    - `ProgressCalculationTest`: Math formulas handling active vs cancelled tasks.
- [ ] **Step 2:** Run full suite from clean test database:
  ```bash
  php artisan test
  ```
- [ ] **Step 3:** Measure test coverage and ensure zero failing tests.

### Task 11.2: Security Hardening & IDOR Verification
- [ ] **Step 1: IDOR & Scope Leakage Audit:**
  - Verify that knowing an ID never bypasses authorization on:
    - `GET /api/v1/tasks/{task}`
    - `GET /api/v1/projects/{project}`
    - `GET /api/v1/tasks/{task}/attachments/{attachment}/download`
    - `GET /api/v1/notifications`
- [ ] **Step 2: Mass Assignment & Model Protection:**
  - Audit every Eloquent model to ensure `$fillable` is explicitly declared.
  - Never allow raw `$request->all()` into `Model::create()` without Form Request validation.
- [ ] **Step 3: Error Sanitization:**
  - In `bootstrap/app.php`, verify that when `APP_DEBUG=false`, internal SQL errors, PDO exceptions, or file paths are replaced with a generic JSON response:
    ```json
    {
      "message": "Server Error",
      "status": 500
    }
    ```
- [ ] **Step 4: Rate Limiting & Throttling:**
  - Ensure global API throttling is active: `throttle:api` (e.g. 60 requests/minute).
  - Ensure strict login throttling: 5 attempts per minute per IP.

### Task 11.3: API Documentation (OpenAPI / Postman)
- [ ] **Step 1:** Create an OpenAPI 3.0 specification file `docs/openapi.yaml` or Postman collection `docs/TaskFlow.postman_collection.json`.
- [ ] **Step 2:** Document all endpoints under `/api/v1`:
  - Request body schemas with field requirements and types.
  - Bearer token authentication header (`Authorization: Bearer <token>`).
  - Example `200`, `201`, `401`, `403`, `422`, and `404` responses.

### Task 11.4: Production Configuration & Operations
- [ ] **Step 1: Production `.env` Template:**
  - Create `.env.production.example` documenting mandatory production variables:
    ```ini
    APP_ENV=production
    APP_DEBUG=false
    APP_URL=https://api.taskflow.yourdomain.com
    DB_CONNECTION=mysql
    QUEUE_CONNECTION=database
    SESSION_DRIVER=database
    ```
- [ ] **Step 2: Queue Worker Daemon Configuration:**
  - Create Supervisor configuration file `docs/deployment/supervisor-taskflow-worker.conf`:
    ```ini
    [program:taskflow-worker]
    process_name=%(program_name)s_%(process_num)02d
    command=php /path-to-taskflow/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
    autostart=true
    autorestart=true
    user=www-data
    numprocs=2
    redirect_stderr=true
    stdout_logfile=/var/log/supervisor/taskflow-worker.log
    ```
- [ ] **Step 3: Linux Cron Job for Scheduler:**
  - Document crontab entry running Laravel Scheduler every minute:
    ```cron
    * * * * * cd /path-to-taskflow && php artisan schedule:run >> /dev/null 2>&1
    ```
- [ ] **Step 4: Database Optimization:**
  - Run `php artisan config:cache`, `php artisan route:cache`, `php artisan view:cache` during deployment pipeline.

### Task 11.5: Final Verification Checklist
- [ ] **Step 1:** Run `php artisan test` and verify 100% pass rate.
- [ ] **Step 2:** Run `php artisan route:list --path=api/v1` to verify all route URIs, HTTP verbs, middleware, and controller bindings.
- [ ] **Step 3:** Commit working state to Git with clean commit message.

---

## Acceptance Criteria
- [x] Complete automated test suite covering all features, authorization checks, and edge cases.
- [x] Zero IDOR vulnerabilities or unauthenticated endpoints.
- [x] Full OpenAPI specification / Postman collection available for API consumers.
- [x] Supervisor and Cron deployment guides ready for production deployment.
- [x] Complete satisfaction of Definition of Done (Section 17).

