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
        $this->renderable(function (InternalErrorException $e, $request) {
            if ($request->is('api/*') || $request->is('admin/*')) {
                return response()->json([
                    'message' => 'Record not found.',   "from" => "handler NotFoundHttpException",
                ], 404);
            }
        });
        $this->renderable(function (NotFoundHttpException $e, $request) {
            if ($request->is('api/*') || $request->is('admin/*')) {
                return response()->json([
                    'message' => 'Record not found.',   "from" => "handler NotFoundHttpException",
                ], 404);
            }
        });
        $this->renderable(function (AuthenticationException $e, $request) {
            if ($request->is('api/*') || $request->is('admin/*')) {
                return response()->json([
                    'message' => 'Unauthenticated.',   "from" => "handler AuthenticationException",
                ], 401);
            }
        });


        $this->renderable(function (ModelNotFoundException $e, $request) {
            if ($request->is('api/*') || $request->is('admin/*')) {
                return response()->json([
                    'message' => $e->getModel() . ' model not found!',   "from" => "handler",
                ], 404);
            }
        });
       
        $this->renderable(function (RouteNotFoundException $e, $request) {
            if ($request->is('api/*') || $request->is('admin/*')) {
                return response()->json([
                    'message' => 'unauthenticated or  Invalid Request!',
                    "from" => "handler RouteNotFoundException",
                ], 404);
            }
        });

        $this->renderable(function (ValidationException $e, $request) {
            if ($request->is('api/*') || $request->is('admin/*')) {
                return response()->json([
                    'message' => $e->getMessage(),
                    "status" => "error"
                ], 422);
            }
        });

        // $this->renderable(function (Swift_TransportException $e, $request) {
        //     if ($request->is('tenant/*') ) {
        //         return response()->json([
        //             "from" => "handler Swift_TransportException",
        //             'message' => "The mail service has encountered a problem. Please retry later or contact the site admin."
        //         ], 404);
        //     }
        // });

        $this->renderable(function (QueryException $e, $request) {
            if ($request->is('api/*') || $request->is('admin/*')) {
                $errorCode = $e->errorInfo[1];
                switch ($errorCode) {
                    case 1062: //code dublicate entry
                        return response([
                            'message' => 'Duplicate Entry',
                            "from" => "handler",
                        ], Response::HTTP_NOT_FOUND);
                        break;
                    case 1452: //Cannot add or update a child row
                        return response([
                            'message' => trans("messages.SOMETHING_WENT_WRONG"),
                            "from" => "handler",
                        ], Response::HTTP_NOT_FOUND);
                        break;
                    case 1364: // you can handel any other error
                        return response([
                            "exception" => $e,
                            'message' => $e->getMessage(),
                            "from" => "handler",
                        ], Response::HTTP_NOT_FOUND);
                        break;
                    case '42S22':
                    case 1054: // you can handel any other error
                        return response([
                            "exception" => $e,
                            'message' => "Database Query Issue",
                            "from" => "handler",
                        ], Response::HTTP_NOT_FOUND);
                        break;
                }
            }
        });

        $this->renderable(function (Exception $e, $request) {
            dd($e);
         if ($request->is('api/*') || $request->is('admin/*')) {

                return response()->json([

                    'message' => $e->getMessage(),  "from" => "handler Exception",
                ], 404);
            }
        });
    }
}
