<?php

namespace App\Traits;

use App\Traits\Api\ResponseTrait;
use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    use ResponseTrait;

    /**
     * Standard success JSON response.
     */
    public function successResponse(mixed $data = null, ?string $message = null, int $code = 200): JsonResponse
    {
        return $this->jsonResponse(
            data: $data,
            msg: $message ?? 'Operation successful.',
            key: 'success',
            error: false,
            code: $code
        );
    }

    /**
     * Standard error JSON response.
     */
    public function errorResponse(string $message = 'Operation failed.', int $code = 400, mixed $errors = null): JsonResponse
    {
        $key = match ($code) {
            401 => 'unauthenticated',
            403 => 'unauthorized',
            404 => 'not_found',
            500 => 'exception',
            default => 'fail',
        };

        return $this->jsonResponse(
            data: null,
            msg: $message,
            key: $key,
            error: true,
            errors: $errors,
            code: $code
        );
    }
}

