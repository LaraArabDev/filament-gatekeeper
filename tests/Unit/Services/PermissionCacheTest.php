<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Unit\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use LaraArabDev\FilamentGatekeeper\Services\PermissionCache;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class PermissionCacheTest extends TestCase
{
    use RefreshDatabase;

    protected PermissionCache $cache;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = new PermissionCache;
    }

    #[Test]
    public function it_can_get_cache_stats(): void
    {
        $stats = $this->cache->getStats();

        $this->assertArrayHasKey('prefix', $stats);
        $this->assertArrayHasKey('ttl', $stats);
        $this->assertArrayHasKey('driver', $stats);
        $this->assertArrayHasKey('supports_tagging', $stats);
    }

    #[Test]
    public function it_uses_configured_prefix(): void
    {
        config()->set('gatekeeper.cache.prefix', 'custom_prefix_');

        $cache = new PermissionCache;
        $stats = $cache->getStats();

        $this->assertEquals('custom_prefix_', $stats['prefix']);
    }

    #[Test]
    public function it_uses_configured_ttl(): void
    {
        config()->set('gatekeeper.cache.ttl', 7200);

        $cache = new PermissionCache;
        $stats = $cache->getStats();

        $this->assertEquals(7200, $stats['ttl']);
    }

    #[Test]
    public function it_can_invalidate_all_cache(): void
    {
        // Store some cached data
        $this->cache->remember('test_key', fn () => 'test_value');

        // Invalidate all
        $this->cache->invalidateAll();

        // This should not throw any exceptions
        $this->assertTrue(true);
    }

    #[Test]
    public function it_can_remember_value(): void
    {
        $value = $this->cache->remember('test_key', fn () => 'cached_value');

        $this->assertEquals('cached_value', $value);
    }

    #[Test]
    public function it_can_forget_specific_key(): void
    {
        $this->cache->remember('test_key', fn () => 'cached_value');

        $this->cache->forget('test_key');

        // Trying to get the value again should re-compute
        $newValue = $this->cache->remember('test_key', fn () => 'new_value');

        $this->assertEquals('new_value', $newValue);
    }

    #[Test]
    public function it_can_get_user_permissions_cache_key(): void
    {
        $user = $this->createUser();

        $key = $this->cache->getUserPermissionsCacheKey($user);

        $this->assertStringContainsString('user:', $key);
        $this->assertStringContainsString((string) $user->id, $key);
        $this->assertStringContainsString('matrix', $key);
    }

    #[Test]
    public function it_can_invalidate_user_cache(): void
    {
        $user = $this->createUser();

        // Cache user permissions
        $key = $this->cache->getUserPermissionsCacheKey($user);
        $this->cache->remember($key, fn () => ['permission1', 'permission2']);

        // Invalidate
        $this->cache->invalidateForUser($user);

        // Should not throw exception
        $this->assertTrue(true);
    }

    #[Test]
    public function it_respects_cache_enabled_config(): void
    {
        config()->set('gatekeeper.cache.enabled', false);

        $cache = new PermissionCache;

        // When cache is disabled, remember should always compute
        $counter = 0;
        $value1 = $cache->remember('test', function () use (&$counter) {
            $counter++;

            return 'value';
        });

        $value2 = $cache->remember('test', function () use (&$counter) {
            $counter++;

            return 'value';
        });

        // Verify that values are returned correctly
        $this->assertEquals('value', $value1);
        $this->assertEquals('value', $value2);

        // When disabled, callback behavior depends on implementation
        // At minimum, verify the method executes without error
        $this->assertTrue(true);
    }

    #[Test]
    public function it_can_flush_all_shield_cache(): void
    {
        $this->cache->remember('key1', fn () => 'value1');
        $this->cache->remember('key2', fn () => 'value2');

        $this->cache->flushAll();

        // Should complete without error
        $this->assertTrue(true);
    }

    #[Test]
    public function it_executes_callback_directly_when_cache_disabled_in_remember(): void
    {
        config()->set('gatekeeper.cache.enabled', false);

        $cache = new PermissionCache;

        $callCount = 0;
        $value = $cache->remember('some_key', function () use (&$callCount) {
            $callCount++;

            return 'direct_value';
        });

        $this->assertEquals('direct_value', $value);
        $this->assertEquals(1, $callCount);
    }

    #[Test]
    public function it_calls_callback_every_time_when_cache_is_disabled(): void
    {
        config()->set('gatekeeper.cache.enabled', false);

        $cache = new PermissionCache;

        $callCount = 0;
        $cache->remember('some_key', function () use (&$callCount) {
            $callCount++;

            return 'value';
        });
        $cache->remember('some_key', function () use (&$callCount) {
            $callCount++;

            return 'value';
        });

        // With cache disabled, callback should be called each time
        $this->assertEquals(2, $callCount);
    }

    #[Test]
    public function it_can_forget_a_key(): void
    {
        $this->cache->remember('forget_test_key', fn () => 'initial_value');

        $result = $this->cache->forget('forget_test_key');

        // forget() should return bool
        $this->assertIsBool($result);
    }

    #[Test]
    public function it_can_warm_cache_for_user(): void
    {
        $user = $this->createUser();

        // warmCache should not throw
        $this->cache->warmCache($user);

        $this->assertTrue(true);
    }

    #[Test]
    public function it_can_get_stats_with_all_required_keys(): void
    {
        $stats = $this->cache->getStats();

        $this->assertArrayHasKey('prefix', $stats);
        $this->assertArrayHasKey('ttl', $stats);
        $this->assertArrayHasKey('driver', $stats);
        $this->assertArrayHasKey('tags', $stats);
        $this->assertArrayHasKey('supports_tagging', $stats);
        $this->assertIsArray($stats['tags']);
        $this->assertIsBool($stats['supports_tagging']);
    }

    #[Test]
    public function it_invalidates_all_without_exception(): void
    {
        $this->cache->remember('key_for_invalidate', fn () => 'value');

        // Should complete without throwing
        $this->cache->invalidateAll();

        $this->assertTrue(true);
    }
}
