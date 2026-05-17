<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Http\Middleware;

use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use LaraArabDev\FilamentGatekeeper\Facades\Gatekeeper;
use Symfony\Component\HttpFoundation\Response;

/**
 * Shield API Middleware
 *
 * Usage in routes:
 * - Route::get('/users', ...)->middleware('gatekeeper.api:view_any_user');
 * - Route::post('/users', ...)->middleware('gatekeeper.api:create_user');
 * - Route::apiResource('users', UserController::class)->middleware('gatekeeper.api.resource:User');
 */
class GatekeeperApiMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission, ?string $guard = null): Response
    {
        $guard ??= $this->detectGuard($request);

        $user = Auth::guard($guard)->user();
        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        if (! Gatekeeper::guard($guard)->can($permission)) {
            throw new AuthorizationException(
                __('gatekeeper::messages.unauthorized')
            );
        }

        return $next($request);
    }

    /**
     * Detect the guard from the request.
     */
    protected function detectGuard(Request $request): string
    {
        if ($request->bearerToken() || $request->is('api/*')) {
            return 'api';
        }

        return config('gatekeeper.guard', 'web');
    }
}
