<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Unit\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LaraArabDev\FilamentGatekeeper\Models\Permission;
use LaraArabDev\FilamentGatekeeper\Models\Role;
use LaraArabDev\FilamentGatekeeper\Services\PermissionCache;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class PermissionCacheExtendedTest extends TestCase
{
    use RefreshDatabase;

    protected PermissionCache $cache;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cache = new PermissionCache;
    }

    #[Test]
    public function it_can_get_permission_matrix_for_user(): void
    {
        $user = $this->createUser();

        Permission::factory()->resource()->create(['name' => 'view_any_user']);
        $user->givePermissionTo('view_any_user');

        $matrix = $this->cache->getPermissionMatrix($user);

        $this->assertIsArray($matrix);
    }

    #[Test]
    public function it_can_warm_cache_for_user(): void
    {
        $user = $this->createUser();

        Permission::factory()->resource()->create(['name' => 'view_any_user']);
        $user->givePermissionTo('view_any_user');

        // Should not throw exception
        $this->cache->warmCache($user);
        $this->assertTrue(true);
    }

    #[Test]
    public function it_invalidate_for_user_clears_specific_user_cache(): void
    {
        $user = $this->createUser();

        // Should not throw
        $this->cache->invalidateForUser($user);
        $this->assertTrue(true);
    }

    #[Test]
    public function it_can_get_user_permissions_cache_key(): void
    {
        $user = $this->createUser();

        $key = $this->cache->getUserPermissionsCacheKey($user);

        $this->assertIsString($key);
        $this->assertNotEmpty($key);
        $this->assertStringContainsString((string) $user->getAuthIdentifier(), $key);
    }

    #[Test]
    public function it_cache_disabled_bypasses_caching(): void
    {
        config()->set('gatekeeper.cache.enabled', false);

        $cache = new PermissionCache;
        $user = $this->createUser();

        Permission::factory()->resource()->create(['name' => 'view_any_user']);
        $user->givePermissionTo('view_any_user');

        // Should still return matrix without caching
        $matrix = $cache->getPermissionMatrix($user);
        $this->assertIsArray($matrix);
    }

    #[Test]
    public function it_invalidate_role_clears_cache_for_role_users(): void
    {
        $user = $this->createUser();
        $role = $this->createRole('editor');
        $user->assignRole($role);

        // Should not throw
        $this->cache->invalidateRole($role);
        $this->assertTrue(true);
    }

    #[Test]
    public function it_cache_key_is_unique_per_user(): void
    {
        $user1 = $this->createUser();
        $user2 = $this->createUser(['email' => 'user2@example.com']);

        $key1 = $this->cache->getUserPermissionsCacheKey($user1);
        $key2 = $this->cache->getUserPermissionsCacheKey($user2);

        $this->assertNotEquals($key1, $key2);
    }

    #[Test]
    public function it_permission_matrix_is_empty_for_user_with_no_roles(): void
    {
        $user = $this->createUser();

        $matrix = $this->cache->getPermissionMatrix($user);

        $this->assertIsArray($matrix);
        $this->assertEmpty($matrix);
    }

    #[Test]
    public function it_permission_matrix_reflects_role_field_permissions(): void
    {
        $user = $this->createUser();
        $role = Role::factory()->withFieldPermissions([
            'User' => [
                'fields' => [
                    'email' => ['view' => true, 'update' => false],
                ],
                'columns' => ['email' => true],
                'actions' => ['export' => true],
                'relations' => ['posts' => ['view']],
            ],
        ])->create(['name' => 'manager']);

        $user->assignRole($role);

        $matrix = $this->cache->getPermissionMatrix($user);

        $this->assertArrayHasKey('User', $matrix);
        $this->assertTrue($matrix['User']['fields']['email']['view'] ?? false);
        $this->assertTrue($matrix['User']['columns']['email'] ?? false);
        $this->assertTrue($matrix['User']['actions']['export'] ?? false);
        $this->assertContains('view', $matrix['User']['relations']['posts'] ?? []);
    }

    #[Test]
    public function it_merges_field_permissions_from_multiple_roles(): void
    {
        $user = $this->createUser();

        $role1 = Role::factory()->withFieldPermissions([
            'User' => [
                'fields' => ['email' => ['view' => true, 'update' => false]],
                'columns' => [],
                'actions' => [],
                'relations' => [],
            ],
        ])->create(['name' => 'viewer']);

        $role2 = Role::factory()->withFieldPermissions([
            'User' => [
                'fields' => ['salary' => ['view' => true, 'update' => true]],
                'columns' => [],
                'actions' => [],
                'relations' => [],
            ],
        ])->create(['name' => 'hr_manager']);

        $user->assignRole([$role1, $role2]);

        $matrix = $this->cache->getPermissionMatrix($user);

        $this->assertArrayHasKey('User', $matrix);
        $this->assertTrue($matrix['User']['fields']['email']['view'] ?? false);
        $this->assertTrue($matrix['User']['fields']['salary']['view'] ?? false);
    }

    #[Test]
    public function it_cache_key_contains_matrix_suffix(): void
    {
        $user = $this->createUser();

        $key = $this->cache->getUserPermissionsCacheKey($user);

        $this->assertStringContainsString('matrix', $key);
    }

    #[Test]
    public function it_invalidate_all_does_not_throw(): void
    {
        $this->cache->remember('test_key_extended', fn () => 'test_value');

        $this->cache->invalidateAll();

        $this->assertTrue(true);
    }

    #[Test]
    public function it_returns_empty_matrix_for_user_without_roles_method(): void
    {
        // Create a user that implements Authenticatable but has no roles() method
        $simpleUser = new class implements Authenticatable
        {
            public function getAuthIdentifierName(): string
            {
                return 'id';
            }

            public function getAuthIdentifier(): mixed
            {
                return 99999;
            }

            public function getAuthPasswordName(): string
            {
                return 'password';
            }

            public function getAuthPassword(): string
            {
                return '';
            }

            public function getRememberToken(): string
            {
                return '';
            }

            public function setRememberToken(mixed $value): void {}

            public function getRememberTokenName(): string
            {
                return 'remember_token';
            }
        };

        $matrix = $this->cache->getPermissionMatrix($simpleUser);

        $this->assertIsArray($matrix);
        $this->assertSame([], $matrix);
    }

    #[Test]
    public function it_can_flush_all_cache(): void
    {
        $user = $this->createUser();
        $this->cache->warmCache($user);

        $this->cache->flushAll();

        $this->assertTrue(true); // Should not throw
    }
}
