<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiBootTest extends TestCase
{
    public function test_api_health_endpoint_returns_success(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'ok',
                'version' => 'v1',
            ]);
    }

    public function test_unauthenticated_api_request_returns_structured_unauthenticated_response(): void
    {
        $response = $this->getJson('/api/v1/user');

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'unauthenticated',
                'code' => 401,
                'msg' => 'Unauthenticated.',
                'response_status' => [
                    'error' => true,
                ],
            ]);
    }

    public function test_non_existent_api_endpoint_returns_structured_not_found_response(): void
    {
        $response = $this->getJson('/api/v1/non-existent-route');

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'not_found',
                'code' => 404,
                'response_status' => [
                    'error' => true,
                ],
            ]);
    }
}
