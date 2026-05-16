<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Feature\Middleware;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Route;
use LaraArabDev\FilamentGatekeeper\Http\Middleware\GatekeeperResourceMiddleware;
use LaraArabDev\FilamentGatekeeper\Models\Permission;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;

class GatekeeperResourceMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected GatekeeperResourceMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        $this->middleware = new GatekeeperResourceMiddleware;
    }

    /** @test */
    public function it_allows_get_index_request_with_view_any_permission(): void
    {
        $user = $this->createUser();

        Permission::factory()->resource()->create([
            'name' => 'view_any_user',
        ]);

        $user->givePermissionTo('view_any_user');

        $this->actingAs($user, 'web');

        // Use non-API route to avoid auto-detection of 'api' guard
        $request = Request::create('/users', 'GET');

        // Explicitly pass 'web' guard to match user authentication
        $response = $this->middleware->handle($request, function () {
            return new Response('OK');
        }, 'user', 'web');

        $this->assertEquals('OK', $response->getContent());
    }

    /** @test */
    public function it_allows_get_show_request_with_view_permission(): void
    {
        $user = $this->createUser();

        Permission::factory()->resource()->create([
            'name' => 'view_user',
        ]);

        $user->givePermissionTo('view_user');

        $this->actingAs($user, 'web');

        // Request with an ID in the route
        $request = Request::create('/users/1', 'GET');
        $request->setRouteResolver(function () {
            $route = new Route('GET', '/users/{user}', []);
            $route->bind(Request::create('/users/1', 'GET'));
            $route->setParameter('user', 1);

            return $route;
        });

        // Explicitly pass 'web' guard to match user authentication
        $response = $this->middleware->handle($request, function () {
            return new Response('OK');
        }, 'user', 'web');

        $this->assertEquals('OK', $response->getContent());
    }

    /** @test */
    public function it_allows_post_request_with_create_permission(): void
    {
        $user = $this->createUser();

        Permission::factory()->resource()->create([
            'name' => 'create_user',
        ]);

        $user->givePermissionTo('create_user');

        $this->actingAs($user, 'web');

        // Use non-API route to avoid auto-detection of 'api' guard
        $request = Request::create('/users', 'POST');

        // Explicitly pass 'web' guard to match user authentication
        $response = $this->middleware->handle($request, function () {
            return new Response('OK');
        }, 'user', 'web');

        $this->assertEquals('OK', $response->getContent());
    }

    /** @test */
    public function it_allows_put_request_with_update_permission(): void
    {
        $user = $this->createUser();

        Permission::factory()->resource()->create([
            'name' => 'update_user',
        ]);

        $user->givePermissionTo('update_user');

        $this->actingAs($user, 'web');

        // Use non-API route to avoid auto-detection of 'api' guard
        $request = Request::create('/users/1', 'PUT');

        // Explicitly pass 'web' guard to match user authentication
        $response = $this->middleware->handle($request, function () {
            return new Response('OK');
        }, 'user', 'web');

        $this->assertEquals('OK', $response->getContent());
    }

    /** @test */
    public function it_allows_patch_request_with_update_permission(): void
    {
        $user = $this->createUser();

        Permission::factory()->resource()->create([
            'name' => 'update_user',
        ]);

        $user->givePermissionTo('update_user');

        $this->actingAs($user, 'web');

        // Use non-API route to avoid auto-detection of 'api' guard
        $request = Request::create('/users/1', 'PATCH');

        // Explicitly pass 'web' guard to match user authentication
        $response = $this->middleware->handle($request, function () {
            return new Response('OK');
        }, 'user', 'web');

        $this->assertEquals('OK', $response->getContent());
    }

    /** @test */
    public function it_allows_delete_request_with_delete_permission(): void
    {
        $user = $this->createUser();

        Permission::factory()->resource()->create([
            'name' => 'delete_user',
        ]);

        $user->givePermissionTo('delete_user');

        $this->actingAs($user, 'web');

        // Use non-API route to avoid auto-detection of 'api' guard
        $request = Request::create('/users/1', 'DELETE');
        // Set route with parameter to ensure delete_user (not delete_any_user) is used
        $request->setRouteResolver(function () use ($request) {
            $route = new Route('DELETE', '/users/{user}', []);
            // Bind the route properly
            $route->bind($request);

            return $route;
        });

        // Explicitly pass 'web' guard to match user authentication
        $response = $this->middleware->handle($request, function () {
            return new Response('OK');
        }, 'user', 'web');

        $this->assertEquals('OK', $response->getContent());
    }

    /** @test */
    public function it_denies_post_request_without_create_permission(): void
    {
        $user = $this->createUser();

        $this->actingAs($user, 'web');

        // Use non-API route to avoid auto-detection of 'api' guard
        $request = Request::create('/users', 'POST');

        // Explicitly pass 'web' guard to match user authentication
        // Middleware returns JSON response instead of throwing exception
        $response = $this->middleware->handle($request, function () {
            return new Response('OK');
        }, 'user', 'web');

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('message', $content);
        $this->assertArrayHasKey('permission', $content);
        $this->assertEquals('create_user', $content['permission']);
    }

    /** @test */
    public function it_allows_super_admin_for_all_methods(): void
    {
        $user = $this->createSuperAdmin();

        $this->actingAs($user, 'web');

        $methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

        foreach ($methods as $method) {
            // Use non-API route to avoid auto-detection of 'api' guard
            $request = Request::create('/users/1', $method);

            // Explicitly pass 'web' guard to match user authentication
            $response = $this->middleware->handle($request, function () {
                return new Response('OK');
            }, 'user', 'web');

            $this->assertEquals('OK', $response->getContent(), "Failed for method: {$method}");
        }
    }
}
