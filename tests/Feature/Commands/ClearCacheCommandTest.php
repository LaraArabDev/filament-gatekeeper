<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Feature\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use LaraArabDev\FilamentGatekeeper\Services\PermissionCache;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;
use Mockery;

class ClearCacheCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('cache.default', 'array');
    }

    /** @test */
    public function it_calls_invalidate_all_on_permission_cache(): void
    {
        $mock = Mockery::mock(PermissionCache::class);
        $mock->shouldReceive('invalidateAll')->once();
        $mock->shouldReceive('getStats')->andReturn([
            'prefix' => 'shield',
            'ttl' => 3600,
            'driver' => 'array',
            'supports_tagging' => true,
        ]);
        $this->app->instance(PermissionCache::class, $mock);

        $this->artisan('gatekeeper:clear-cache')->assertExitCode(0);
    }

    /** @test */
    public function it_outputs_success_message_after_clearing_cache(): void
    {
        $this->artisan('gatekeeper:clear-cache')
            ->expectsOutputToContain('Gatekeeper cache cleared')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_clears_cache_keys_stored_via_remember(): void
    {
        $cache = app(PermissionCache::class);

        // Store a value and confirm it is cached (callback runs only once)
        $callCount = 0;
        $cache->remember('test-key', function () use (&$callCount) {
            $callCount++;
            return 'test-value';
        });
        $this->assertSame(1, $callCount);

        // Run the clear-cache command
        $this->artisan('gatekeeper:clear-cache')->assertExitCode(0);

        // After clearing, the callback must run again (cache miss)
        $cache->remember('test-key', function () use (&$callCount) {
            $callCount++;
            return 'fresh-value';
        });
        $this->assertSame(2, $callCount, 'Cache was not cleared — callback was not invoked after clear');
    }

    /** @test */
    public function it_clears_cache_for_specific_user(): void
    {
        $user = $this->createUser();

        $mock = Mockery::mock(PermissionCache::class);
        $mock->shouldReceive('invalidateUser')->once()->with(
            Mockery::on(fn ($arg) => $arg->getKey() === $user->getKey())
        );
        $this->app->instance(PermissionCache::class, $mock);

        $this->artisan('gatekeeper:clear-cache', ['--user' => $user->id])
            ->assertExitCode(0);
    }

    /** @test */
    public function it_does_not_throw_when_cache_is_already_empty(): void
    {
        Cache::flush();

        $this->artisan('gatekeeper:clear-cache')->assertExitCode(0);
    }
}
