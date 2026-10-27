<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Auth\ForgotPasswordDTO;
use App\DTOs\Auth\LoginDTO;
use App\DTOs\Auth\RegisterDTO;
use App\DTOs\Auth\ResetPasswordDTO;
use App\DTOs\Auth\UpdateProfileDTO;
use App\Http\Requests\Api\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Http\Requests\Api\Auth\ResetPasswordRequest;
use App\Http\Requests\Api\Auth\UpdateProfileRequest;
use App\Http\Resources\Api\UserResource;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends ApiController
{
    public function __construct(
        protected AuthService $authService
    ) {}

    /**
     * Register a new user.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $dto = RegisterDTO::fromRequest($request);
            $result = $this->authService->register($dto);

            return $this->jsonResponse(
                data: [
                    'user' => UserResource::make($result['data']['user']),
                    'token' => $result['data']['token'],
                ],
                msg: $result['msg'],
                key: $result['key'],
                code: $result['code']
            );
        });
    }

    /**
     * Authenticate user with credentials.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $dto = LoginDTO::fromRequest($request);
            $result = $this->authService->login($dto, $request->ip());

            if ($result['key'] !== 'success') {
                return $this->jsonResponse(
                    data: null,
                    msg: $result['msg'],
                    key: $result['key'],
                    error: true,
                    code: $result['code']
                );
            }

            return $this->jsonResponse(
                data: [
                    'user' => UserResource::make($result['data']['user']),
                    'token' => $result['data']['token'],
                ],
                msg: $result['msg'],
                key: $result['key'],
                code: $result['code']
            );
        });
    }

    /**
     * Invalidate current access token.
     */
    public function logout(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $result = $this->authService->logout($request->user());

            return $this->jsonResponse(
                data: null,
                msg: $result['msg'],
                key: $result['key'],
                code: $result['code']
            );
        });
    }

    /**
     * Get authenticated user profile.
     */
    public function me(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $result = $this->authService->me($request->user());

            return $this->jsonResponse(
                data: [
                    'user' => UserResource::make($result['data']['user']),
                ],
                msg: $result['msg'],
                key: $result['key'],
                code: $result['code']
            );
        });
    }

    /**
     * Update user profile.
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $dto = UpdateProfileDTO::fromRequest($request);
            $result = $this->authService->updateProfile($request->user(), $dto);

            if ($result['key'] !== 'success') {
                return $this->jsonResponse(
                    data: null,
                    msg: $result['msg'],
                    key: $result['key'],
                    error: true,
                    code: $result['code']
                );
            }

            return $this->jsonResponse(
                data: [
                    'user' => UserResource::make($result['data']['user']),
                ],
                msg: $result['msg'],
                key: $result['key'],
                code: $result['code']
            );
        });
    }

    /**
     * Send password reset link.
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $dto = ForgotPasswordDTO::fromRequest($request);
            $result = $this->authService->forgotPassword($dto);

            return $this->jsonResponse(
                data: null,
                msg: $result['msg'],
                key: $result['key'],
                error: $result['key'] !== 'success',
                code: $result['code']
            );
        });
    }

    /**
     * Reset password with reset token.
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $dto = ResetPasswordDTO::fromRequest($request);
            $result = $this->authService->resetPassword($dto);

            return $this->jsonResponse(
                data: null,
                msg: $result['msg'],
                key: $result['key'],
                error: $result['key'] !== 'success',
                code: $result['code']
            );
        });
    }
}
