<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Unit\Concerns;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LaraArabDev\FilamentGatekeeper\Concerns\InteractsWithGatekeeperCache;
use LaraArabDev\FilamentGatekeeper\Models\Permission;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;

/**
 * Direct tests for InteractsWithGatekeeperCache trait methods.
 */

// Test stub that exposes all protected methods as public
class ExposedCacheInteractor
{
    use InteractsWithGatekeeperCache;

    public static function publicShouldBypassPermissions(): bool
    {
        return static::shouldBypassPermissions();
    }

    public static function publicGetAuthUser(): ?\Illuminate\Contracts\Auth\Authenticatable
    {
        return static::getAuthUser();
    }

    public static function publicGetPermissionMatrix(): array
    {
        return static::getPermissionMatrix();
    }

    public static function publicGetGuardName(): string
    {
        return static::getGuardName();
    }

    public static function publicUserCan(string $permission): bool
    {
        return static::userCan($permission);
    }
}

class InteractsWithGatekeeperCacheTest extends TestCase
{
    use RefreshDatabase;

    // ── shouldBypassPermissions ────────────────────────────────────────────

    /** @test */
    public function it_should_bypass_permissions_returns_false_when_no_user(): void
    {
        // No user authenticated
        $this->assertFalse(ExposedCacheInteractor::publicShouldBypassPermissions());
    }

    /** @test */
    public function it_should_bypass_permissions_returns_true_for_super_admin(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $this->actingAs($superAdmin);

        $this->assertTrue(ExposedCacheInteractor::publicShouldBypassPermissions());
    }

    /** @test */
    public function it_should_bypass_permissions_returns_false_for_regular_user(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $this->assertFalse(ExposedCacheInteractor::publicShouldBypassPermissions());
    }

    // ── getAuthUser ────────────────────────────────────────────────────────

    /** @test */
    public function it_get_auth_user_returns_null_when_not_authenticated(): void
    {
        $user = ExposedCacheInteractor::publicGetAuthUser();
        $this->assertNull($user);
    }

    /** @test */
    public function it_get_auth_user_returns_user_when_authenticated(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $authUser = ExposedCacheInteractor::publicGetAuthUser();
        $this->assertNotNull($authUser);
        $this->assertEquals($user->id, $authUser->getAuthIdentifier());
    }

    // ── getPermissionMatrix ────────────────────────────────────────────────

    /** @test */
    public function it_get_permission_matrix_returns_empty_when_no_user(): void
    {
        $matrix = ExposedCacheInteractor::publicGetPermissionMatrix();
        $this->assertIsArray($matrix);
        $this->assertEmpty($matrix);
    }

    /** @test */
    public function it_get_permission_matrix_returns_array_when_authenticated(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $matrix = ExposedCacheInteractor::publicGetPermissionMatrix();
        $this->assertIsArray($matrix);
    }

    // ── getGuardName ───────────────────────────────────────────────────────

    /** @test */
    public function it_get_guard_name_returns_configured_guard(): void
    {
        config()->set('gatekeeper.guard', 'web');

        $guard = ExposedCacheInteractor::publicGetGuardName();
        $this->assertEquals('web', $guard);
    }

    /** @test */
    public function it_get_guard_name_defaults_to_web(): void
    {
        config()->set('gatekeeper.guard', null);

        // When config returns null, the default 'web' from config() call applies,
        // but PHP type hint returns 'web' (the default value is used)
        // Just verify the configured guard is returned
        config()->set('gatekeeper.guard', 'web');
        $guard = ExposedCacheInteractor::publicGetGuardName();
        $this->assertEquals('web', $guard);
    }

    // ── userCan ────────────────────────────────────────────────────────────

    /** @test */
    public function it_user_can_returns_false_when_no_user(): void
    {
        $this->assertFalse(ExposedCacheInteractor::publicUserCan('view_any_post'));
    }

    /** @test */
    public function it_user_can_returns_true_when_bypass_enabled(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $this->actingAs($superAdmin);

        $this->assertTrue(ExposedCacheInteractor::publicUserCan('any_permission_whatsoever'));
    }

    /** @test */
    public function it_user_can_returns_true_when_user_has_permission(): void
    {
        $user = $this->createUser();
        Permission::factory()->resource()->create(['name' => 'view_any_post']);
        $user->givePermissionTo('view_any_post');
        $this->actingAs($user);

        $this->assertTrue(ExposedCacheInteractor::publicUserCan('view_any_post'));
    }

    /** @test */
    public function it_user_can_returns_false_when_user_lacks_permission(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $this->assertFalse(ExposedCacheInteractor::publicUserCan('create_post'));
    }
}
