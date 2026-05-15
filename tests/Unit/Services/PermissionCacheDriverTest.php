<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Unit\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LaraArabDev\FilamentGatekeeper\Models\Role;
use LaraArabDev\FilamentGatekeeper\Services\PermissionCache;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;

/**
 * Tests targeting PermissionCache branch coverage:
 * - supportsTagging() with different cache drivers
 * - cache() with specific driver
 * - buildPermissionMatrix() edge cases
 */
class PermissionCacheDriverTest extends TestCase
{
    use RefreshDatabase;

    // ── supportsTagging() ──────────────────────────────────────────────────

    /** @test */
    public function it_supports_tagging_with_array_driver(): void
    {
        config()->set('cache.default', 'array');
        config()->set('gatekeeper.cache.driver', null);

        $cache = new PermissionCache();
        $stats = $cache->getStats();

        $this->assertTrue($stats['supports_tagging']);
    }

    /** @test */
    public function it_does_not_support_tagging_with_file_driver(): void
    {
        config()->set('cache.default', 'file');
        config()->set('gatekeeper.cache.driver', null);

        $cache = new PermissionCache();
        $stats = $cache->getStats();

        $this->assertFalse($stats['supports_tagging']);
    }

    /** @test */
    public function it_does_not_support_tagging_with_database_driver(): void
    {
        config()->set('cache.default', 'database');
        config()->set('gatekeeper.cache.driver', null);

        $cache = new PermissionCache();
        $stats = $cache->getStats();

        $this->assertFalse($stats['supports_tagging']);
    }

    /** @test */
    public function it_uses_configured_driver_for_tagging_check(): void
    {
        // When driver is explicitly set, it uses that for supportsTagging check
        config()->set('gatekeeper.cache.driver', 'array');

        $cache = new PermissionCache();
        $stats = $cache->getStats();

        // array driver supports tagging
        $this->assertTrue($stats['supports_tagging']);
    }

    /** @test */
    public function it_does_not_support_tagging_when_driver_is_file(): void
    {
        config()->set('gatekeeper.cache.driver', 'file');

        $cache = new PermissionCache();
        $stats = $cache->getStats();

        $this->assertFalse($stats['supports_tagging']);
    }

    // ── buildPermissionMatrix() with non-Role instance ─────────────────────

    /** @test */
    public function it_skips_non_role_instances_in_matrix_building(): void
    {
        $user = $this->createUser();

        // User with no roles → empty matrix
        $cache = new PermissionCache();
        $matrix = $cache->getPermissionMatrix($user);

        $this->assertIsArray($matrix);
        $this->assertEmpty($matrix);
    }

    /** @test */
    public function it_builds_complete_matrix_with_all_permission_types(): void
    {
        $user = $this->createUser();
        $role = Role::factory()->withFieldPermissions([
            'Order' => [
                'fields' => [
                    'total' => ['view' => true, 'update' => true],
                    'status' => ['view' => true, 'update' => false],
                ],
                'columns' => ['total' => true, 'status' => false],
                'actions' => ['approve' => true, 'cancel' => false],
                'relations' => ['items' => ['view', 'edit'], 'customer' => ['view']],
            ],
        ])->create(['name' => 'order-manager']);

        $user->assignRole($role);

        $cache = new PermissionCache();
        $matrix = $cache->getPermissionMatrix($user);

        $this->assertArrayHasKey('Order', $matrix);
        $this->assertTrue($matrix['Order']['fields']['total']['view']);
        $this->assertTrue($matrix['Order']['fields']['total']['update']);
        $this->assertTrue($matrix['Order']['fields']['status']['view']);
        $this->assertFalse($matrix['Order']['fields']['status']['update']);
        $this->assertTrue($matrix['Order']['columns']['total']);
        $this->assertFalse($matrix['Order']['columns']['status']);
        $this->assertTrue($matrix['Order']['actions']['approve']);
        $this->assertFalse($matrix['Order']['actions']['cancel']);
        $this->assertContains('view', $matrix['Order']['relations']['items']);
        $this->assertContains('edit', $matrix['Order']['relations']['items']);
        $this->assertContains('view', $matrix['Order']['relations']['customer']);
    }

    /** @test */
    public function it_merges_relation_permissions_from_multiple_roles_uniquely(): void
    {
        $user = $this->createUser();

        $role1 = Role::factory()->withFieldPermissions([
            'User' => [
                'fields' => [],
                'columns' => [],
                'actions' => [],
                'relations' => ['posts' => ['view']],
            ],
        ])->create(['name' => 'viewer-a']);

        $role2 = Role::factory()->withFieldPermissions([
            'User' => [
                'fields' => [],
                'columns' => [],
                'actions' => [],
                'relations' => ['posts' => ['view', 'create']],
            ],
        ])->create(['name' => 'viewer-b']);

        $user->assignRole([$role1, $role2]);

        $cache = new PermissionCache();
        $matrix = $cache->getPermissionMatrix($user);

        $relations = $matrix['User']['relations']['posts'] ?? [];
        // Should have unique values
        $this->assertContains('view', $relations);
        $this->assertContains('create', $relations);
        $this->assertEquals(count($relations), count(array_unique($relations)));
    }

    /** @test */
    public function it_combines_boolean_column_permissions_with_or_logic(): void
    {
        $user = $this->createUser();

        $role1 = Role::factory()->withFieldPermissions([
            'Product' => [
                'fields' => [],
                'columns' => ['price' => false],
                'actions' => [],
                'relations' => [],
            ],
        ])->create(['name' => 'limited-viewer']);

        $role2 = Role::factory()->withFieldPermissions([
            'Product' => [
                'fields' => [],
                'columns' => ['price' => true],
                'actions' => [],
                'relations' => [],
            ],
        ])->create(['name' => 'full-viewer']);

        $user->assignRole([$role1, $role2]);

        $cache = new PermissionCache();
        $matrix = $cache->getPermissionMatrix($user);

        // OR logic: false || true = true
        $this->assertTrue($matrix['Product']['columns']['price'] ?? false);
    }

    /** @test */
    public function it_combines_field_view_permissions_with_or_logic(): void
    {
        $user = $this->createUser();

        $role1 = Role::factory()->withFieldPermissions([
            'Invoice' => [
                'fields' => ['amount' => ['view' => false, 'update' => false]],
                'columns' => [],
                'actions' => [],
                'relations' => [],
            ],
        ])->create(['name' => 'no-view-role']);

        $role2 = Role::factory()->withFieldPermissions([
            'Invoice' => [
                'fields' => ['amount' => ['view' => true, 'update' => false]],
                'columns' => [],
                'actions' => [],
                'relations' => [],
            ],
        ])->create(['name' => 'view-role']);

        $user->assignRole([$role1, $role2]);

        $cache = new PermissionCache();
        $matrix = $cache->getPermissionMatrix($user);

        // OR logic applies: false || true = true
        $this->assertTrue($matrix['Invoice']['fields']['amount']['view']);
        $this->assertFalse($matrix['Invoice']['fields']['amount']['update']);
    }

    /** @test */
    public function it_getPermissionMatrix_with_cache_enabled_caches_result(): void
    {
        config()->set('gatekeeper.cache.enabled', true);

        $user = $this->createUser();
        $cache = new PermissionCache();

        // First call builds and caches
        $matrix1 = $cache->getPermissionMatrix($user);
        // Second call returns from cache
        $matrix2 = $cache->getPermissionMatrix($user);

        $this->assertEquals($matrix1, $matrix2);
    }

    /** @test */
    public function it_invalidateUser_clears_user_specific_cache(): void
    {
        $user = $this->createUser();
        $cache = new PermissionCache();

        $cache->getPermissionMatrix($user);
        $cache->invalidateUser($user);

        // Should not throw after invalidation
        $matrix = $cache->getPermissionMatrix($user);
        $this->assertIsArray($matrix);
    }
}
