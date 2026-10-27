<?php

namespace App\Traits\Api;

use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

trait HandlesApiExceptions
{
    use ResponseTrait;

    /**
     * Wrap API action in centralized exception handling.
     */
    public function handleApi(Closure $callback): JsonResponse
    {
        try {
            return $callback();
        } catch (ValidationException $e) {
            return $this->jsonResponse(
                data: null,
                msg: $e->getMessage(),
                key: 'fail',
                error: true,
                errors: $e->errors(),
                code: 422
            );
        } catch (AuthenticationException $e) {
            return $this->jsonResponse(
                data: null,
                msg: $e->getMessage() ?: 'Unauthenticated.',
                key: 'unauthenticated',
                error: true,
                code: 401
            );
        } catch (AuthorizationException | AccessDeniedHttpException $e) {
            return $this->jsonResponse(
                data: null,
                msg: $e->getMessage() ?: 'Forbidden.',
                key: 'unauthorized',
                error: true,
                code: 403
            );
        } catch (ModelNotFoundException | NotFoundHttpException $e) {
            return $this->jsonResponse(
                data: null,
                msg: 'Resource not found.',
                key: 'not_found',
                error: true,
                code: 404
            );
        } catch (Throwable $e) {
            Log::error('API Exception: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            $message = config('app.debug')
                ? $e->getMessage()
                : 'An unexpected server error occurred.';

            return $this->jsonResponse(
                data: null,
                msg: $message,
                key: 'exception',
                error: true,
                code: 500
            );
        }
    }
}

