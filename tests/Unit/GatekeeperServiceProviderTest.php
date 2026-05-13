<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Event;
use LaraArabDev\FilamentGatekeeper\Gatekeeper;
use LaraArabDev\FilamentGatekeeper\Models\Permission;
use LaraArabDev\FilamentGatekeeper\Models\Role;
use LaraArabDev\FilamentGatekeeper\Services\PermissionCache;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;

class GatekeeperServiceProviderTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_registers_gatekeeper_as_singleton(): void
    {
        $instance1 = app(Gatekeeper::class);
        $instance2 = app(Gatekeeper::class);

        $this->assertInstanceOf(Gatekeeper::class, $instance1);
        $this->assertSame($instance1, $instance2);
    }

    /** @test */
    public function it_registers_permission_cache_as_singleton(): void
    {
        $instance1 = app(PermissionCache::class);
        $instance2 = app(PermissionCache::class);

        $this->assertInstanceOf(PermissionCache::class, $instance1);
        $this->assertSame($instance1, $instance2);
    }

    /** @test */
    public function it_registers_gatekeeper_api_middleware_alias(): void
    {
        $router = app(Router::class);
        $middleware = $router->getMiddleware();

        $this->assertArrayHasKey('gatekeeper.api', $middleware);
        $this->assertSame(
            \LaraArabDev\FilamentGatekeeper\Http\Middleware\GatekeeperApiMiddleware::class,
            $middleware['gatekeeper.api']
        );
    }

    /** @test */
    public function it_registers_gatekeeper_resource_middleware_alias(): void
    {
        $router = app(Router::class);
        $middleware = $router->getMiddleware();

        $this->assertArrayHasKey('gatekeeper.resource', $middleware);
        $this->assertSame(
            \LaraArabDev\FilamentGatekeeper\Http\Middleware\GatekeeperResourceMiddleware::class,
            $middleware['gatekeeper.resource']
        );
    }

    /** @test */
    public function it_invalidates_role_cache_when_role_is_saved(): void
    {
        $role = Role::factory()->create(['name' => 'test-role']);

        // Mock the PermissionCache
        $cacheMock = $this->mock(PermissionCache::class);
        $cacheMock->shouldReceive('invalidateRole')->once()->with(\Mockery::type(Role::class));
        $this->app->instance(PermissionCache::class, $cacheMock);

        // Fire the event
        Event::dispatch('eloquent.saved: ' . Role::class, $role);
    }

    /** @test */
    public function it_invalidates_role_cache_when_role_is_deleted(): void
    {
        $role = Role::factory()->create(['name' => 'test-role-del']);

        $cacheMock = $this->mock(PermissionCache::class);
        $cacheMock->shouldReceive('invalidateRole')->once()->with(\Mockery::type(Role::class));
        $this->app->instance(PermissionCache::class, $cacheMock);

        Event::dispatch('eloquent.deleted: ' . Role::class, $role);
    }

    /** @test */
    public function it_invalidates_all_cache_when_permission_is_saved(): void
    {
        $cacheMock = $this->mock(PermissionCache::class);
        $cacheMock->shouldReceive('invalidateAll')->once();
        $this->app->instance(PermissionCache::class, $cacheMock);

        Event::dispatch('eloquent.saved: ' . Permission::class);
    }

    /** @test */
    public function it_invalidates_all_cache_when_permission_is_deleted(): void
    {
        $cacheMock = $this->mock(PermissionCache::class);
        $cacheMock->shouldReceive('invalidateAll')->once();
        $this->app->instance(PermissionCache::class, $cacheMock);

        Event::dispatch('eloquent.deleted: ' . Permission::class);
    }
}
