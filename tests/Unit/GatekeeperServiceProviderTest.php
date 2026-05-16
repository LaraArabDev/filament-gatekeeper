<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use LaraArabDev\FilamentGatekeeper\Gatekeeper;
use LaraArabDev\FilamentGatekeeper\Http\Middleware\GatekeeperApiMiddleware;
use LaraArabDev\FilamentGatekeeper\Http\Middleware\GatekeeperResourceMiddleware;
use LaraArabDev\FilamentGatekeeper\Models\Permission;
use LaraArabDev\FilamentGatekeeper\Models\Role;
use LaraArabDev\FilamentGatekeeper\Services\PermissionCache;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class GatekeeperServiceProviderTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_registers_gatekeeper_as_singleton(): void
    {
        $instance1 = app(Gatekeeper::class);
        $instance2 = app(Gatekeeper::class);

        $this->assertInstanceOf(Gatekeeper::class, $instance1);
        $this->assertSame($instance1, $instance2);
    }

    #[Test]
    public function it_registers_permission_cache_as_singleton(): void
    {
        $instance1 = app(PermissionCache::class);
        $instance2 = app(PermissionCache::class);

        $this->assertInstanceOf(PermissionCache::class, $instance1);
        $this->assertSame($instance1, $instance2);
    }

    #[Test]
    public function it_registers_gatekeeper_api_middleware_alias(): void
    {
        $router = app(Router::class);
        $middleware = $router->getMiddleware();

        $this->assertArrayHasKey('gatekeeper.api', $middleware);
        $this->assertSame(
            GatekeeperApiMiddleware::class,
            $middleware['gatekeeper.api']
        );
    }

    #[Test]
    public function it_registers_gatekeeper_resource_middleware_alias(): void
    {
        $router = app(Router::class);
        $middleware = $router->getMiddleware();

        $this->assertArrayHasKey('gatekeeper.resource', $middleware);
        $this->assertSame(
            GatekeeperResourceMiddleware::class,
            $middleware['gatekeeper.resource']
        );
    }

    #[Test]
    public function it_invalidates_role_cache_when_role_is_saved(): void
    {
        $role = Role::factory()->create(['name' => 'test-role']);

        // Mock the PermissionCache
        $cacheMock = $this->mock(PermissionCache::class);
        $cacheMock->shouldReceive('invalidateRole')->once()->with(\Mockery::type(Role::class));
        $this->app->instance(PermissionCache::class, $cacheMock);

        // Fire the event
        Event::dispatch('eloquent.saved: '.Role::class, $role);
    }

    #[Test]
    public function it_invalidates_role_cache_when_role_is_deleted(): void
    {
        $role = Role::factory()->create(['name' => 'test-role-del']);

        $cacheMock = $this->mock(PermissionCache::class);
        $cacheMock->shouldReceive('invalidateRole')->once()->with(\Mockery::type(Role::class));
        $this->app->instance(PermissionCache::class, $cacheMock);

        Event::dispatch('eloquent.deleted: '.Role::class, $role);
    }

    #[Test]
    public function it_invalidates_all_cache_when_permission_is_saved(): void
    {
        $cacheMock = $this->mock(PermissionCache::class);
        $cacheMock->shouldReceive('invalidateAll')->once();
        $this->app->instance(PermissionCache::class, $cacheMock);

        Event::dispatch('eloquent.saved: '.Permission::class);
    }

    #[Test]
    public function it_invalidates_all_cache_when_permission_is_deleted(): void
    {
        $cacheMock = $this->mock(PermissionCache::class);
        $cacheMock->shouldReceive('invalidateAll')->once();
        $this->app->instance(PermissionCache::class, $cacheMock);

        Event::dispatch('eloquent.deleted: '.Permission::class);
    }

    #[Test]
    public function it_allows_super_admin_via_gate_before_callback(): void
    {
        config()->set('gatekeeper.super_admin.enabled', true);
        config()->set('gatekeeper.super_admin.role', 'super-admin');

        $user = $this->createSuperAdmin();
        $this->actingAs($user);

        // Gate::allows uses Gate::before callback registered by service provider
        $this->assertTrue(Gate::allows('any-arbitrary-permission'));
    }

    #[Test]
    public function it_has_gate_before_callback_registered(): void
    {
        // Verify the app is booted and Gate policies can be checked
        $this->assertTrue(app()->isBooted());

        // The Gate before callback is registered during packageBooted
        // We verify it works by checking a super admin can pass through
        $user = $this->createSuperAdmin();
        $this->actingAs($user);

        $this->assertTrue(Gate::allows('any-permission-whatsoever'));
    }
}
