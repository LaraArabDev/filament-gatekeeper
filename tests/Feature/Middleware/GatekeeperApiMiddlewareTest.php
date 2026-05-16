<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Feature\Middleware;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use LaraArabDev\FilamentGatekeeper\Http\Middleware\GatekeeperApiMiddleware;
use LaraArabDev\FilamentGatekeeper\Models\Permission;
use LaraArabDev\FilamentGatekeeper\Models\Role;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;

class GatekeeperApiMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected GatekeeperApiMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        $this->middleware = new GatekeeperApiMiddleware;
    }

    /** @test */
    public function it_allows_request_with_permission(): void
    {
        $user = $this->createUser();

        // Create permission with web guard (matching user's default guard)
        $permission = Permission::factory()->resource()->forGuard('web')->create([
            'name' => 'view_any_user',
        ]);

        // Give permission to user (uses web guard by default)
        $user->givePermissionTo($permission);

        // Act as user with web guard (matching permission guard)
        $this->actingAs($user, 'web');

        $request = Request::create('/api/users', 'GET');

        // Use web guard to match permission and user authentication
        $response = $this->middleware->handle($request, function () {
            return new Response('OK');
        }, 'view_any_user', 'web');

        $this->assertEquals('OK', $response->getContent());
    }

    /** @test */
    public function it_denies_request_without_permission(): void
    {
        $user = $this->createUser();

        // Act as user with web guard
        $this->actingAs($user, 'web');

        $request = Request::create('/api/users', 'GET');

        $this->expectException(AuthorizationException::class);

        // Use web guard to match user authentication
        $this->middleware->handle($request, function () {
            return new Response('OK');
        }, 'view_any_user', 'web');
    }

    /** @test */
    public function it_allows_super_admin(): void
    {
        $user = $this->createSuperAdmin();

        // Act as user with web guard (matching role guard)
        $this->actingAs($user, 'web');

        $request = Request::create('/api/users', 'GET');

        // Use web guard to match role guard
        $response = $this->middleware->handle($request, function () {
            return new Response('OK');
        }, 'view_any_user', 'web');

        $this->assertEquals('OK', $response->getContent());
    }

    /** @test */
    public function it_denies_unauthenticated_request(): void
    {
        $request = Request::create('/api/users', 'GET');

        $this->expectException(AuthenticationException::class);

        $this->middleware->handle($request, function () {
            return new Response('OK');
        }, 'view_any_user');
    }

    /** @test */
    public function it_can_check_multiple_permissions_with_or(): void
    {
        $user = $this->createUser();

        // Create permission with web guard (matching user's default guard)
        $permission = Permission::factory()->resource()->forGuard('web')->create([
            'name' => 'view_any_user',
        ]);

        // Give permission to user (uses web guard by default)
        $user->givePermissionTo($permission);

        // Act as user with web guard (matching permission guard)
        $this->actingAs($user, 'web');

        $request = Request::create('/api/users', 'GET');

        // User has view_any_user but not create_user
        // With OR logic, should pass
        // Use web guard to match permission and user authentication
        $response = $this->middleware->handle($request, function () {
            return new Response('OK');
        }, 'view_any_user|create_user', 'web');

        $this->assertEquals('OK', $response->getContent());
    }
}
