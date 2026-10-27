<?php

namespace App\Traits\Api;

use Illuminate\Http\JsonResponse;

trait ResponseTrait
{
    /**
     * Map response key to envelope status code.
     */
    public function getCode(string $key): int
    {
        return match ($key) {
            'success' => 200,
            'fail' => 400,
            'unauthenticated' => 401,
            'unauthorized' => 403,
            'blocked', 'needActive', 'go_to_complete_data', 'pendingApproval' => 403,
            'not_found' => 404,
            'exception' => 500,
            default => 200,
        };
    }

    /**
     * Generate standard API JSON response with HTTP 200 transport.
     */
    public function jsonResponse(
        mixed $data = null,
        string $msg = '',
        string $key = 'success',
        bool $error = false,
        mixed $errors = null,
        ?int $code = null,
        int $httpStatus = 200
    ): JsonResponse {
        $envelopeCode = $code ?? $this->getCode($key);
        $isError = $error || ($key !== 'success');

        $validationErrors = [];
        if ($errors !== null) {
            $validationErrors = is_array($errors) ? $errors : ['error' => [$errors]];
        }

        $payload = [
            'key' => $key,
            'code' => $envelopeCode,
            'msg' => $msg,
            'data' => $data,
            'response_status' => [
                'error' => $isError,
                'validation_errors' => (object) $validationErrors,
            ],
        ];

        return response()->json($payload, $httpStatus);
    }
}

