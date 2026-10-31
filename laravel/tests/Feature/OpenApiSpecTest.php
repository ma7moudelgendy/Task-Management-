<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class OpenApiSpecTest extends TestCase
{
    /**
     * Test that openapi:generate generates a valid OpenAPI 3.1.0 document.
     */
    public function test_openapi_generate_command_creates_valid_specification(): void
    {
        $this->artisan('openapi:generate')
            ->assertSuccessful();

        $path = base_path('../docs/openapi.json');
        if (! File::exists($path)) {
            $path = base_path('docs/openapi.json');
        }

        $this->assertFileExists($path);

        $content = File::get($path);
        $spec = json_decode($content, true);

        $this->assertIsArray($spec);
        $this->assertSame('3.1.0', $spec['openapi']);
        $this->assertSame('TaskFlow API', $spec['info']['title']);
        $this->assertNotEmpty($spec['paths']);
        $this->assertNotEmpty($spec['components']['schemas']);
        $this->assertArrayHasKey('BearerAuth', $spec['components']['securitySchemes']);
    }

    /**
     * Test that all actual Laravel routes are documented in the specification.
     */
    public function test_all_api_routes_are_documented_in_openapi(): void
    {
        $this->artisan('openapi:generate');

        $path = base_path('../docs/openapi.json');
        if (! File::exists($path)) {
            $path = base_path('docs/openapi.json');
        }

        $spec = json_decode(File::get($path), true);
        $paths = $spec['paths'];

        $expectedEndpoints = [
            '/health' => ['get'],
            '/auth/register' => ['post'],
            '/auth/login' => ['post'],
            '/auth/logout' => ['post'],
            '/auth/me' => ['get', 'patch'],
            '/auth/forgot-password' => ['post'],
            '/auth/reset-password' => ['post'],
            '/user' => ['get'],
            '/teams' => ['get', 'post'],
            '/teams/{team}' => ['get', 'patch', 'delete'],
            '/teams/{team}/members' => ['get', 'post'],
            '/teams/{team}/members/{user}' => ['patch', 'delete'],
            '/projects' => ['get', 'post'],
            '/projects/{project}' => ['get', 'patch', 'delete'],
            '/projects/{project}/members' => ['get', 'post'],
            '/projects/{project}/members/{user}' => ['patch', 'delete'],
            '/projects/{project}/tasks' => ['get', 'post'],
            '/tasks' => ['get'],
            '/tasks/{task}' => ['get', 'patch', 'delete'],
            '/tasks/{task}/assignees' => ['put'],
            '/tasks/{task}/status' => ['post'],
            '/tasks/{task}/dependencies' => ['get', 'post'],
            '/tasks/{task}/dependencies/{dependency}' => ['delete'],
            '/tasks/{task}/history' => ['get'],
            '/tasks/{task}/comments' => ['get', 'post'],
            '/comments/{comment}' => ['patch', 'delete'],
            '/tasks/{task}/attachments' => ['get', 'post'],
            '/tasks/{task}/attachments/{attachment}/download' => ['get'],
            '/tasks/{task}/attachments/{attachment}' => ['delete'],
        ];

        foreach ($expectedEndpoints as $route => $methods) {
            $this->assertArrayHasKey($route, $paths, "Missing OpenAPI documentation for path: {$route}");
            foreach ($methods as $method) {
                $this->assertArrayHasKey($method, $paths[$route], "Missing HTTP method '{$method}' for path: {$route}");
            }
        }
    }

    /**
     * Test that protected routes require Sanctum Bearer authentication.
     */
    public function test_protected_routes_require_sanctum_bearer_auth(): void
    {
        $path = base_path('../docs/openapi.json');
        if (! File::exists($path)) {
            $path = base_path('docs/openapi.json');
        }

        $spec = json_decode(File::get($path), true);
        $paths = $spec['paths'];

        $protectedOperations = [
            ['/auth/logout', 'post'],
            ['/auth/me', 'get'],
            ['/auth/me', 'patch'],
            ['/user', 'get'],
            ['/teams', 'get'],
            ['/teams', 'post'],
            ['/teams/{team}', 'get'],
            ['/teams/{team}', 'patch'],
            ['/teams/{team}', 'delete'],
            ['/teams/{team}/members', 'get'],
            ['/teams/{team}/members', 'post'],
            ['/teams/{team}/members/{user}', 'patch'],
            ['/teams/{team}/members/{user}', 'delete'],
            ['/projects', 'get'],
            ['/projects', 'post'],
            ['/projects/{project}', 'get'],
            ['/projects/{project}', 'patch'],
            ['/projects/{project}', 'delete'],
            ['/projects/{project}/members', 'get'],
            ['/projects/{project}/members', 'post'],
            ['/projects/{project}/members/{user}', 'patch'],
            ['/projects/{project}/members/{user}', 'delete'],
            ['/projects/{project}/tasks', 'get'],
            ['/projects/{project}/tasks', 'post'],
            ['/tasks', 'get'],
            ['/tasks/{task}', 'get'],
            ['/tasks/{task}', 'patch'],
            ['/tasks/{task}', 'delete'],
            ['/tasks/{task}/assignees', 'put'],
            ['/tasks/{task}/status', 'post'],
            ['/tasks/{task}/dependencies', 'get'],
            ['/tasks/{task}/dependencies', 'post'],
            ['/tasks/{task}/dependencies/{dependency}', 'delete'],
            ['/tasks/{task}/history', 'get'],
            ['/tasks/{task}/comments', 'get'],
            ['/tasks/{task}/comments', 'post'],
            ['/comments/{comment}', 'patch'],
            ['/comments/{comment}', 'delete'],
            ['/tasks/{task}/attachments', 'get'],
            ['/tasks/{task}/attachments', 'post'],
            ['/tasks/{task}/attachments/{attachment}/download', 'get'],
            ['/tasks/{task}/attachments/{attachment}', 'delete'],
        ];

        foreach ($protectedOperations as [$route, $method]) {
            $operation = $paths[$route][$method];
            $this->assertArrayHasKey('security', $operation, "Route {$route} [{$method}] should require security");
            $this->assertEquals([['BearerAuth' => []]], $operation['security']);
        }
    }

    /**
     * Test that response envelope schemas adhere to project standards.
     */
    public function test_response_envelope_schemas_are_compliant(): void
    {
        $path = base_path('../docs/openapi.json');
        if (! File::exists($path)) {
            $path = base_path('docs/openapi.json');
        }

        $spec = json_decode(File::get($path), true);
        $schemas = $spec['components']['schemas'];

        $this->assertArrayHasKey('StandardEnvelopeResponse', $schemas);
        $this->assertArrayHasKey('ResponseStatus', $schemas);
        $this->assertArrayHasKey('UserResource', $schemas);
        $this->assertArrayHasKey('RegisterRequest', $schemas);

        $envelopeProps = $schemas['StandardEnvelopeResponse']['properties'];
        $this->assertArrayHasKey('key', $envelopeProps);
        $this->assertArrayHasKey('code', $envelopeProps);
        $this->assertArrayHasKey('msg', $envelopeProps);
        $this->assertArrayHasKey('data', $envelopeProps);
        $this->assertArrayHasKey('response_status', $envelopeProps);
    }

    /**
     * Test apidog:sync dry-run execution.
     */
    public function test_apidog_sync_dry_run_executes_safely(): void
    {
        $this->artisan('apidog:sync', ['--dry-run' => true])
            ->expectsOutputToContain('Apidog OpenAPI Sync Plan')
            ->expectsOutputToContain('DRY RUN MODE')
            ->assertSuccessful();
    }
}
