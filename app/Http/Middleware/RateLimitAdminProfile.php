<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class RateLimitAdminProfile
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            return $next($request);
        }

        $key = 'admin_profile:' . $user->id . ':' . $request->ip();

        // Different limits for different types of requests
        $limits = $this->getLimitsForRoute($request);

        $executed = RateLimiter::attempt(
            $key,
            $limits['max_attempts'],
            function () use ($request, $user, $limits) {
                // Log rate limited attempts
                \App\Models\AuditLog::log(
                    $user->id,
                    'rate_limited',
                    $user->id,
                    'User',
                    "Rate limited: {$limits['max_attempts']} attempts per {$limits['decay_minutes']} minutes",
                    $request->ip()
                );
            },
            $limits['decay_seconds']
        );

        if (!$executed) {
            return response()->json([
                'message' => 'Too many attempts. Please try again later.',
                'retry_after' => RateLimiter::availableIn($key),
            ], 429);
        }

        return $next($request);
    }

    /**
     * Get rate limits based on the route being accessed
     */
    private function getLimitsForRoute(Request $request): array
    {
        $routeName = $request->route() ? $request->route()->getName() : '';

        return match ($routeName) {
            // Profile viewing - more permissive
            'admin.profile' => [
                'max_attempts' => 100,
                'decay_minutes' => 5,
                'decay_seconds' => 300,
            ],

            // Personal info updates - moderate
            'admin.profile.update' => [
                'max_attempts' => 10,
                'decay_minutes' => 15,
                'decay_seconds' => 900,
            ],

            // Password changes - strict
            'admin.profile.password' => [
                'max_attempts' => 3,
                'decay_minutes' => 60,
                'decay_seconds' => 3600,
            ],

            // Email changes - very strict
            'admin.profile.email' => [
                'max_attempts' => 2,
                'decay_minutes' => 120,
                'decay_seconds' => 7200,
            ],

            // 2FA operations - moderate to strict
            'admin.profile.2fa.enable', 'admin.profile.2fa.verify' => [
                'max_attempts' => 5,
                'decay_minutes' => 30,
                'decay_seconds' => 1800,
            ],

            'admin.profile.2fa.disable' => [
                'max_attempts' => 3,
                'decay_minutes' => 60,
                'decay_seconds' => 3600,
            ],

            // Activity logs - permissive
            'admin.activity.logs' => [
                'max_attempts' => 200,
                'decay_minutes' => 5,
                'decay_seconds' => 300,
            ],

            // Email verification - moderate
            'admin.verify-email-change' => [
                'max_attempts' => 5,
                'decay_minutes' => 60,
                'decay_seconds' => 3600,
            ],

            // Default limits
            default => [
                'max_attempts' => 50,
                'decay_minutes' => 10,
                'decay_seconds' => 600,
            ],
        };
    }
}
