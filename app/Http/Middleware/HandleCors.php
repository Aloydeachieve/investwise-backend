<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleCors
{
  /** 
   * Handle an incoming request. 
   * 
   * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next 
   */
  public function handle(Request $request, Closure $next): Response
  {
    $response = $next($request);

    // Get the origin from the request 
    $origin = $request->header('Origin');

    // Define allowed origins 
    $allowedOrigins = [
      'http://localhost:3000',
      'http://localhost:3001',
      'http://127.0.0.1:3000',
      'https://investwise-frontend-8paj.vercel.app',
      'https://investwise-frontend-8paj-git-main-aloysius-dominics-projects.vercel.app',
      'https://investwise-frontend-8paj-mag8v2ryu-aloysius-dominics-projects.vercel.app',
      'https://investwise-frontend-8paj-git-dev-aloysius-dominics-projects.vercel.app',
      'https://investwise-frontend-8paj-jw01s3za5-aloysius-dominics-projects.vercel.app/',
    ];

    // Check if the origin is allowed 
    if ($origin && in_array($origin, $allowedOrigins)) {
      $response->headers->set('Access-Control-Allow-Origin', $origin);
    }

    // Set other CORS headers 
    $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
    $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With, Authorization, X-CSRF-TOKEN, X-XSRF-TOKEN, Accept, Origin');
    $response->headers->set('Access-Control-Allow-Credentials', 'true');
    $response->headers->set('Access-Control-Max-Age', '86400');
    return $response;
  }
}
