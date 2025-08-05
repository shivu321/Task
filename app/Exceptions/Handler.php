<?php

namespace App\Exceptions;

use Dotenv\Exception\ValidationException;
use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Response;
use Symfony\Component\CssSelector\Exception\InternalErrorException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register(): void
    {
        // 1. Handle internal errors
        $this->renderable(function (InternalErrorException $e, $request) {
            if ($request->is('api/*') || $request->is('admin/*')) {
                return response()->json([
                    'message' => 'An internal server error occurred.',
                    'from' => 'InternalErrorException',
                ], 500);
            }
        });

        // 2. Handle route not found
        $this->renderable(function (NotFoundHttpException $e, $request) {
            if ($request->is('api/*') || $request->is('admin/*')) {
                return response()->json([
                    'message' => 'Route not found.',
                    'from' => 'NotFoundHttpException',
                ], 404);
            }
        });

        // 3. Handle unauthenticated requests
        $this->renderable(function (AuthenticationException $e, $request) {
            if ($request->is('api/*') || $request->is('admin/*')) {
                return response()->json([
                    'message' => 'Authentication required.',
                    'from' => 'AuthenticationException',
                ], 401);
            }
        });

        // 4. Handle missing model instances
        $this->renderable(function (ModelNotFoundException $e, $request) {
            if ($request->is('api/*') || $request->is('admin/*')) {
                return response()->json([
                    'message' => class_basename($e->getModel()) . ' not found.',
                    'from' => 'ModelNotFoundException',
                ], 404);
            }
        });

        // 5. Handle unauthorized route access
        $this->renderable(function (RouteNotFoundException $e, $request) {
            if ($request->is('api/*') || $request->is('admin/*')) {
                return response()->json([
                    'message' => 'Unauthorized or invalid route access.',
                    'from' => 'RouteNotFoundException',
                ], 403);
            }
        });

        // 6. Handle validation errors
        $this->renderable(function (ValidationException $e, $request) {
            if ($request->is('api/*') || $request->is('admin/*')) {
                return response()->json([
                    'message' => 'Validation failed.',
                    'errors' => $e->getMessage(),
                    'from' => 'ValidationException',
                ], 422);
            }
        });

        // 7. Handle database query issues
        $this->renderable(function (QueryException $e, $request) {
            if ($request->is('api/*') || $request->is('admin/*')) {
                $errorCode = $e->errorInfo[1] ?? null;

                switch ($errorCode) {
                    case 1062: // Duplicate entry
                        return response()->json([
                            'message' => 'Duplicate entry found.',
                            'from' => 'QueryException (1062)',
                        ], 409);

                    case 1452: // Foreign key constraint fails
                        return response()->json([
                            'message' => 'Cannot add or update due to foreign key constraint.',
                            'from' => 'QueryException (1452)',
                        ], 400);

                    case 1364: // Field doesn’t have a default value
                        return response()->json([
                            'message' => 'Missing required field value.',
                            'from' => 'QueryException (1364)',
                        ], 400);

                    case 1054: // Unknown column
                    case '42S22':
                        return response()->json([
                            'message' => 'Invalid column name in database query.',
                            'from' => 'QueryException (1054/42S22)',
                        ], 400);

                    default:
                        return response()->json([
                            'message' => 'A database query error occurred.',
                            'error' => $e->getMessage(),
                            'from' => 'QueryException (default)',
                        ], 500);
                }
            }
        });

        // 8. Catch-all handler for other exceptions
        $this->renderable(function (Exception $e, $request) {
            if ($request->is('api/*') || $request->is('admin/*')) {
                return response()->json([
                    'message' => 'An unexpected error occurred.',
                    'error' => $e->getMessage(),
                    'from' => 'Exception',
                ], 500);
            }
        });
    }
}
