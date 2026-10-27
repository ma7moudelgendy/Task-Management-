<?php

namespace App\Services\Auth;

use App\DTOs\Auth\ForgotPasswordDTO;
use App\DTOs\Auth\LoginDTO;
use App\DTOs\Auth\RegisterDTO;
use App\DTOs\Auth\ResetPasswordDTO;
use App\DTOs\Auth\UpdateProfileDTO;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthService
{
    public function __construct(
        protected UserRepositoryInterface $userRepository
    ) {}

    /**
     * Register a new user.
     *
     * @return array{key: string, msg: string, code: int, data: array{user: User, token: string}}
     */
    public function register(RegisterDTO $dto): array
    {
        $user = $this->userRepository->create([
            'name' => $dto->name,
            'email' => $dto->email,
            'password' => $dto->password,
            'is_active' => true,
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return [
            'key' => 'success',
            'msg' => 'Registration successful.',
            'code' => 201,
            'data' => [
                'user' => $user,
                'token' => $token,
            ],
        ];
    }

    /**
     * Authenticate a user and issue a token.
     *
     * @return array{key: string, msg: string, code: int, data: ?array{user: User, token: string}}
     */
    public function login(LoginDTO $dto, ?string $ip = null): array
    {
        $throttleKey = Str::lower($dto->email) . '|' . ($ip ?? '127.0.0.1');

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return [
                'key' => 'fail',
                'msg' => "Too many login attempts. Please try again in {$seconds} seconds.",
                'code' => 429,
                'data' => null,
            ];
        }

        $user = $this->userRepository->findByEmail($dto->email);

        if (! $user || ! Hash::check($dto->password, $user->password)) {
            RateLimiter::hit($throttleKey, 60);

            return [
                'key' => 'fail',
                'msg' => 'Invalid email or password.',
                'code' => 401,
                'data' => null,
            ];
        }

        if (! $user->is_active) {
            return [
                'key' => 'needActive',
                'msg' => 'Account is inactive. Please contact support.',
                'code' => 403,
                'data' => null,
            ];
        }

        RateLimiter::clear($throttleKey);

        $deviceName = $dto->deviceName ?: 'auth-token';
        $token = $user->createToken($deviceName)->plainTextToken;

        return [
            'key' => 'success',
            'msg' => 'Login successful.',
            'code' => 200,
            'data' => [
                'user' => $user,
                'token' => $token,
            ],
        ];
    }

    /**
     * Invalidate current user token.
     *
     * @return array{key: string, msg: string, code: int, data: null}
     */
    public function logout(User $user): array
    {
        $user->currentAccessToken()?->delete();

        return [
            'key' => 'success',
            'msg' => 'Logged out successfully.',
            'code' => 200,
            'data' => null,
        ];
    }

    /**
     * Retrieve user profile.
     *
     * @return array{key: string, msg: string, code: int, data: array{user: User}}
     */
    public function me(User $user): array
    {
        return [
            'key' => 'success',
            'msg' => 'Profile retrieved.',
            'code' => 200,
            'data' => [
                'user' => $user,
            ],
        ];
    }

    /**
     * Update authenticated user profile.
     *
     * @return array{key: string, msg: string, code: int, data: ?array{user: User}}
     */
    public function updateProfile(User $user, UpdateProfileDTO $dto): array
    {
        if ($dto->password) {
            if (! $dto->currentPassword || ! Hash::check($dto->currentPassword, $user->password)) {
                return [
                    'key' => 'fail',
                    'msg' => 'Current password does not match.',
                    'code' => 422,
                    'data' => null,
                ];
            }
        }

        $this->userRepository->update($user, $dto->toArray());
        $user->refresh();

        return [
            'key' => 'success',
            'msg' => 'Profile updated successfully.',
            'code' => 200,
            'data' => [
                'user' => $user,
            ],
        ];
    }

    /**
     * Send password reset link to user.
     *
     * @return array{key: string, msg: string, code: int, data: null}
     */
    public function forgotPassword(ForgotPasswordDTO $dto): array
    {
        $status = Password::sendResetLink(['email' => $dto->email]);

        if ($status === Password::RESET_LINK_SENT) {
            return [
                'key' => 'success',
                'msg' => 'Password reset link sent to your email.',
                'code' => 200,
                'data' => null,
            ];
        }

        return [
            'key' => 'fail',
            'msg' => __($status),
            'code' => 400,
            'data' => null,
        ];
    }

    /**
     * Reset password with token.
     *
     * @return array{key: string, msg: string, code: int, data: null}
     */
    public function resetPassword(ResetPasswordDTO $dto): array
    {
        $status = Password::reset(
            [
                'email' => $dto->email,
                'password' => $dto->password,
                'password_confirmation' => $dto->password,
                'token' => $dto->token,
            ],
            function (User $user, string $password) {
                $this->userRepository->update($user, [
                    'password' => $password,
                ]);
                // Revoke all tokens to enforce re-login
                $user->tokens()->delete();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return [
                'key' => 'success',
                'msg' => 'Password has been reset successfully.',
                'code' => 200,
                'data' => null,
            ];
        }

        return [
            'key' => 'fail',
            'msg' => __($status),
            'code' => 422,
            'data' => null,
        ];
    }
}
