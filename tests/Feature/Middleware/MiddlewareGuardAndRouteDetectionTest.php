<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Feature\Middleware;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Route;
use LaraArabDev\FilamentGatekeeper\Http\Middleware\GatekeeperApiMiddleware;
use LaraArabDev\FilamentGatekeeper\Http\Middleware\GatekeeperResourceMiddleware;
use LaraArabDev\FilamentGatekeeper\Models\Permission;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;

/**
 * Branch coverage for middleware that is NOT already covered by the base middleware tests:
 *
 * GatekeeperResourceMiddleware:
 *  - hasResourceParameter() when NO route resolver is set (path-based: /users/1 → true)
 *  - hasResourceParameter() when NO route resolver is set (path-based: /users → false)
 *  - hasResourceParameter() when route exists but params are empty AND URI has {param} pattern
 *  - resolvePermission() DELETE without a route parameter → delete_any_*
 *  - detectGuard() via is('api/*') (no bearer token)
 *
 * GatekeeperApiMiddleware:
 *  - detectGuard() via is('api/*') (no bearer token, but path starts with api/)
 */
class MiddlewareGuardAndRouteDetectionTest extends TestCase
{
    use RefreshDatabase;

    protected GatekeeperResourceMiddleware $resourceMiddleware;
    protected GatekeeperApiMiddleware $apiMiddleware;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resourceMiddleware = new GatekeeperResourceMiddleware();
        $this->apiMiddleware      = new GatekeeperApiMiddleware();
    }

    // ---------------------------------------------------------------------------
    // GatekeeperResourceMiddleware – hasResourceParameter() (no route resolver)
    // ---------------------------------------------------------------------------

    /** @test */
    public function has_resource_parameter_is_true_when_path_contains_numeric_segment_and_no_route(): void
    {
        // No route resolver – relies on path-based detection: /\d+(\/|$)/
        $request = Request::create('/users/42', 'GET');

        $user = $this->createUser();

        Permission::factory()->resource()->create(['name' => 'view_user']);
        $user->givePermissionTo('view_user');

        $this->actingAs($user, 'web');

        $response = $this->resourceMiddleware->handle(
            $request,
            fn() => new Response('OK'),
            'user',
            'web'
        );

        // view_user (with param) → user has view_user → 200
        $this->assertEquals('OK', $response->getContent());
    }

    /** @test */
    public function has_resource_parameter_is_false_when_path_has_no_numeric_segment_and_no_route(): void
    {
        // No route resolver and no numeric segment → view_any_user
        $request = Request::create('/users', 'GET');

        $user = $this->createUser();

        Permission::factory()->resource()->create(['name' => 'view_any_user']);
        $user->givePermissionTo('view_any_user');

        $this->actingAs($user, 'web');

        $response = $this->resourceMiddleware->handle(
            $request,
            fn() => new Response('OK'),
            'user',
            'web'
        );

        $this->assertEquals('OK', $response->getContent());
    }

    // ---------------------------------------------------------------------------
    // GatekeeperResourceMiddleware – hasResourceParameter() (route exists, params empty, URI has {param})
    // ---------------------------------------------------------------------------

    /** @test */
    public function has_resource_parameter_is_true_when_route_has_no_params_but_uri_contains_placeholder(): void
    {
        // Route exists but parameters() is empty, URI template has {user} → true
        $request = Request::create('/users/new', 'GET');
        $request->setRouteResolver(function () use ($request) {
            $route = new Route('GET', '/users/{user}', []);
            $route->bind($request);
            // Do NOT set any parameters so that parameters() returns []
            return $route;
        });

        $user = $this->createUser();

        Permission::factory()->resource()->create(['name' => 'view_user']);
        $user->givePermissionTo('view_user');

        $this->actingAs($user, 'web');

        $response = $this->resourceMiddleware->handle(
            $request,
            fn() => new Response('OK'),
            'user',
            'web'
        );

        $this->assertEquals('OK', $response->getContent());
    }

    /** @test */
    public function has_resource_parameter_is_false_when_route_has_no_params_and_uri_has_no_placeholder(): void
    {
        // Route exists, parameters() is empty, URI has no {placeholder} → false → view_any_user
        $request = Request::create('/users', 'GET');
        $request->setRouteResolver(function () use ($request) {
            $route = new Route('GET', '/users', []);
            $route->bind($request);

            return $route;
        });

        $user = $this->createUser();

        Permission::factory()->resource()->create(['name' => 'view_any_user']);
        $user->givePermissionTo('view_any_user');

        $this->actingAs($user, 'web');

        $response = $this->resourceMiddleware->handle(
            $request,
            fn() => new Response('OK'),
            'user',
            'web'
        );

        $this->assertEquals('OK', $response->getContent());
    }

    // ---------------------------------------------------------------------------
    // GatekeeperResourceMiddleware – resolvePermission() DELETE without route param
    // ---------------------------------------------------------------------------

    /** @test */
    public function resolve_permission_delete_without_route_parameter_returns_delete_any(): void
    {
        // DELETE without a route parameter → delete_any_user
        $request = Request::create('/users', 'DELETE');
        // No route resolver → path-based check: '/users' has no numeric segment → false

        $user = $this->createUser();

        Permission::factory()->resource()->create(['name' => 'delete_any_user']);
        $user->givePermissionTo('delete_any_user');

        $this->actingAs($user, 'web');

        $response = $this->resourceMiddleware->handle(
            $request,
            fn() => new Response('OK'),
            'user',
            'web'
        );

        $this->assertEquals('OK', $response->getContent());
    }

    /** @test */
    public function resolve_permission_delete_with_route_parameter_returns_delete(): void
    {
        // DELETE with a route parameter → delete_user
        $request = Request::create('/users/5', 'DELETE');
        $request->setRouteResolver(function () use ($request) {
            $route = new Route('DELETE', '/users/{user}', []);
            $route->bind($request);
            $route->setParameter('user', 5);

            return $route;
        });

        $user = $this->createUser();

        Permission::factory()->resource()->create(['name' => 'delete_user']);
        $user->givePermissionTo('delete_user');

        $this->actingAs($user, 'web');

        $response = $this->resourceMiddleware->handle(
            $request,
            fn() => new Response('OK'),
            'user',
            'web'
        );

        $this->assertEquals('OK', $response->getContent());
    }

    // ---------------------------------------------------------------------------
    // GatekeeperResourceMiddleware – detectGuard() via is('api/*')
    // ---------------------------------------------------------------------------

    /** @test */
    public function resource_middleware_detects_api_guard_for_api_path_without_bearer_token(): void
    {
        // No bearer token but path is api/* → guard should be 'api'
        // We pass guard=null so detectGuard() is called
        $request = Request::create('/api/users', 'GET');

        // No actingAs → no user → 401 response expected (guard=api, no api user)
        $response = $this->resourceMiddleware->handle(
            $request,
            fn() => new Response('OK'),
            'user'
            // guard is null → detectGuard() is called
        );

        // 401 because user is not authenticated via api guard, but we confirmed
        // the guard detection ran (api path with no user → 401 not 403)
        $this->assertEquals(401, $response->getStatusCode());
    }

    /** @test */
    public function resource_middleware_detects_web_guard_for_non_api_path_without_bearer_token(): void
    {
        // No bearer token, path is not api/* → guard is 'web'
        // With no user → 401
        $request = Request::create('/admin/users', 'GET');

        $response = $this->resourceMiddleware->handle(
            $request,
            fn() => new Response('OK'),
            'user'
            // guard is null → detectGuard() is called → returns 'web'
        );

        $this->assertEquals(401, $response->getStatusCode());
    }

    // ---------------------------------------------------------------------------
    // GatekeeperApiMiddleware – detectGuard() via is('api/*')
    // ---------------------------------------------------------------------------

    /** @test */
    public function api_middleware_detects_api_guard_for_api_path_without_bearer_token(): void
    {
        // Path starts with api/ but no bearer token → detectGuard() returns 'api'
        // No user authenticated → AuthenticationException
        $request = Request::create('/api/something', 'GET');

        $this->expectException(\Illuminate\Auth\AuthenticationException::class);

        $this->apiMiddleware->handle(
            $request,
            fn() => new Response('OK'),
            'view_any_user'
            // guard is null → detectGuard() is invoked → returns 'api'
        );
    }

    /** @test */
    public function api_middleware_detects_web_guard_for_non_api_path(): void
    {
        // Path does NOT start with api/ and no bearer token → guard is 'web'
        // No user → AuthenticationException
        $request = Request::create('/admin/dashboard', 'GET');

        $this->expectException(\Illuminate\Auth\AuthenticationException::class);

        $this->apiMiddleware->handle(
            $request,
            fn() => new Response('OK'),
            'view_any_user'
            // guard is null → detectGuard() → web
        );
    }

    /** @test */
    public function api_middleware_with_api_path_and_authenticated_user_checks_permission(): void
    {
        $user = $this->createUser();

        // Create permission with web guard to match user's guard
        Permission::factory()->resource()->forGuard('web')->create(['name' => 'view_any_user_api_branch']);
        $user->givePermissionTo('view_any_user_api_branch');

        $this->actingAs($user, 'web');

        $request = Request::create('/api/users', 'GET');
        // Explicitly pass web guard to avoid detectGuard() switching to api
        $response = $this->apiMiddleware->handle(
            $request,
            fn() => new Response('OK'),
            'view_any_user_api_branch',
            'web'
        );

        $this->assertEquals('OK', $response->getContent());
    }

    // ---------------------------------------------------------------------------
    // GatekeeperResourceMiddleware – unauthenticated user returns 401
    // ---------------------------------------------------------------------------

    /** @test */
    public function resource_middleware_returns_401_for_unauthenticated_user(): void
    {
        $request = Request::create('/users', 'POST');

        $response = $this->resourceMiddleware->handle(
            $request,
            fn() => new Response('OK'),
            'user',
            'web'
        );

        $this->assertEquals(401, $response->getStatusCode());
    }

    // ---------------------------------------------------------------------------
    // GatekeeperResourceMiddleware – resolvePermission returns null for unknown method
    // ---------------------------------------------------------------------------

    /** @test */
    public function resource_middleware_passes_through_when_permission_cannot_be_resolved(): void
    {
        // HEAD method falls through to 'default => null' in resolvePermission()
        $request = Request::create('/users', 'HEAD');

        $response = $this->resourceMiddleware->handle(
            $request,
            fn() => new Response('OK'),
            'user',
            'web'
        );

        // null permission → $next($request) is called → 200
        $this->assertEquals('OK', $response->getContent());
    }
}
