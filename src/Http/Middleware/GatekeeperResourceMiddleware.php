<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use LaraArabDev\FilamentGatekeeper\Facades\Gatekeeper;
use Symfony\Component\HttpFoundation\Response;

/**
 * Shield Resource Middleware
 *
 * Automatically applies CRUD permissions based on HTTP method.
 *
 * Usage:
 * Route::apiResource('users', UserController::class)->middleware('gatekeeper.resource:User');
 *
 * This will automatically check:
 * - GET /users         -> view_any_user
 * - GET /users/{id}    -> view_user
 * - POST /users        -> create_user
 * - PUT /users/{id}    -> update_user
 * - DELETE /users/{id} -> delete_user
 */
class GatekeeperResourceMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $model, ?string $guard = null): Response
    {
        $guard = $guard ?? $this->detectGuard($request);
        $permission = $this->resolvePermission($request, $model);

        if (! $permission) {
            return $next($request);
        }

        $user = Auth::guard($guard)->user();
        if (! $user) {
            return response()->json([
                'message' => __('gatekeeper::messages.messages.unauthorized'),
                'permission' => $permission,
            ], 401);
        }

        if (! Gatekeeper::guard($guard)->can($permission)) {
            return response()->json([
                'message' => __('gatekeeper::messages.messages.unauthorized'),
                'permission' => $permission,
            ], 403);
        }

        return $next($request);
    }

    /**
     * Resolve the permission based on HTTP method and route.
     */
    protected function resolvePermission(Request $request, string $model): ?string
    {
        $modelSnake = str($model)->snake()->toString();
        $method = strtoupper($request->method());
        $hasRouteParameter = $this->hasResourceParameter($request);

        return match (true) {
            $method === 'GET' && ! $hasRouteParameter => "view_any_{$modelSnake}",
            $method === 'GET' && $hasRouteParameter => "view_{$modelSnake}",
            $method === 'POST' => "create_{$modelSnake}",
            ($method === 'PUT' || $method === 'PATCH') => "update_{$modelSnake}",
            $method === 'DELETE' && ! $hasRouteParameter => "delete_any_{$modelSnake}",
            $method === 'DELETE' && $hasRouteParameter => "delete_{$modelSnake}",
            default => null,
        };
    }

    /**
     * Check if route has a resource parameter (e.g., /users/{user}).
     */
    protected function hasResourceParameter(Request $request): bool
    {
        $route = $request->route();

        if (! $route) {
            $path = $request->path();

            return (bool) preg_match('/\/\d+(\/|$)/', $path);
        }

        $parameters = $route->parameters();

        if (empty($parameters)) {
            $uri = $route->uri();

            return (bool) preg_match('/\{[^}]+\}/', $uri);
        }

        return ! empty($parameters);
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
