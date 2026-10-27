<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api/v1',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'key' => 'fail',
                    'code' => 422,
                    'msg' => $e->getMessage(),
                    'data' => null,
                    'response_status' => [
                        'error' => true,
                        'validation_errors' => (object) $e->errors(),
                    ],
                ], 200);
            }
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'key' => 'unauthenticated',
                    'code' => 401,
                    'msg' => 'Unauthenticated.',
                    'data' => null,
                    'response_status' => [
                        'error' => true,
                        'validation_errors' => (object) [],
                    ],
                ], 200);
            }
        });

        $exceptions->render(function (AuthorizationException | AccessDeniedHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'key' => 'unauthorized',
                    'code' => 403,
                    'msg' => 'Forbidden.',
                    'data' => null,
                    'response_status' => [
                        'error' => true,
                        'validation_errors' => (object) [],
                    ],
                ], 200);
            }
        });

        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'key' => 'not_found',
                    'code' => 404,
                    'msg' => 'Resource not found.',
                    'data' => null,
                    'response_status' => [
                        'error' => true,
                        'validation_errors' => (object) [],
                    ],
                ], 200);
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $prev = $e->getPrevious();
                $msg = ($prev instanceof ModelNotFoundException) ? 'Resource not found.' : 'Resource not found.';

                return response()->json([
                    'key' => 'not_found',
                    'code' => 404,
                    'msg' => $msg,
                    'data' => null,
                    'response_status' => [
                        'error' => true,
                        'validation_errors' => (object) [],
                    ],
                ], 200);
            }
        });
    })->create();
