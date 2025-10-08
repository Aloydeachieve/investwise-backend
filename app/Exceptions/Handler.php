<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Helpers\ApiResponse;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
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
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $e
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $e)
    {
        // Handle API requests with JSON responses
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->handleApiException($request, $e);
        }

        return parent::render($request, $e);
    }

    /**
     * Handle API exceptions with standardized JSON responses
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $e
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleApiException($request, Throwable $e)
    {
        // Handle validation exceptions
        if ($e instanceof ValidationException) {
            return ApiResponse::validationError(
                $e->errors(),
                'The given data was invalid.'
            );
        }

        // Handle authentication exceptions
        if ($e instanceof AuthenticationException) {
            return ApiResponse::unauthorized('Authentication required.');
        }

        // Handle model not found exceptions
        if ($e instanceof ModelNotFoundException) {
            return ApiResponse::notFound('Resource not found.');
        }

        // Handle not found HTTP exceptions
        if ($e instanceof NotFoundHttpException) {
            return ApiResponse::notFound('The requested resource was not found.');
        }

        // Handle method not allowed exceptions
        if ($e instanceof MethodNotAllowedHttpException) {
            return ApiResponse::error('Method not allowed.', 405);
        }

        // Handle HTTP exceptions
        if ($e instanceof HttpException) {
            $statusCode = $e->getStatusCode();

            switch ($statusCode) {
                case 401:
                    return ApiResponse::unauthorized($e->getMessage() ?: 'Unauthorized.');
                case 403:
                    return ApiResponse::forbidden($e->getMessage() ?: 'Forbidden.');
                case 404:
                    return ApiResponse::notFound($e->getMessage() ?: 'Not found.');
                case 422:
                    return ApiResponse::error($e->getMessage() ?: 'Unprocessable entity.', 422);
                default:
                    return ApiResponse::error($e->getMessage() ?: 'HTTP error.', $statusCode);
            }
        }

        // Handle all other exceptions
        if (app()->environment(['production'])) {
            // In production, don't expose internal error details
            return ApiResponse::serverError('An unexpected error occurred.');
        } else {
            // In development, show detailed error information
            return ApiResponse::error(
                $e->getMessage() ?: 'An unexpected error occurred.',
                500,
                [],
                [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTrace()
                ]
            );
        }
    }
}
