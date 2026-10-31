<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateOpenApiSpec extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'openapi:generate {--output= : Optional custom path to output OpenAPI JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate OpenAPI 3.1.0 specification for the TaskFlow Laravel API';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Generating OpenAPI 3.1.0 specification for TaskFlow...');

        $spec = $this->buildSpecification();

        $primaryPath = base_path('../docs/openapi.json');
        $fallbackPath = base_path('docs/openapi.json');

        $outputPath = $this->option('output') ?: $primaryPath;

        $targetDir = dirname($outputPath);
        if (! File::isDirectory($targetDir)) {
            File::makeDirectory($targetDir, 0755, true);
        }

        $jsonOutput = json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        File::put($outputPath, $jsonOutput);
        $this->info("✓ OpenAPI specification written to: {$outputPath}");

        // Also ensure fallback path exists in laravel/docs for self-contained portability
        if ($outputPath === $primaryPath) {
            $fallbackDir = dirname($fallbackPath);
            if (! File::isDirectory($fallbackDir)) {
                File::makeDirectory($fallbackDir, 0755, true);
            }
            File::put($fallbackPath, $jsonOutput);
            $this->info("✓ Mirror copy written to: {$fallbackPath}");
        }

        $endpointCount = count($spec['paths']);
        $this->info("✓ Successfully generated OpenAPI documentation for {$endpointCount} API paths.");

        return Command::SUCCESS;
    }

    /**
     * Build the full OpenAPI 3.1.0 specification array.
     */
    protected function buildSpecification(): array
    {
        return [
            'openapi' => '3.1.0',
            'info' => [
                'title' => 'TaskFlow API',
                'description' => 'Advanced Task Management API built with Laravel 12, MySQL, and Laravel Sanctum authentication. Enforces standard HTTP 200 response envelope with business outcome routing via the body "key".',
                'version' => '1.0.0',
                'contact' => [
                    'name' => 'TaskFlow Development Team',
                    'email' => 'dev@taskflow.local',
                ],
            ],
            'servers' => [
                [
                    'url' => rtrim(config('app.url', 'http://localhost:8000'), '/').'/api/v1',
                    'description' => 'Current API Environment (v1)',
                ],
            ],
            'tags' => [
                [
                    'name' => 'General',
                    'description' => 'System health, heartbeat, and diagnostics.',
                ],
                [
                    'name' => 'Authentication',
                    'description' => 'Registration, login, password recovery, and token management.',
                ],
                [
                    'name' => 'User Profile',
                    'description' => 'Authenticated user profile retrieval and updates.',
                ],
                [
                    'name' => 'Teams',
                    'description' => 'Team workspace management, unique slugs, settings, and lifecycle.',
                ],
                [
                    'name' => 'Team Members',
                    'description' => 'Team member invitations, role management, and owner retention safeguards.',
                ],
                [
                    'name' => 'Projects',
                    'description' => 'Project workspace lifecycle, team-scoping, date ranges, and status.',
                ],
                [
                    'name' => 'Project Members',
                    'description' => 'Project membership enrollments, scoped roles (OWNER, MANAGER, MEMBER, VIEWER), and last-owner safeguards.',
                ],
                [
                    'name' => 'Tasks',
                    'description' => 'Task management, subtasks hierarchy, project assignees, status lifecycle, priorities, and filters.',
                ],
                [
                    'name' => 'Comments',
                    'description' => 'Task discussion comments, author-exclusive edits, and manager moderation.',
                ],
                [
                    'name' => 'Attachments',
                    'description' => 'Multi-type validated task file attachments on private disk storage and authorized download streaming.',
                ],
            ],
            'paths' => $this->buildPaths(),
            'components' => $this->buildComponents(),
        ];
    }

    /**
     * Build OpenAPI paths for registered API endpoints.
     */
    protected function buildPaths(): array
    {
        return [
            '/health' => [
                'get' => [
                    'tags' => ['General'],
                    'summary' => 'System health check',
                    'description' => 'Returns runtime health status, API version, and timestamp to verify API availability.',
                    'operationId' => 'healthCheck',
                    'responses' => [
                        '200' => [
                            'description' => 'API is operational',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/HealthCheckResponse',
                                    ],
                                    'example' => [
                                        'key' => 'success',
                                        'status' => 'ok',
                                        'version' => 'v1',
                                        'timestamp' => '2026-10-27T21:40:00+00:00',
                                        'response_status' => [
                                            'error' => false,
                                            'validation_errors' => new \stdClass,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'x-postman-test' => 'pm.test("Status code is 200", function () { pm.response.to.have.status(200); }); pm.test("Service is healthy", function () { var json = pm.response.json(); pm.expect(json.key).to.eql("success"); pm.expect(json.status).to.eql("ok"); });',
                ],
            ],
            '/auth/register' => [
                'post' => [
                    'tags' => ['Authentication'],
                    'summary' => 'Register a new user',
                    'description' => 'Creates a new user account, stores credentials securely, and issues a Laravel Sanctum personal access token.',
                    'operationId' => 'authRegister',
                    'requestBody' => [
                        'required' => true,
                        'description' => 'User registration details',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/RegisterRequest',
                                ],
                                'example' => [
                                    'name' => 'Jane Doe',
                                    'email' => 'jane.doe@example.com',
                                    'password' => 'SecurePass123!',
                                    'password_confirmation' => 'SecurePass123!',
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Registration outcome wrapped in HTTP 200 envelope',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/AuthSuccessResponse',
                                    ],
                                    'examples' => [
                                        'success' => [
                                            'summary' => 'Successful registration (key: success, code: 201)',
                                            'value' => [
                                                'key' => 'success',
                                                'code' => 201,
                                                'msg' => 'Registration successful.',
                                                'data' => [
                                                    'user' => [
                                                        'id' => 1,
                                                        'name' => 'Jane Doe',
                                                        'email' => 'jane.doe@example.com',
                                                        'role' => 'member',
                                                        'is_active' => true,
                                                        'created_at' => '2026-10-27T21:40:00+00:00',
                                                        'updated_at' => '2026-10-27T21:40:00+00:00',
                                                    ],
                                                    'token' => '1|1a2b3c4d5e6f7g8h9i0j',
                                                ],
                                                'response_status' => [
                                                    'error' => false,
                                                    'validation_errors' => new \stdClass,
                                                ],
                                            ],
                                        ],
                                        'validation_error' => [
                                            'summary' => 'Validation failure (key: fail, code: 422)',
                                            'value' => [
                                                'key' => 'fail',
                                                'code' => 422,
                                                'msg' => 'The email has already been taken.',
                                                'data' => null,
                                                'response_status' => [
                                                    'error' => true,
                                                    'validation_errors' => [
                                                        'email' => ['The email has already been taken.'],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'x-postman-test' => 'pm.test("Status code is 200", function () { pm.response.to.have.status(200); }); var json = pm.response.json(); pm.test("Response envelope format", function () { pm.expect(json).to.have.property("key"); pm.expect(json).to.have.property("response_status"); }); if (json.key === "success") { pm.environment.set("auth_token", json.data.token); }',
                ],
            ],
            '/auth/login' => [
                'post' => [
                    'tags' => ['Authentication'],
                    'summary' => 'User login',
                    'description' => 'Authenticates user credentials and issues a Sanctum Bearer token. Rate limited to 6 requests per minute.',
                    'operationId' => 'authLogin',
                    'requestBody' => [
                        'required' => true,
                        'description' => 'User login credentials',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/LoginRequest',
                                ],
                                'example' => [
                                    'email' => 'jane.doe@example.com',
                                    'password' => 'SecurePass123!',
                                    'device_name' => 'Developer Workstation',
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Authentication result wrapped in HTTP 200 envelope',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/AuthSuccessResponse',
                                    ],
                                    'examples' => [
                                        'success' => [
                                            'summary' => 'Successful login (key: success, code: 200)',
                                            'value' => [
                                                'key' => 'success',
                                                'code' => 200,
                                                'msg' => 'Login successful.',
                                                'data' => [
                                                    'user' => [
                                                        'id' => 1,
                                                        'name' => 'Jane Doe',
                                                        'email' => 'jane.doe@example.com',
                                                        'role' => 'member',
                                                        'is_active' => true,
                                                        'created_at' => '2026-10-27T21:40:00+00:00',
                                                        'updated_at' => '2026-10-27T21:40:00+00:00',
                                                    ],
                                                    'token' => '2|2b3c4d5e6f7g8h9i0j1k',
                                                ],
                                                'response_status' => [
                                                    'error' => false,
                                                    'validation_errors' => new \stdClass,
                                                ],
                                            ],
                                        ],
                                        'invalid_credentials' => [
                                            'summary' => 'Invalid credentials (key: fail, code: 401)',
                                            'value' => [
                                                'key' => 'fail',
                                                'code' => 401,
                                                'msg' => 'Invalid email or password.',
                                                'data' => null,
                                                'response_status' => [
                                                    'error' => true,
                                                    'validation_errors' => new \stdClass,
                                                ],
                                            ],
                                        ],
                                        'inactive_account' => [
                                            'summary' => 'Account inactive (key: needActive, code: 403)',
                                            'value' => [
                                                'key' => 'needActive',
                                                'code' => 403,
                                                'msg' => 'Account is inactive. Please contact support.',
                                                'data' => null,
                                                'response_status' => [
                                                    'error' => true,
                                                    'validation_errors' => new \stdClass,
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'x-postman-test' => 'pm.test("Status code is 200", function () { pm.response.to.have.status(200); }); var json = pm.response.json(); if (json.key === "success") { pm.environment.set("auth_token", json.data.token); }',
                ],
            ],
            '/auth/logout' => [
                'post' => [
                    'tags' => ['Authentication'],
                    'summary' => 'Revoke current session token',
                    'description' => 'Deletes the Sanctum access token used for the current request.',
                    'operationId' => 'authLogout',
                    'security' => [
                        ['BearerAuth' => []],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Logout outcome',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/StandardEnvelopeResponse',
                                    ],
                                    'example' => [
                                        'key' => 'success',
                                        'code' => 200,
                                        'msg' => 'Logged out successfully.',
                                        'data' => null,
                                        'response_status' => [
                                            'error' => false,
                                            'validation_errors' => new \stdClass,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'x-postman-test' => 'pm.test("Status code is 200", function () { pm.response.to.have.status(200); }); var json = pm.response.json(); pm.test("Token revoked", function () { pm.expect(json.key).to.eql("success"); });',
                ],
            ],
            '/auth/me' => [
                'get' => [
                    'tags' => ['User Profile'],
                    'summary' => 'Get current authenticated user profile',
                    'description' => 'Returns profile information of the user associated with the provided Sanctum Bearer token.',
                    'operationId' => 'authGetProfile',
                    'security' => [
                        ['BearerAuth' => []],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'User profile retrieved successfully',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/UserProfileResponse',
                                    ],
                                    'examples' => [
                                        'success' => [
                                            'summary' => 'Profile retrieved (key: success, code: 200)',
                                            'value' => [
                                                'key' => 'success',
                                                'code' => 200,
                                                'msg' => 'Profile retrieved.',
                                                'data' => [
                                                    'user' => [
                                                        'id' => 1,
                                                        'name' => 'Jane Doe',
                                                        'email' => 'jane.doe@example.com',
                                                        'role' => 'member',
                                                        'is_active' => true,
                                                        'created_at' => '2026-10-27T21:40:00+00:00',
                                                        'updated_at' => '2026-10-27T21:40:00+00:00',
                                                    ],
                                                ],
                                                'response_status' => [
                                                    'error' => false,
                                                    'validation_errors' => new \stdClass,
                                                ],
                                            ],
                                        ],
                                        'unauthenticated' => [
                                            'summary' => 'Unauthenticated request (key: unauthenticated, code: 401)',
                                            'value' => [
                                                'key' => 'unauthenticated',
                                                'code' => 401,
                                                'msg' => 'Unauthenticated.',
                                                'data' => null,
                                                'response_status' => [
                                                    'error' => true,
                                                    'validation_errors' => new \stdClass,
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'x-postman-test' => 'pm.test("Status code is 200", function () { pm.response.to.have.status(200); }); var json = pm.response.json(); pm.test("User data returned", function () { pm.expect(json.data.user).to.have.property("email"); });',
                ],
                'patch' => [
                    'tags' => ['User Profile'],
                    'summary' => 'Update authenticated user profile',
                    'description' => 'Updates profile details (name, email) and optionally changes the account password (requires current_password).',
                    'operationId' => 'authUpdateProfile',
                    'security' => [
                        ['BearerAuth' => []],
                    ],
                    'requestBody' => [
                        'required' => true,
                        'description' => 'Profile update fields',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/UpdateProfileRequest',
                                ],
                                'example' => [
                                    'name' => 'Jane Smith',
                                    'email' => 'jane.smith@example.com',
                                    'current_password' => 'SecurePass123!',
                                    'password' => 'BrandNewPass456!',
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Profile update result',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/UserProfileResponse',
                                    ],
                                    'examples' => [
                                        'success' => [
                                            'summary' => 'Profile updated successfully (key: success, code: 200)',
                                            'value' => [
                                                'key' => 'success',
                                                'code' => 200,
                                                'msg' => 'Profile updated successfully.',
                                                'data' => [
                                                    'user' => [
                                                        'id' => 1,
                                                        'name' => 'Jane Smith',
                                                        'email' => 'jane.smith@example.com',
                                                        'role' => 'member',
                                                        'is_active' => true,
                                                        'created_at' => '2026-10-27T21:40:00+00:00',
                                                        'updated_at' => '2026-10-27T21:45:00+00:00',
                                                    ],
                                                ],
                                                'response_status' => [
                                                    'error' => false,
                                                    'validation_errors' => new \stdClass,
                                                ],
                                            ],
                                        ],
                                        'wrong_password' => [
                                            'summary' => 'Current password incorrect (key: fail, code: 422)',
                                            'value' => [
                                                'key' => 'fail',
                                                'code' => 422,
                                                'msg' => 'Current password does not match.',
                                                'data' => null,
                                                'response_status' => [
                                                    'error' => true,
                                                    'validation_errors' => new \stdClass,
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'x-postman-test' => 'pm.test("Status code is 200", function () { pm.response.to.have.status(200); }); var json = pm.response.json(); pm.test("Update status", function () { pm.expect(json).to.have.property("key"); });',
                ],
            ],
            '/auth/forgot-password' => [
                'post' => [
                    'tags' => ['Authentication'],
                    'summary' => 'Send password reset link',
                    'description' => 'Sends a password reset link to the given email address. Rate limited to 6 requests per minute.',
                    'operationId' => 'authForgotPassword',
                    'requestBody' => [
                        'required' => true,
                        'description' => 'Email address to receive the password reset token',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/ForgotPasswordRequest',
                                ],
                                'example' => [
                                    'email' => 'jane.doe@example.com',
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Reset link status',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/StandardEnvelopeResponse',
                                    ],
                                    'example' => [
                                        'key' => 'success',
                                        'code' => 200,
                                        'msg' => 'Password reset link sent to your email.',
                                        'data' => null,
                                        'response_status' => [
                                            'error' => false,
                                            'validation_errors' => new \stdClass,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'x-postman-test' => 'pm.test("Status code is 200", function () { pm.response.to.have.status(200); });',
                ],
            ],
            '/auth/reset-password' => [
                'post' => [
                    'tags' => ['Authentication'],
                    'summary' => 'Reset user password',
                    'description' => 'Resets user password using the token issued by the forgot-password flow. Rate limited to 6 requests per minute.',
                    'operationId' => 'authResetPassword',
                    'requestBody' => [
                        'required' => true,
                        'description' => 'Password reset credentials with token',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/ResetPasswordRequest',
                                ],
                                'example' => [
                                    'email' => 'jane.doe@example.com',
                                    'token' => 'a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6',
                                    'password' => 'BrandNewPassword123!',
                                    'password_confirmation' => 'BrandNewPassword123!',
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Password reset outcome',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/StandardEnvelopeResponse',
                                    ],
                                    'examples' => [
                                        'success' => [
                                            'summary' => 'Password reset successfully (key: success, code: 200)',
                                            'value' => [
                                                'key' => 'success',
                                                'code' => 200,
                                                'msg' => 'Password has been reset successfully.',
                                                'data' => null,
                                                'response_status' => [
                                                    'error' => false,
                                                    'validation_errors' => new \stdClass,
                                                ],
                                            ],
                                        ],
                                        'invalid_token' => [
                                            'summary' => 'Invalid or expired token (key: fail, code: 422)',
                                            'value' => [
                                                'key' => 'fail',
                                                'code' => 422,
                                                'msg' => 'This password reset token is invalid.',
                                                'data' => null,
                                                'response_status' => [
                                                    'error' => true,
                                                    'validation_errors' => new \stdClass,
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'x-postman-test' => 'pm.test("Status code is 200", function () { pm.response.to.have.status(200); });',
                ],
            ],
            '/user' => [
                'get' => [
                    'tags' => ['User Profile'],
                    'summary' => 'Get user model',
                    'description' => 'Default Sanctum authenticated user endpoint returning the raw User entity.',
                    'operationId' => 'getUserRaw',
                    'security' => [
                        ['BearerAuth' => []],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Raw User model object',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/UserRaw',
                                    ],
                                    'example' => [
                                        'id' => 1,
                                        'name' => 'Jane Doe',
                                        'email' => 'jane.doe@example.com',
                                        'email_verified_at' => null,
                                        'role' => 'member',
                                        'is_active' => 1,
                                        'created_at' => '2026-10-27T21:40:00.000000Z',
                                        'updated_at' => '2026-10-27T21:40:00.000000Z',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'x-postman-test' => 'pm.test("Status code is 200", function () { pm.response.to.have.status(200); });',
                ],
            ],
            '/teams' => [
                'get' => [
                    'tags' => ['Teams'],
                    'summary' => 'List user teams',
                    'description' => 'Returns paginated teams where the authenticated user is a member.',
                    'operationId' => 'teamsIndex',
                    'security' => [
                        ['BearerAuth' => []],
                    ],
                    'parameters' => [
                        [
                            'name' => 'per_page',
                            'in' => 'query',
                            'description' => 'Number of teams per page (default 15)',
                            'required' => false,
                            'schema' => ['type' => 'integer', 'default' => 15],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Paginated team list',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/StandardEnvelopeResponse',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'post' => [
                    'tags' => ['Teams'],
                    'summary' => 'Create a new team',
                    'description' => 'Creates a new collaborative team workspace. Creator automatically becomes the initial OWNER.',
                    'operationId' => 'teamsStore',
                    'security' => [
                        ['BearerAuth' => []],
                    ],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/StoreTeamRequest',
                                ],
                                'example' => [
                                    'name' => 'Alpha Engineering',
                                    'slug' => 'alpha-engineering',
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Team successfully created',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/StandardEnvelopeResponse',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            '/teams/{team}' => [
                'get' => [
                    'tags' => ['Teams'],
                    'summary' => 'Get team details',
                    'description' => 'Returns details and member count for a specific team. User must be a member or admin.',
                    'operationId' => 'teamsShow',
                    'security' => [
                        ['BearerAuth' => []],
                    ],
                    'parameters' => [
                        [
                            'name' => 'team',
                            'in' => 'path',
                            'required' => true,
                            'description' => 'Team ID',
                            'schema' => ['type' => 'integer'],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Team details',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/StandardEnvelopeResponse',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'patch' => [
                    'tags' => ['Teams'],
                    'summary' => 'Update team settings',
                    'description' => 'Updates team name or slug. User must be an OWNER or MANAGER.',
                    'operationId' => 'teamsUpdate',
                    'security' => [
                        ['BearerAuth' => []],
                    ],
                    'parameters' => [
                        [
                            'name' => 'team',
                            'in' => 'path',
                            'required' => true,
                            'description' => 'Team ID',
                            'schema' => ['type' => 'integer'],
                        ],
                    ],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/UpdateTeamRequest',
                                ],
                                'example' => [
                                    'name' => 'Alpha Core Engineering',
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Updated team details',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/StandardEnvelopeResponse',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'delete' => [
                    'tags' => ['Teams'],
                    'summary' => 'Soft delete team',
                    'description' => 'Soft-deletes the team workspace. Only team OWNER can delete.',
                    'operationId' => 'teamsDestroy',
                    'security' => [
                        ['BearerAuth' => []],
                    ],
                    'parameters' => [
                        [
                            'name' => 'team',
                            'in' => 'path',
                            'required' => true,
                            'description' => 'Team ID',
                            'schema' => ['type' => 'integer'],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Team deleted successfully',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/StandardEnvelopeResponse',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            '/teams/{team}/members' => [
                'get' => [
                    'tags' => ['Team Members'],
                    'summary' => 'List team members',
                    'description' => 'Returns list of all members and their roles in the team.',
                    'operationId' => 'teamMembersIndex',
                    'security' => [
                        ['BearerAuth' => []],
                    ],
                    'parameters' => [
                        [
                            'name' => 'team',
                            'in' => 'path',
                            'required' => true,
                            'description' => 'Team ID',
                            'schema' => ['type' => 'integer'],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Team members list',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/StandardEnvelopeResponse',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'post' => [
                    'tags' => ['Team Members'],
                    'summary' => 'Add member to team',
                    'description' => 'Invites/adds a registered user to the team by email with assigned role (OWNER, MANAGER, MEMBER). Caller must be OWNER or MANAGER.',
                    'operationId' => 'teamMembersStore',
                    'security' => [
                        ['BearerAuth' => []],
                    ],
                    'parameters' => [
                        [
                            'name' => 'team',
                            'in' => 'path',
                            'required' => true,
                            'description' => 'Team ID',
                            'schema' => ['type' => 'integer'],
                        ],
                    ],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/AddTeamMemberRequest',
                                ],
                                'example' => [
                                    'email' => 'member@example.com',
                                    'role' => 'MEMBER',
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Member added successfully',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/StandardEnvelopeResponse',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            '/teams/{team}/members/{user}' => [
                'patch' => [
                    'tags' => ['Team Members'],
                    'summary' => 'Update member role',
                    'description' => 'Changes the role of a team member. Only team OWNER can change roles. Team must retain at least one OWNER.',
                    'operationId' => 'teamMembersUpdateRole',
                    'security' => [
                        ['BearerAuth' => []],
                    ],
                    'parameters' => [
                        [
                            'name' => 'team',
                            'in' => 'path',
                            'required' => true,
                            'description' => 'Team ID',
                            'schema' => ['type' => 'integer'],
                        ],
                        [
                            'name' => 'user',
                            'in' => 'path',
                            'required' => true,
                            'description' => 'User ID',
                            'schema' => ['type' => 'integer'],
                        ],
                    ],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/UpdateTeamMemberRoleRequest',
                                ],
                                'example' => [
                                    'role' => 'MANAGER',
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Member role updated',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/StandardEnvelopeResponse',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'delete' => [
                    'tags' => ['Team Members'],
                    'summary' => 'Remove member from team',
                    'description' => 'Detaches a user from the team. Caller must be OWNER or MANAGER. Team must retain at least one OWNER.',
                    'operationId' => 'teamMembersDestroy',
                    'security' => [
                        ['BearerAuth' => []],
                    ],
                    'parameters' => [
                        [
                            'name' => 'team',
                            'in' => 'path',
                            'required' => true,
                            'description' => 'Team ID',
                            'schema' => ['type' => 'integer'],
                        ],
                        [
                            'name' => 'user',
                            'in' => 'path',
                            'required' => true,
                            'description' => 'User ID',
                            'schema' => ['type' => 'integer'],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Member removed successfully',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/StandardEnvelopeResponse',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            '/projects' => [
                'get' => [
                    'tags' => ['Projects'],
                    'summary' => 'List enrolled projects',
                    'description' => 'Returns paginated list of projects where the authenticated user is an enrolled member.',
                    'operationId' => 'projectsIndex',
                    'security' => [
                        ['BearerAuth' => []],
                    ],
                    'parameters' => [
                        [
                            'name' => 'team_id',
                            'in' => 'query',
                            'description' => 'Filter projects by parent team ID',
                            'required' => false,
                            'schema' => ['type' => 'integer'],
                        ],
                        [
                            'name' => 'status',
                            'in' => 'query',
                            'description' => 'Filter projects by status (ACTIVE, ON_HOLD, COMPLETED, ARCHIVED)',
                            'required' => false,
                            'schema' => ['type' => 'string', 'enum' => ['ACTIVE', 'ON_HOLD', 'COMPLETED', 'ARCHIVED']],
                        ],
                        [
                            'name' => 'per_page',
                            'in' => 'query',
                            'description' => 'Projects per page (default 15)',
                            'required' => false,
                            'schema' => ['type' => 'integer', 'default' => 15],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Paginated project list',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/StandardEnvelopeResponse',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'post' => [
                    'tags' => ['Projects'],
                    'summary' => 'Create a new project',
                    'description' => 'Creates a new project scoped under a team. Caller must be an OWNER or MANAGER of the parent team. Creator automatically becomes project OWNER.',
                    'operationId' => 'projectsStore',
                    'security' => [
                        ['BearerAuth' => []],
                    ],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/StoreProjectRequest',
                                ],
                                'example' => [
                                    'team_id' => 1,
                                    'name' => 'Project Phoenix',
                                    'description' => 'Core infrastructure revamp',
                                    'status' => 'ACTIVE',
                                    'start_date' => '2026-11-01',
                                    'due_date' => '2026-12-31',
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Project successfully created',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/StandardEnvelopeResponse',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            '/projects/{project}' => [
                'get' => [
                    'tags' => ['Projects'],
                    'summary' => 'Get project details',
                    'description' => 'Returns project metadata, counts, and action permissions. Caller must be an enrolled project member or system ADMIN.',
                    'operationId' => 'projectsShow',
                    'security' => [
                        ['BearerAuth' => []],
                    ],
                    'parameters' => [
                        [
                            'name' => 'project',
                            'in' => 'path',
                            'required' => true,
                            'description' => 'Project ID',
                            'schema' => ['type' => 'integer'],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Project details',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/StandardEnvelopeResponse',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'patch' => [
                    'tags' => ['Projects'],
                    'summary' => 'Update project settings',
                    'description' => 'Updates project name, description, status, or date ranges. Caller must be project OWNER, MANAGER, or system ADMIN.',
                    'operationId' => 'projectsUpdate',
                    'security' => [
                        ['BearerAuth' => []],
                    ],
                    'parameters' => [
                        [
                            'name' => 'project',
                            'in' => 'path',
                            'required' => true,
                            'description' => 'Project ID',
                            'schema' => ['type' => 'integer'],
                        ],
                    ],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/UpdateProjectRequest',
                                ],
                                'example' => [
                                    'name' => 'Project Phoenix 2.0',
                                    'status' => 'ON_HOLD',
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Project updated successfully',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/StandardEnvelopeResponse',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'delete' => [
                    'tags' => ['Projects'],
                    'summary' => 'Soft delete project',
                    'description' => 'Soft deletes the project. Only project OWNER or system ADMIN can delete.',
                    'operationId' => 'projectsDestroy',
                    'security' => [
                        ['BearerAuth' => []],
                    ],
                    'parameters' => [
                        [
                            'name' => 'project',
                            'in' => 'path',
                            'required' => true,
                            'description' => 'Project ID',
                            'schema' => ['type' => 'integer'],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Project deleted successfully',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/StandardEnvelopeResponse',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            '/projects/{project}/members' => [
                'get' => [
                    'tags' => ['Project Members'],
                    'summary' => 'List project members',
                    'description' => 'Returns list of all enrolled members and their roles in the project.',
                    'operationId' => 'projectMembersIndex',
                    'security' => [
                        ['BearerAuth' => []],
                    ],
                    'parameters' => [
                        [
                            'name' => 'project',
                            'in' => 'path',
                            'required' => true,
                            'description' => 'Project ID',
                            'schema' => ['type' => 'integer'],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Project members list',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/StandardEnvelopeResponse',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'post' => [
                    'tags' => ['Project Members'],
                    'summary' => 'Add member to project',
                    'description' => 'Enrolls an active member of the parent team into the project with a scoped role. Caller must be project OWNER, MANAGER, or system ADMIN.',
                    'operationId' => 'projectMembersStore',
                    'security' => [
                        ['BearerAuth' => []],
                    ],
                    'parameters' => [
                        [
                            'name' => 'project',
                            'in' => 'path',
                            'required' => true,
                            'description' => 'Project ID',
                            'schema' => ['type' => 'integer'],
                        ],
                    ],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/AddProjectMemberRequest',
                                ],
                                'example' => [
                                    'user_id' => 2,
                                    'role' => 'MEMBER',
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Member enrolled successfully',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/StandardEnvelopeResponse',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            '/projects/{project}/members/{user}' => [
                'patch' => [
                    'tags' => ['Project Members'],
                    'summary' => 'Update project member role',
                    'description' => 'Updates the role of a project member. Only project OWNER or system ADMIN can change roles. Project must retain at least one OWNER.',
                    'operationId' => 'projectMembersUpdateRole',
                    'security' => [
                        ['BearerAuth' => []],
                    ],
                    'parameters' => [
                        [
                            'name' => 'project',
                            'in' => 'path',
                            'required' => true,
                            'description' => 'Project ID',
                            'schema' => ['type' => 'integer'],
                        ],
                        [
                            'name' => 'user',
                            'in' => 'path',
                            'required' => true,
                            'description' => 'User ID',
                            'schema' => ['type' => 'integer'],
                        ],
                    ],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/UpdateProjectMemberRoleRequest',
                                ],
                                'example' => [
                                    'role' => 'MANAGER',
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Member role updated successfully',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/StandardEnvelopeResponse',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'delete' => [
                    'tags' => ['Project Members'],
                    'summary' => 'Remove member from project',
                    'description' => 'Revokes project access for a user while preserving task assignment history. Caller must be project OWNER, MANAGER, or system ADMIN. Project must retain at least one OWNER.',
                    'operationId' => 'projectMembersDestroy',
                    'security' => [
                        ['BearerAuth' => []],
                    ],
                    'parameters' => [
                        [
                            'name' => 'project',
                            'in' => 'path',
                            'required' => true,
                            'description' => 'Project ID',
                            'schema' => ['type' => 'integer'],
                        ],
                        [
                            'name' => 'user',
                            'in' => 'path',
                            'required' => true,
                            'description' => 'User ID',
                            'schema' => ['type' => 'integer'],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Member removed successfully',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/StandardEnvelopeResponse',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            '/tasks' => [
                'get' => [
                    'tags' => ['Tasks'],
                    'summary' => 'List tasks across enrolled projects',
                    'description' => 'Retrieve paginated tasks accessible to the authenticated user with optional status, priority, assignee, and date range filters.',
                    'security' => [['BearerAuth' => []]],
                    'parameters' => [
                        [
                            'name' => 'status',
                            'in' => 'query',
                            'required' => false,
                            'schema' => ['type' => 'string', 'enum' => ['TODO', 'IN_PROGRESS', 'IN_REVIEW', 'DONE', 'CANCELLED']],
                        ],
                        [
                            'name' => 'priority',
                            'in' => 'query',
                            'required' => false,
                            'schema' => ['type' => 'string', 'enum' => ['LOW', 'MEDIUM', 'HIGH', 'URGENT']],
                        ],
                        [
                            'name' => 'assignee_id',
                            'in' => 'query',
                            'required' => false,
                            'schema' => ['type' => 'integer'],
                        ],
                        [
                            'name' => 'due_before',
                            'in' => 'query',
                            'required' => false,
                            'schema' => ['type' => 'string', 'format' => 'date-time'],
                        ],
                        [
                            'name' => 'due_after',
                            'in' => 'query',
                            'required' => false,
                            'schema' => ['type' => 'string', 'format' => 'date-time'],
                        ],
                        [
                            'name' => 'sort_by',
                            'in' => 'query',
                            'required' => false,
                            'schema' => ['type' => 'string', 'enum' => ['due_at', 'priority', 'created_at', 'position', 'title']],
                        ],
                        [
                            'name' => 'sort_dir',
                            'in' => 'query',
                            'required' => false,
                            'schema' => ['type' => 'string', 'enum' => ['asc', 'desc']],
                        ],
                        [
                            'name' => 'per_page',
                            'in' => 'query',
                            'required' => false,
                            'schema' => ['type' => 'integer', 'default' => 15, 'minimum' => 1, 'maximum' => 100],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Tasks retrieved successfully',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/TaskListResponse',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            '/projects/{project}/tasks' => [
                'get' => [
                    'tags' => ['Tasks'],
                    'summary' => 'List tasks in project',
                    'description' => 'Retrieve paginated tasks within a specific project.',
                    'security' => [['BearerAuth' => []]],
                    'parameters' => [
                        [
                            'name' => 'project',
                            'in' => 'path',
                            'required' => true,
                            'description' => 'Project ID',
                            'schema' => ['type' => 'integer'],
                        ],
                        [
                            'name' => 'status',
                            'in' => 'query',
                            'required' => false,
                            'schema' => ['type' => 'string', 'enum' => ['TODO', 'IN_PROGRESS', 'IN_REVIEW', 'DONE', 'CANCELLED']],
                        ],
                        [
                            'name' => 'priority',
                            'in' => 'query',
                            'required' => false,
                            'schema' => ['type' => 'string', 'enum' => ['LOW', 'MEDIUM', 'HIGH', 'URGENT']],
                        ],
                        [
                            'name' => 'assignee_id',
                            'in' => 'query',
                            'required' => false,
                            'schema' => ['type' => 'integer'],
                        ],
                        [
                            'name' => 'per_page',
                            'in' => 'query',
                            'required' => false,
                            'schema' => ['type' => 'integer', 'default' => 15],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Project tasks retrieved successfully',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/TaskListResponse',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'post' => [
                    'tags' => ['Tasks'],
                    'summary' => 'Create a new task in project',
                    'description' => 'Creates a task or subtask within a project. Assignees must be active project members.',
                    'security' => [['BearerAuth' => []]],
                    'parameters' => [
                        [
                            'name' => 'project',
                            'in' => 'path',
                            'required' => true,
                            'description' => 'Project ID',
                            'schema' => ['type' => 'integer'],
                        ],
                    ],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/StoreTaskRequest',
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Task created successfully',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/TaskResponse',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            '/tasks/{task}' => [
                'get' => [
                    'tags' => ['Tasks'],
                    'summary' => 'Get task details',
                    'description' => 'Retrieve complete task information including assignees, project, and subtasks.',
                    'security' => [['BearerAuth' => []]],
                    'parameters' => [
                        [
                            'name' => 'task',
                            'in' => 'path',
                            'required' => true,
                            'description' => 'Task ID',
                            'schema' => ['type' => 'integer'],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Task details retrieved successfully',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/TaskResponse',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'patch' => [
                    'tags' => ['Tasks'],
                    'summary' => 'Update task',
                    'description' => 'Update task attributes, status, priority, or dates.',
                    'security' => [['BearerAuth' => []]],
                    'parameters' => [
                        [
                            'name' => 'task',
                            'in' => 'path',
                            'required' => true,
                            'description' => 'Task ID',
                            'schema' => ['type' => 'integer'],
                        ],
                    ],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/UpdateTaskRequest',
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Task updated successfully',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/TaskResponse',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'delete' => [
                    'tags' => ['Tasks'],
                    'summary' => 'Delete task',
                    'description' => 'Soft delete a task.',
                    'security' => [['BearerAuth' => []]],
                    'parameters' => [
                        [
                            'name' => 'task',
                            'in' => 'path',
                            'required' => true,
                            'description' => 'Task ID',
                            'schema' => ['type' => 'integer'],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Task deleted successfully',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/StandardEnvelopeResponse',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            '/tasks/{task}/assignees' => [
                'put' => [
                    'tags' => ['Tasks'],
                    'summary' => 'Sync task assignees',
                    'description' => 'Atomically replace the full list of assignees for the task. All assignees must be project members.',
                    'security' => [['BearerAuth' => []]],
                    'parameters' => [
                        [
                            'name' => 'task',
                            'in' => 'path',
                            'required' => true,
                            'description' => 'Task ID',
                            'schema' => ['type' => 'integer'],
                        ],
                    ],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/SyncAssigneesRequest',
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Task assignees synchronized successfully',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/TaskResponse',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            '/tasks/{task}/status' => [
                'post' => [
                    'tags' => ['Tasks'],
                    'summary' => 'Transition task status',
                    'description' => 'Advance or reopen task status subject to state machine transition rules and dependency completion gates.',
                    'security' => [['BearerAuth' => []]],
                    'parameters' => [
                        [
                            'name' => 'task',
                            'in' => 'path',
                            'required' => true,
                            'description' => 'Task ID',
                            'schema' => ['type' => 'integer'],
                        ],
                    ],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/ChangeTaskStatusRequest',
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Task status updated successfully',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/TaskResponse',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            '/tasks/{task}/dependencies' => [
                'get' => [
                    'tags' => ['Tasks'],
                    'summary' => 'Get task dependencies',
                    'description' => 'List all prerequisite dependencies (tasks this task depends on) and dependents (tasks waiting on this task).',
                    'security' => [['BearerAuth' => []]],
                    'parameters' => [
                        [
                            'name' => 'task',
                            'in' => 'path',
                            'required' => true,
                            'description' => 'Task ID',
                            'schema' => ['type' => 'integer'],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Task dependencies retrieved successfully',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/TaskDependenciesResponse',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'post' => [
                    'tags' => ['Tasks'],
                    'summary' => 'Add prerequisite dependency',
                    'description' => 'Declare that this task depends on another task in the same project. Blocks any additions that would cause cycles.',
                    'security' => [['BearerAuth' => []]],
                    'parameters' => [
                        [
                            'name' => 'task',
                            'in' => 'path',
                            'required' => true,
                            'description' => 'Task ID',
                            'schema' => ['type' => 'integer'],
                        ],
                    ],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/AddDependencyRequest',
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Task dependency added successfully',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/StandardEnvelopeResponse',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            '/tasks/{task}/dependencies/{dependency}' => [
                'delete' => [
                    'tags' => ['Tasks'],
                    'summary' => 'Remove prerequisite dependency',
                    'description' => 'Remove a prerequisite dependency edge from the task.',
                    'security' => [['BearerAuth' => []]],
                    'parameters' => [
                        [
                            'name' => 'task',
                            'in' => 'path',
                            'required' => true,
                            'description' => 'Task ID',
                            'schema' => ['type' => 'integer'],
                        ],
                        [
                            'name' => 'dependency',
                            'in' => 'path',
                            'required' => true,
                            'description' => 'Dependency Task ID',
                            'schema' => ['type' => 'integer'],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Task dependency removed successfully',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/StandardEnvelopeResponse',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            '/tasks/{task}/history' => [
                'get' => [
                    'tags' => ['Tasks'],
                    'summary' => 'Get task status audit history',
                    'description' => 'Retrieve paginated status change audit log for the task.',
                    'security' => [['BearerAuth' => []]],
                    'parameters' => [
                        [
                            'name' => 'task',
                            'in' => 'path',
                            'required' => true,
                            'description' => 'Task ID',
                            'schema' => ['type' => 'integer'],
                        ],
                        [
                            'name' => 'per_page',
                            'in' => 'query',
                            'required' => false,
                            'schema' => ['type' => 'integer', 'default' => 15],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Task status history retrieved successfully',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/TaskStatusHistoryListResponse',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            '/tasks/{task}/comments' => [
                'get' => [
                    'tags' => ['Comments'],
                    'summary' => 'List task comments',
                    'description' => 'Retrieve paginated comments for the task.',
                    'security' => [['BearerAuth' => []]],
                    'parameters' => [
                        [
                            'name' => 'task',
                            'in' => 'path',
                            'required' => true,
                            'description' => 'Task ID',
                            'schema' => ['type' => 'integer'],
                        ],
                        [
                            'name' => 'per_page',
                            'in' => 'query',
                            'required' => false,
                            'schema' => ['type' => 'integer', 'default' => 15],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Comments retrieved successfully',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/CommentListResponse',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'post' => [
                    'tags' => ['Comments'],
                    'summary' => 'Create task comment',
                    'description' => 'Add a new discussion comment to the task.',
                    'security' => [['BearerAuth' => []]],
                    'parameters' => [
                        [
                            'name' => 'task',
                            'in' => 'path',
                            'required' => true,
                            'description' => 'Task ID',
                            'schema' => ['type' => 'integer'],
                        ],
                    ],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/StoreCommentRequest',
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Comment created successfully',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/CommentDetailResponse',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            '/comments/{comment}' => [
                'patch' => [
                    'tags' => ['Comments'],
                    'summary' => 'Update comment',
                    'description' => 'Update body of an existing comment (author only).',
                    'security' => [['BearerAuth' => []]],
                    'parameters' => [
                        [
                            'name' => 'comment',
                            'in' => 'path',
                            'required' => true,
                            'description' => 'Comment ID',
                            'schema' => ['type' => 'integer'],
                        ],
                    ],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/UpdateCommentRequest',
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Comment updated successfully',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/CommentDetailResponse',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'delete' => [
                    'tags' => ['Comments'],
                    'summary' => 'Delete comment',
                    'description' => 'Soft delete a comment (author or project manager/owner).',
                    'security' => [['BearerAuth' => []]],
                    'parameters' => [
                        [
                            'name' => 'comment',
                            'in' => 'path',
                            'required' => true,
                            'description' => 'Comment ID',
                            'schema' => ['type' => 'integer'],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Comment deleted successfully',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/StandardEnvelopeResponse',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            '/tasks/{task}/attachments' => [
                'get' => [
                    'tags' => ['Attachments'],
                    'summary' => 'List task attachments',
                    'description' => 'Retrieve paginated file attachments on the task.',
                    'security' => [['BearerAuth' => []]],
                    'parameters' => [
                        [
                            'name' => 'task',
                            'in' => 'path',
                            'required' => true,
                            'description' => 'Task ID',
                            'schema' => ['type' => 'integer'],
                        ],
                        [
                            'name' => 'per_page',
                            'in' => 'query',
                            'required' => false,
                            'schema' => ['type' => 'integer', 'default' => 15],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Attachments retrieved successfully',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/AttachmentListResponse',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'post' => [
                    'tags' => ['Attachments'],
                    'summary' => 'Upload file attachment',
                    'description' => 'Upload a validated file (max 10MB) to private storage disk.',
                    'security' => [['BearerAuth' => []]],
                    'parameters' => [
                        [
                            'name' => 'task',
                            'in' => 'path',
                            'required' => true,
                            'description' => 'Task ID',
                            'schema' => ['type' => 'integer'],
                        ],
                    ],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'multipart/form-data' => [
                                'schema' => [
                                    'type' => 'object',
                                    'required' => ['file'],
                                    'properties' => [
                                        'file' => [
                                            'type' => 'string',
                                            'format' => 'binary',
                                            'description' => 'File to upload (pdf, doc, docx, xls, xlsx, png, jpg, jpeg, zip, txt; max 10MB)',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Attachment uploaded successfully',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/AttachmentDetailResponse',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            '/tasks/{task}/attachments/{attachment}/download' => [
                'get' => [
                    'tags' => ['Attachments'],
                    'summary' => 'Download attachment file',
                    'description' => 'Authorized binary streaming download with IDOR protection.',
                    'security' => [['BearerAuth' => []]],
                    'parameters' => [
                        [
                            'name' => 'task',
                            'in' => 'path',
                            'required' => true,
                            'description' => 'Task ID',
                            'schema' => ['type' => 'integer'],
                        ],
                        [
                            'name' => 'attachment',
                            'in' => 'path',
                            'required' => true,
                            'description' => 'Attachment ID',
                            'schema' => ['type' => 'integer'],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Binary file stream',
                            'content' => [
                                'application/octet-stream' => [
                                    'schema' => [
                                        'type' => 'string',
                                        'format' => 'binary',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            '/tasks/{task}/attachments/{attachment}' => [
                'delete' => [
                    'tags' => ['Attachments'],
                    'summary' => 'Delete file attachment',
                    'description' => 'Delete file attachment and remove file from private disk.',
                    'security' => [['BearerAuth' => []]],
                    'parameters' => [
                        [
                            'name' => 'task',
                            'in' => 'path',
                            'required' => true,
                            'description' => 'Task ID',
                            'schema' => ['type' => 'integer'],
                        ],
                        [
                            'name' => 'attachment',
                            'in' => 'path',
                            'required' => true,
                            'description' => 'Attachment ID',
                            'schema' => ['type' => 'integer'],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Attachment deleted successfully',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/StandardEnvelopeResponse',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Build reusable OpenAPI components and schemas.
     */
    protected function buildComponents(): array
    {
        return [
            'securitySchemes' => [
                'BearerAuth' => [
                    'type' => 'http',
                    'scheme' => 'bearer',
                    'bearerFormat' => 'Sanctum',
                    'description' => 'Enter your Laravel Sanctum plain text token in the format "Bearer <token>"',
                ],
            ],
            'schemas' => [
                'HealthCheckResponse' => [
                    'type' => 'object',
                    'required' => ['key', 'status', 'version', 'timestamp', 'response_status'],
                    'properties' => [
                        'key' => [
                            'type' => 'string',
                            'example' => 'success',
                        ],
                        'status' => [
                            'type' => 'string',
                            'example' => 'ok',
                        ],
                        'version' => [
                            'type' => 'string',
                            'example' => 'v1',
                        ],
                        'timestamp' => [
                            'type' => 'string',
                            'format' => 'date-time',
                            'example' => '2026-10-27T21:40:00+00:00',
                        ],
                        'response_status' => [
                            '$ref' => '#/components/schemas/ResponseStatus',
                        ],
                    ],
                ],
                'ResponseStatus' => [
                    'type' => 'object',
                    'required' => ['error', 'validation_errors'],
                    'properties' => [
                        'error' => [
                            'type' => 'boolean',
                            'description' => 'Indicates whether the request resulted in a business or validation error.',
                            'example' => false,
                        ],
                        'validation_errors' => [
                            'type' => 'object',
                            'description' => 'Key-value map of field validation error messages.',
                            'additionalProperties' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'string',
                                ],
                            ],
                        ],
                    ],
                ],
                'StandardEnvelopeResponse' => [
                    'type' => 'object',
                    'required' => ['key', 'code', 'msg', 'data', 'response_status'],
                    'properties' => [
                        'key' => [
                            'type' => 'string',
                            'description' => 'Outcome key for client flow branching.',
                            'enum' => ['success', 'fail', 'unauthenticated', 'unauthorized', 'not_found', 'blocked', 'needActive', 'exception'],
                            'example' => 'success',
                        ],
                        'code' => [
                            'type' => 'integer',
                            'description' => 'Envelope status code hint.',
                            'example' => 200,
                        ],
                        'msg' => [
                            'type' => 'string',
                            'description' => 'Human-readable message.',
                            'example' => 'Operation successful.',
                        ],
                        'data' => [
                            'nullable' => true,
                            'description' => 'Response data payload or null.',
                        ],
                        'response_status' => [
                            '$ref' => '#/components/schemas/ResponseStatus',
                        ],
                    ],
                ],
                'UserResource' => [
                    'type' => 'object',
                    'required' => ['id', 'name', 'email', 'role', 'is_active', 'created_at', 'updated_at'],
                    'properties' => [
                        'id' => [
                            'type' => 'integer',
                            'example' => 1,
                        ],
                        'name' => [
                            'type' => 'string',
                            'example' => 'Jane Doe',
                        ],
                        'email' => [
                            'type' => 'string',
                            'format' => 'email',
                            'example' => 'jane.doe@example.com',
                        ],
                        'role' => [
                            'type' => 'string',
                            'enum' => ['admin', 'manager', 'member', 'guest'],
                            'example' => 'member',
                        ],
                        'is_active' => [
                            'type' => 'boolean',
                            'example' => true,
                        ],
                        'created_at' => [
                            'type' => 'string',
                            'format' => 'date-time',
                            'example' => '2026-10-27T21:40:00+00:00',
                        ],
                        'updated_at' => [
                            'type' => 'string',
                            'format' => 'date-time',
                            'example' => '2026-10-27T21:40:00+00:00',
                        ],
                    ],
                ],
                'UserRaw' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'example' => 1],
                        'name' => ['type' => 'string', 'example' => 'Jane Doe'],
                        'email' => ['type' => 'string', 'format' => 'email', 'example' => 'jane.doe@example.com'],
                        'email_verified_at' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true, 'example' => null],
                        'role' => ['type' => 'string', 'example' => 'member'],
                        'is_active' => ['type' => 'integer', 'example' => 1],
                        'created_at' => ['type' => 'string', 'format' => 'date-time', 'example' => '2026-10-27T21:40:00.000000Z'],
                        'updated_at' => ['type' => 'string', 'format' => 'date-time', 'example' => '2026-10-27T21:40:00.000000Z'],
                    ],
                ],
                'AuthSuccessResponse' => [
                    'type' => 'object',
                    'required' => ['key', 'code', 'msg', 'data', 'response_status'],
                    'properties' => [
                        'key' => [
                            'type' => 'string',
                            'example' => 'success',
                        ],
                        'code' => [
                            'type' => 'integer',
                            'example' => 200,
                        ],
                        'msg' => [
                            'type' => 'string',
                            'example' => 'Operation successful.',
                        ],
                        'data' => [
                            'type' => 'object',
                            'required' => ['user', 'token'],
                            'properties' => [
                                'user' => [
                                    '$ref' => '#/components/schemas/UserResource',
                                ],
                                'token' => [
                                    'type' => 'string',
                                    'description' => 'Sanctum Bearer access token',
                                    'example' => '1|1a2b3c4d5e6f7g8h9i0j',
                                ],
                            ],
                        ],
                        'response_status' => [
                            '$ref' => '#/components/schemas/ResponseStatus',
                        ],
                    ],
                ],
                'UserProfileResponse' => [
                    'type' => 'object',
                    'required' => ['key', 'code', 'msg', 'data', 'response_status'],
                    'properties' => [
                        'key' => [
                            'type' => 'string',
                            'example' => 'success',
                        ],
                        'code' => [
                            'type' => 'integer',
                            'example' => 200,
                        ],
                        'msg' => [
                            'type' => 'string',
                            'example' => 'Profile retrieved.',
                        ],
                        'data' => [
                            'type' => 'object',
                            'required' => ['user'],
                            'properties' => [
                                'user' => [
                                    '$ref' => '#/components/schemas/UserResource',
                                ],
                            ],
                        ],
                        'response_status' => [
                            '$ref' => '#/components/schemas/ResponseStatus',
                        ],
                    ],
                ],
                'RegisterRequest' => [
                    'type' => 'object',
                    'required' => ['name', 'email', 'password', 'password_confirmation'],
                    'properties' => [
                        'name' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'User full name',
                            'example' => 'Jane Doe',
                        ],
                        'email' => [
                            'type' => 'string',
                            'format' => 'email',
                            'maxLength' => 255,
                            'description' => 'Unique email address',
                            'example' => 'jane.doe@example.com',
                        ],
                        'password' => [
                            'type' => 'string',
                            'format' => 'password',
                            'minLength' => 8,
                            'description' => 'Account password (minimum 8 characters, letters and numbers, mixed case)',
                            'example' => 'SecurePass123!',
                        ],
                        'password_confirmation' => [
                            'type' => 'string',
                            'format' => 'password',
                            'description' => 'Must match password field exactly',
                            'example' => 'SecurePass123!',
                        ],
                    ],
                ],
                'LoginRequest' => [
                    'type' => 'object',
                    'required' => ['email', 'password'],
                    'properties' => [
                        'email' => [
                            'type' => 'string',
                            'format' => 'email',
                            'description' => 'Account email address',
                            'example' => 'jane.doe@example.com',
                        ],
                        'password' => [
                            'type' => 'string',
                            'format' => 'password',
                            'description' => 'Account password',
                            'example' => 'SecurePass123!',
                        ],
                        'device_name' => [
                            'type' => 'string',
                            'maxLength' => 100,
                            'nullable' => true,
                            'description' => 'Optional client device descriptor for token management',
                            'example' => 'Developer Workstation',
                        ],
                    ],
                ],
                'ForgotPasswordRequest' => [
                    'type' => 'object',
                    'required' => ['email'],
                    'properties' => [
                        'email' => [
                            'type' => 'string',
                            'format' => 'email',
                            'description' => 'User account email address to send the password reset link to',
                            'example' => 'jane.doe@example.com',
                        ],
                    ],
                ],
                'ResetPasswordRequest' => [
                    'type' => 'object',
                    'required' => ['email', 'token', 'password', 'password_confirmation'],
                    'properties' => [
                        'email' => [
                            'type' => 'string',
                            'format' => 'email',
                            'description' => 'User account email address',
                            'example' => 'jane.doe@example.com',
                        ],
                        'token' => [
                            'type' => 'string',
                            'description' => 'Password reset token received via forgot-password email',
                            'example' => 'a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6',
                        ],
                        'password' => [
                            'type' => 'string',
                            'format' => 'password',
                            'minLength' => 8,
                            'description' => 'New password (minimum 8 characters with letters and numbers)',
                            'example' => 'BrandNewPassword123!',
                        ],
                        'password_confirmation' => [
                            'type' => 'string',
                            'format' => 'password',
                            'description' => 'Password confirmation matching new password',
                            'example' => 'BrandNewPassword123!',
                        ],
                    ],
                ],
                'UpdateProfileRequest' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => [
                            'type' => 'string',
                            'maxLength' => 255,
                            'description' => 'Updated user full name',
                            'example' => 'Jane Smith',
                        ],
                        'email' => [
                            'type' => 'string',
                            'format' => 'email',
                            'maxLength' => 255,
                            'description' => 'Updated unique email address',
                            'example' => 'jane.smith@example.com',
                        ],
                        'current_password' => [
                            'type' => 'string',
                            'format' => 'password',
                            'description' => 'Current password (required if updating password)',
                            'example' => 'SecurePass123!',
                        ],
                        'password' => [
                            'type' => 'string',
                            'format' => 'password',
                            'minLength' => 8,
                            'description' => 'New password (minimum 8 characters with letters and numbers)',
                            'example' => 'BrandNewPass456!',
                        ],
                    ],
                ],
                'StoreTeamRequest' => [
                    'type' => 'object',
                    'required' => ['name'],
                    'properties' => [
                        'name' => [
                            'type' => 'string',
                            'maxLength' => 100,
                            'description' => 'Team workspace name',
                            'example' => 'Alpha Engineering',
                        ],
                        'slug' => [
                            'type' => 'string',
                            'maxLength' => 100,
                            'description' => 'Custom unique URL slug (auto-generated from name if omitted)',
                            'example' => 'alpha-engineering',
                        ],
                    ],
                ],
                'UpdateTeamRequest' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => [
                            'type' => 'string',
                            'maxLength' => 100,
                            'description' => 'Updated team workspace name',
                            'example' => 'Alpha Core Engineering',
                        ],
                        'slug' => [
                            'type' => 'string',
                            'maxLength' => 100,
                            'description' => 'Updated unique URL slug',
                            'example' => 'alpha-core-engineering',
                        ],
                    ],
                ],
                'AddTeamMemberRequest' => [
                    'type' => 'object',
                    'required' => ['email', 'role'],
                    'properties' => [
                        'email' => [
                            'type' => 'string',
                            'format' => 'email',
                            'description' => 'Registered user email address',
                            'example' => 'colleague@example.com',
                        ],
                        'role' => [
                            'type' => 'string',
                            'enum' => ['OWNER', 'MANAGER', 'MEMBER'],
                            'description' => 'Assigned role within the team',
                            'example' => 'MEMBER',
                        ],
                    ],
                ],
                'UpdateTeamMemberRoleRequest' => [
                    'type' => 'object',
                    'required' => ['role'],
                    'properties' => [
                        'role' => [
                            'type' => 'string',
                            'enum' => ['OWNER', 'MANAGER', 'MEMBER'],
                            'description' => 'New role within the team',
                            'example' => 'MANAGER',
                        ],
                    ],
                ],
                'StoreProjectRequest' => [
                    'type' => 'object',
                    'required' => ['team_id', 'name'],
                    'properties' => [
                        'team_id' => [
                            'type' => 'integer',
                            'description' => 'Parent team ID',
                            'example' => 1,
                        ],
                        'name' => [
                            'type' => 'string',
                            'maxLength' => 150,
                            'description' => 'Project workspace name',
                            'example' => 'Project Phoenix',
                        ],
                        'description' => [
                            'type' => 'string',
                            'description' => 'Detailed project scope or summary',
                            'example' => 'Core infrastructure revamp',
                        ],
                        'status' => [
                            'type' => 'string',
                            'enum' => ['ACTIVE', 'ON_HOLD', 'COMPLETED', 'ARCHIVED'],
                            'default' => 'ACTIVE',
                        ],
                        'start_date' => [
                            'type' => 'string',
                            'format' => 'date',
                            'example' => '2026-11-01',
                        ],
                        'due_date' => [
                            'type' => 'string',
                            'format' => 'date',
                            'example' => '2026-12-31',
                        ],
                    ],
                ],
                'UpdateProjectRequest' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => [
                            'type' => 'string',
                            'maxLength' => 150,
                            'description' => 'Updated project name',
                            'example' => 'Project Phoenix 2.0',
                        ],
                        'description' => [
                            'type' => 'string',
                            'description' => 'Updated project summary',
                            'example' => 'Expanded scope including edge nodes',
                        ],
                        'status' => [
                            'type' => 'string',
                            'enum' => ['ACTIVE', 'ON_HOLD', 'COMPLETED', 'ARCHIVED'],
                            'example' => 'ON_HOLD',
                        ],
                        'start_date' => [
                            'type' => 'string',
                            'format' => 'date',
                            'example' => '2026-11-01',
                        ],
                        'due_date' => [
                            'type' => 'string',
                            'format' => 'date',
                            'example' => '2027-01-15',
                        ],
                    ],
                ],
                'AddProjectMemberRequest' => [
                    'type' => 'object',
                    'required' => ['user_id', 'role'],
                    'properties' => [
                        'user_id' => [
                            'type' => 'integer',
                            'description' => 'User ID (must already be a member of the parent team)',
                            'example' => 2,
                        ],
                        'role' => [
                            'type' => 'string',
                            'enum' => ['OWNER', 'MANAGER', 'MEMBER', 'VIEWER'],
                            'description' => 'Assigned project role',
                            'example' => 'MEMBER',
                        ],
                    ],
                ],
                'UpdateProjectMemberRoleRequest' => [
                    'type' => 'object',
                    'required' => ['role'],
                    'properties' => [
                        'role' => [
                            'type' => 'string',
                            'enum' => ['OWNER', 'MANAGER', 'MEMBER', 'VIEWER'],
                            'description' => 'New project role',
                            'example' => 'MANAGER',
                        ],
                    ],
                ],
                'TaskResource' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'example' => 1],
                        'project_id' => ['type' => 'integer', 'example' => 1],
                        'parent_task_id' => ['type' => 'integer', 'nullable' => true, 'example' => null],
                        'created_by' => ['type' => 'integer', 'example' => 1],
                        'title' => ['type' => 'string', 'example' => 'Implement Auth System'],
                        'description' => ['type' => 'string', 'nullable' => true, 'example' => 'Setup Sanctum tokens'],
                        'status' => ['type' => 'string', 'enum' => ['TODO', 'IN_PROGRESS', 'IN_REVIEW', 'DONE', 'CANCELLED'], 'example' => 'TODO'],
                        'priority' => ['type' => 'string', 'enum' => ['LOW', 'MEDIUM', 'HIGH', 'URGENT'], 'example' => 'HIGH'],
                        'start_at' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true, 'example' => '2026-11-01T09:00:00Z'],
                        'due_at' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true, 'example' => '2026-11-10T18:00:00Z'],
                        'completed_at' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true, 'example' => null],
                        'position' => ['type' => 'integer', 'example' => 0],
                        'created_at' => ['type' => 'string', 'format' => 'date-time', 'example' => '2026-10-30T10:00:00Z'],
                        'updated_at' => ['type' => 'string', 'format' => 'date-time', 'example' => '2026-10-30T10:00:00Z'],
                        'helpers' => [
                            'type' => 'object',
                            'properties' => [
                                'can_update' => ['type' => 'boolean', 'example' => true],
                                'can_delete' => ['type' => 'boolean', 'example' => true],
                                'can_assign' => ['type' => 'boolean', 'example' => true],
                            ],
                        ],
                    ],
                ],
                'StoreTaskRequest' => [
                    'type' => 'object',
                    'required' => ['title'],
                    'properties' => [
                        'title' => ['type' => 'string', 'maxLength' => 255, 'example' => 'Implement Auth System'],
                        'description' => ['type' => 'string', 'example' => 'Setup Sanctum tokens'],
                        'priority' => ['type' => 'string', 'enum' => ['LOW', 'MEDIUM', 'HIGH', 'URGENT'], 'default' => 'MEDIUM'],
                        'parent_task_id' => ['type' => 'integer', 'nullable' => true, 'example' => null],
                        'start_at' => ['type' => 'string', 'format' => 'date-time', 'example' => '2026-11-01 09:00:00'],
                        'due_at' => ['type' => 'string', 'format' => 'date-time', 'example' => '2026-11-10 18:00:00'],
                        'position' => ['type' => 'integer', 'minimum' => 0, 'example' => 0],
                        'assignee_ids' => [
                            'type' => 'array',
                            'items' => ['type' => 'integer'],
                            'example' => [2, 3],
                        ],
                    ],
                ],
                'UpdateTaskRequest' => [
                    'type' => 'object',
                    'properties' => [
                        'title' => ['type' => 'string', 'maxLength' => 255, 'example' => 'Refined Auth System'],
                        'description' => ['type' => 'string', 'example' => 'Add token expiry configuration'],
                        'status' => ['type' => 'string', 'enum' => ['TODO', 'IN_PROGRESS', 'IN_REVIEW', 'DONE', 'CANCELLED'], 'example' => 'IN_PROGRESS'],
                        'priority' => ['type' => 'string', 'enum' => ['LOW', 'MEDIUM', 'HIGH', 'URGENT'], 'example' => 'URGENT'],
                        'parent_task_id' => ['type' => 'integer', 'nullable' => true, 'example' => null],
                        'start_at' => ['type' => 'string', 'format' => 'date-time', 'example' => '2026-11-01 09:00:00'],
                        'due_at' => ['type' => 'string', 'format' => 'date-time', 'example' => '2026-11-12 18:00:00'],
                        'position' => ['type' => 'integer', 'minimum' => 0, 'example' => 1],
                    ],
                ],
                'SyncAssigneesRequest' => [
                    'type' => 'object',
                    'required' => ['assignee_ids'],
                    'properties' => [
                        'assignee_ids' => [
                            'type' => 'array',
                            'items' => ['type' => 'integer'],
                            'example' => [1, 2],
                        ],
                    ],
                ],
                'TaskResponse' => [
                    'type' => 'object',
                    'properties' => [
                        'key' => ['type' => 'string', 'example' => 'success'],
                        'code' => ['type' => 'integer', 'example' => 200],
                        'msg' => ['type' => 'string', 'example' => 'Task retrieved successfully.'],
                        'data' => ['$ref' => '#/components/schemas/TaskResource'],
                        'response_status' => ['$ref' => '#/components/schemas/ResponseStatus'],
                    ],
                ],
                'TaskListResponse' => [
                    'type' => 'object',
                    'properties' => [
                        'key' => ['type' => 'string', 'example' => 'success'],
                        'code' => ['type' => 'integer', 'example' => 200],
                        'msg' => ['type' => 'string', 'example' => 'Tasks retrieved successfully.'],
                        'data' => [
                            'type' => 'object',
                            'properties' => [
                                'data' => [
                                    'type' => 'array',
                                    'items' => ['$ref' => '#/components/schemas/TaskResource'],
                                ],
                                'links' => ['$ref' => '#/components/schemas/PaginationLinks'],
                                'meta' => ['$ref' => '#/components/schemas/PaginationMeta'],
                            ],
                        ],
                        'response_status' => ['$ref' => '#/components/schemas/ResponseStatus'],
                    ],
                ],
                'ChangeTaskStatusRequest' => [
                    'type' => 'object',
                    'required' => ['status'],
                    'properties' => [
                        'status' => [
                            'type' => 'string',
                            'enum' => ['TODO', 'IN_PROGRESS', 'IN_REVIEW', 'DONE', 'CANCELLED'],
                            'example' => 'IN_PROGRESS',
                        ],
                        'reason' => [
                            'type' => 'string',
                            'maxLength' => 500,
                            'nullable' => true,
                            'example' => 'Commenced implementation of backend services',
                        ],
                    ],
                ],
                'AddDependencyRequest' => [
                    'type' => 'object',
                    'required' => ['depends_on_task_id'],
                    'properties' => [
                        'depends_on_task_id' => [
                            'type' => 'integer',
                            'description' => 'The ID of the prerequisite task that must be completed first',
                            'example' => 5,
                        ],
                    ],
                ],
                'TaskStatusHistoryResource' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'example' => 1],
                        'task_id' => ['type' => 'integer', 'example' => 10],
                        'changed_by' => ['type' => 'integer', 'example' => 2],
                        'old_status' => ['type' => 'string', 'example' => 'TODO'],
                        'new_status' => ['type' => 'string', 'example' => 'IN_PROGRESS'],
                        'reason' => ['type' => 'string', 'nullable' => true, 'example' => 'Sprint started'],
                        'created_at' => ['type' => 'string', 'format' => 'date-time', 'example' => '2026-10-31T10:00:00Z'],
                        'changer' => ['$ref' => '#/components/schemas/UserSummaryResource'],
                    ],
                ],
                'TaskStatusHistoryListResponse' => [
                    'type' => 'object',
                    'properties' => [
                        'key' => ['type' => 'string', 'example' => 'success'],
                        'code' => ['type' => 'integer', 'example' => 200],
                        'msg' => ['type' => 'string', 'example' => 'Task status history retrieved successfully.'],
                        'data' => [
                            'type' => 'object',
                            'properties' => [
                                'data' => [
                                    'type' => 'array',
                                    'items' => ['$ref' => '#/components/schemas/TaskStatusHistoryResource'],
                                ],
                                'links' => ['$ref' => '#/components/schemas/PaginationLinks'],
                                'meta' => ['$ref' => '#/components/schemas/PaginationMeta'],
                            ],
                        ],
                        'response_status' => ['$ref' => '#/components/schemas/ResponseStatus'],
                    ],
                ],
                'TaskDependenciesResponse' => [
                    'type' => 'object',
                    'properties' => [
                        'key' => ['type' => 'string', 'example' => 'success'],
                        'code' => ['type' => 'integer', 'example' => 200],
                        'msg' => ['type' => 'string', 'example' => 'Task dependencies retrieved successfully.'],
                        'data' => [
                            'type' => 'object',
                            'properties' => [
                                'dependencies' => [
                                    'type' => 'array',
                                    'description' => 'Tasks that this task depends on (prerequisites)',
                                    'items' => ['$ref' => '#/components/schemas/TaskResource'],
                                ],
                                'dependents' => [
                                    'type' => 'array',
                                    'description' => 'Tasks waiting on this task',
                                    'items' => ['$ref' => '#/components/schemas/TaskResource'],
                                ],
                            ],
                        ],
                        'response_status' => ['$ref' => '#/components/schemas/ResponseStatus'],
                    ],
                ],
                'StoreCommentRequest' => [
                    'type' => 'object',
                    'required' => ['body'],
                    'properties' => [
                        'body' => ['type' => 'string', 'maxLength' => 5000, 'example' => 'Let us schedule a code review tomorrow morning.'],
                    ],
                ],
                'UpdateCommentRequest' => [
                    'type' => 'object',
                    'required' => ['body'],
                    'properties' => [
                        'body' => ['type' => 'string', 'maxLength' => 5000, 'example' => 'Updated comment content with revised details.'],
                    ],
                ],
                'CommentResource' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'example' => 1],
                        'task_id' => ['type' => 'integer', 'example' => 10],
                        'user_id' => ['type' => 'integer', 'example' => 2],
                        'body' => ['type' => 'string', 'example' => 'Let us schedule a code review tomorrow morning.'],
                        'created_at' => ['type' => 'string', 'format' => 'date-time', 'example' => '2026-10-31T10:00:00Z'],
                        'updated_at' => ['type' => 'string', 'format' => 'date-time', 'example' => '2026-10-31T10:05:00Z'],
                        'user' => ['$ref' => '#/components/schemas/UserSummaryResource'],
                        'helpers' => [
                            'type' => 'object',
                            'properties' => [
                                'can_update' => ['type' => 'boolean', 'example' => true],
                                'can_delete' => ['type' => 'boolean', 'example' => true],
                            ],
                        ],
                    ],
                ],
                'CommentDetailResponse' => [
                    'type' => 'object',
                    'properties' => [
                        'key' => ['type' => 'string', 'example' => 'success'],
                        'code' => ['type' => 'integer', 'example' => 200],
                        'msg' => ['type' => 'string', 'example' => 'Comment processed successfully.'],
                        'data' => ['$ref' => '#/components/schemas/CommentResource'],
                        'response_status' => ['$ref' => '#/components/schemas/ResponseStatus'],
                    ],
                ],
                'CommentListResponse' => [
                    'type' => 'object',
                    'properties' => [
                        'key' => ['type' => 'string', 'example' => 'success'],
                        'code' => ['type' => 'integer', 'example' => 200],
                        'msg' => ['type' => 'string', 'example' => 'Comments retrieved successfully.'],
                        'data' => [
                            'type' => 'object',
                            'properties' => [
                                'data' => [
                                    'type' => 'array',
                                    'items' => ['$ref' => '#/components/schemas/CommentResource'],
                                ],
                                'links' => ['$ref' => '#/components/schemas/PaginationLinks'],
                                'meta' => ['$ref' => '#/components/schemas/PaginationMeta'],
                            ],
                        ],
                        'response_status' => ['$ref' => '#/components/schemas/ResponseStatus'],
                    ],
                ],
                'AttachmentResource' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'example' => 1],
                        'task_id' => ['type' => 'integer', 'example' => 10],
                        'uploaded_by' => ['type' => 'integer', 'example' => 2],
                        'original_name' => ['type' => 'string', 'example' => 'architecture-diagram.pdf'],
                        'mime_type' => ['type' => 'string', 'example' => 'application/pdf'],
                        'size' => ['type' => 'integer', 'example' => 204800],
                        'created_at' => ['type' => 'string', 'format' => 'date-time', 'example' => '2026-10-31T10:00:00Z'],
                        'uploader' => ['$ref' => '#/components/schemas/UserSummaryResource'],
                        'helpers' => [
                            'type' => 'object',
                            'properties' => [
                                'can_delete' => ['type' => 'boolean', 'example' => true],
                            ],
                        ],
                    ],
                ],
                'AttachmentDetailResponse' => [
                    'type' => 'object',
                    'properties' => [
                        'key' => ['type' => 'string', 'example' => 'success'],
                        'code' => ['type' => 'integer', 'example' => 201],
                        'msg' => ['type' => 'string', 'example' => 'Attachment uploaded successfully.'],
                        'data' => ['$ref' => '#/components/schemas/AttachmentResource'],
                        'response_status' => ['$ref' => '#/components/schemas/ResponseStatus'],
                    ],
                ],
                'AttachmentListResponse' => [
                    'type' => 'object',
                    'properties' => [
                        'key' => ['type' => 'string', 'example' => 'success'],
                        'code' => ['type' => 'integer', 'example' => 200],
                        'msg' => ['type' => 'string', 'example' => 'Attachments retrieved successfully.'],
                        'data' => [
                            'type' => 'object',
                            'properties' => [
                                'data' => [
                                    'type' => 'array',
                                    'items' => ['$ref' => '#/components/schemas/AttachmentResource'],
                                ],
                                'links' => ['$ref' => '#/components/schemas/PaginationLinks'],
                                'meta' => ['$ref' => '#/components/schemas/PaginationMeta'],
                            ],
                        ],
                        'response_status' => ['$ref' => '#/components/schemas/ResponseStatus'],
                    ],
                ],
            ],
        ];
    }
}
