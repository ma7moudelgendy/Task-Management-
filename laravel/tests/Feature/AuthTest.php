<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use DatabaseTransactions;
    public function test_user_can_register_successfully(): void
    {
        $payload = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ];

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'success',
                'response_status' => [
                    'error' => false,
                ],
            ])
            ->assertJsonStructure([
                'key',
                'code',
                'msg',
                'data' => [
                    'user' => ['id', 'name', 'email', 'role', 'is_active', 'created_at', 'updated_at'],
                    'token',
                ],
                'response_status' => [
                    'error',
                    'validation_errors',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john.doe@example.com',
            'name' => 'John Doe',
            'is_active' => true,
        ]);
    }

    public function test_duplicate_email_registration_fails(): void
    {
        User::factory()->create(['email' => 'duplicate@example.com']);

        $payload = [
            'name' => 'Another User',
            'email' => 'duplicate@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ];

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'fail',
                'response_status' => [
                    'error' => true,
                ],
            ]);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'login.test@example.com',
            'password' => Hash::make('SecretPass123!'),
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'login.test@example.com',
            'password' => 'SecretPass123!',
            'device_name' => 'MobileApp',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'success',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'email' => 'login.test@example.com',
                    ],
                ],
            ])
            ->assertJsonStructure([
                'data' => [
                    'token',
                ],
            ]);
    }

    public function test_login_fails_with_invalid_password(): void
    {
        User::factory()->create([
            'email' => 'wrongpass@example.com',
            'password' => Hash::make('CorrectPassword123!'),
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'wrongpass@example.com',
            'password' => 'WrongPassword!',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'fail',
                'code' => 401,
                'response_status' => [
                    'error' => true,
                ],
            ]);
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->inactive()->create([
            'email' => 'inactive.user@example.com',
            'password' => Hash::make('ValidPassword123!'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'inactive.user@example.com',
            'password' => 'ValidPassword123!',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'needActive',
                'code' => 403,
                'response_status' => [
                    'error' => true,
                ],
            ]);
    }

    public function test_unauthenticated_user_cannot_access_profile(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'unauthenticated',
                'code' => 401,
                'response_status' => [
                    'error' => true,
                ],
            ]);
    }

    public function test_authenticated_user_can_get_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Profile User',
            'email' => 'profile.user@example.com',
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'success',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => 'Profile User',
                        'email' => 'profile.user@example.com',
                    ],
                ],
            ]);
    }

    public function test_authenticated_user_can_update_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'orig.email@example.com',
            'password' => Hash::make('CurrentPassword123!'),
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson('/api/v1/auth/me', [
                'name' => 'Updated Name',
                'current_password' => 'CurrentPassword123!',
                'password' => 'NewPassword456!',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'success',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => 'Updated Name',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
        ]);

        $this->assertTrue(Hash::check('NewPassword456!', $user->fresh()->password));
    }

    public function test_logout_revokes_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('session-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'success',
            ]);

        // Forget auth guards so Laravel does not reuse the cached user in the test process
        $this->app['auth']->forgetGuards();

        // Attempt to call /me with the revoked token
        $subsequentResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/auth/me');

        $subsequentResponse->assertStatus(200)
            ->assertJson([
                'key' => 'unauthenticated',
                'code' => 401,
            ]);
    }

    public function test_forgot_password_and_reset_password_flow(): void
    {
        $user = User::factory()->create([
            'email' => 'reset.me@example.com',
            'password' => Hash::make('OldPassword123!'),
        ]);

        // Request reset
        $forgotResponse = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'reset.me@example.com',
        ]);

        $forgotResponse->assertStatus(200)
            ->assertJson([
                'key' => 'success',
            ]);

        // Create a valid password reset token
        $token = Password::broker()->createToken($user);

        // Perform reset
        $resetResponse = $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'reset.me@example.com',
            'token' => $token,
            'password' => 'BrandNewPassword123!',
            'password_confirmation' => 'BrandNewPassword123!',
        ]);

        $resetResponse->assertStatus(200)
            ->assertJson([
                'key' => 'success',
            ]);

        $this->assertTrue(Hash::check('BrandNewPassword123!', $user->fresh()->password));
    }
}
