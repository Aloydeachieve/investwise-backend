<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Helpers\ApiResponse;

class ApiResponseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Only transform JSON responses for API routes
        if ($request->is('api/*') && $response->headers->get('content-type') === 'application/json') {
            $data = json_decode($response->getContent(), true);

            // If response doesn't already have our standardized structure
            if (!isset($data['success'])) {
                // Check if it's an error response (has errors or specific status codes)
                if ($response->getStatusCode() >= 400 || isset($data['errors'])) {
                    // Transform error response
                    $message = $data['message'] ?? 'An error occurred';
                    $errors = $data['errors'] ?? [];

                    return ApiResponse::error($message, $response->getStatusCode(), $errors);
                } else {
                    // Transform success response
                    $message = $data['message'] ?? 'Success';
                    $responseData = $data;

                    // Remove message from data if it exists to avoid duplication
                    if (isset($responseData['message'])) {
                        unset($responseData['message']);
                    }

                    // If data is empty or just contains message, return null data
                    if (empty($responseData) || (count($responseData) === 1 && isset($responseData['message']))) {
                        $responseData = null;
                    }

                    return ApiResponse::success($responseData, $message, $response->getStatusCode());
                }
            }
        }

        return $response;
    }
}
