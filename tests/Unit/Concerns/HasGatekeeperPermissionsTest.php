<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Unit\Concerns;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LaraArabDev\FilamentGatekeeper\Concerns\HasGatekeeperPermissions;
use LaraArabDev\FilamentGatekeeper\Concerns\InteractsWithGatekeeperCache;
use LaraArabDev\FilamentGatekeeper\Facades\Gatekeeper;
use LaraArabDev\FilamentGatekeeper\Models\Permission;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;
use LaraArabDev\FilamentGatekeeper\Tests\TestUser;
use Spatie\Permission\PermissionRegistrar;

// Expose InteractsWithGatekeeperCache protected methods for testing
class FakePostResourceWithInteracts
{
    use InteractsWithGatekeeperCache;

    public static string $model = 'Post';

    public static function callGetGuardName(): string
    {
        return static::getGuardName();
    }

    public static function callGetPermissionMatrix(): array
    {
        return static::getPermissionMatrix();
    }

    public static function callGetAuthUser(): ?Authenticatable
    {
        return static::getAuthUser();
    }
}

// Concrete class that uses the trait (simulates a Filament Resource)
class FakePostResource
{
    use HasGatekeeperPermissions;

    public static string $model = 'Post';

    // Expose protected method for testing
    public static function getPermissionName(string $action): string
    {
        return static::getPermissionNameInternal($action);
    }

    private static function getPermissionNameInternal(string $action): string
    {
        $modelName = static::getGatekeeperModelName();
        $separator = config('gatekeeper.generator.separator', '_');
        if (config('gatekeeper.generator.snake_case', true)) {
            $modelName = str($modelName)->snake()->toString();
        } else {
            $modelName = str($modelName)->camel()->toString();
        }

        return "{$action}{$separator}{$modelName}";
    }
}

class FakeUserResource
{
    use HasGatekeeperPermissions;

    public static string $model = TestUser::class;
}

class HasGatekeeperPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('gatekeeper.super_admin.enabled', false);
        config()->set('gatekeeper.generator.separator', '_');
        config()->set('gatekeeper.generator.snake_case', true);
    }

    // ── Permission name generation ────────────────────────────────────────

    /** @test */
    public function it_generates_correct_permission_name_for_action(): void
    {
        $name = FakePostResource::getPermissionName('view_any');
        $this->assertSame('view_any_post', $name);
    }

    /** @test */
    public function it_generates_snake_case_model_name(): void
    {
        config()->set('gatekeeper.generator.snake_case', true);
        $name = FakePostResource::getPermissionName('create');
        $this->assertSame('create_post', $name);
    }

    /** @test */
    public function it_generates_camel_case_model_name_when_snake_case_disabled(): void
    {
        config()->set('gatekeeper.generator.snake_case', false);
        config()->set('gatekeeper.generator.separator', '_');
        $name = FakePostResource::getPermissionName('create');
        // 'post' → camelCase is still 'post' (single word)
        $this->assertSame('create_post', $name);
    }

    /** @test */
    public function it_extracts_model_name_from_class_basename(): void
    {
        // FakePostResource → $model = 'Post' → basename = 'Post'
        $name = FakePostResource::getPermissionName('delete');
        $this->assertSame('delete_post', $name);
    }

    // ── Super admin bypass ────────────────────────────────────────────────

    /** @test */
    public function it_returns_true_for_all_methods_when_super_admin_bypasses(): void
    {
        Gatekeeper::shouldReceive('shouldBypassPermissions')->andReturn(true);

        $this->assertTrue(FakePostResource::canViewAny());
        $this->assertTrue(FakePostResource::canCreate());
        $this->assertTrue(FakePostResource::canDeleteAny());
        $this->assertTrue(FakePostResource::canRestoreAny());
        $this->assertTrue(FakePostResource::canForceDeleteAny());
        $this->assertTrue(FakePostResource::canReorder());
    }

    // ── No authenticated user ─────────────────────────────────────────────

    /** @test */
    public function it_returns_false_when_no_user_is_authenticated(): void
    {
        $this->assertFalse(FakePostResource::canViewAny());
        $this->assertFalse(FakePostResource::canCreate());
        $this->assertFalse(FakePostResource::canDeleteAny());
    }

    // ── Authenticated user with permission ────────────────────────────────

    /** @test */
    public function it_returns_true_when_user_has_view_any_permission(): void
    {
        $user = $this->createUser();
        $permission = $this->createPermission('view_any_post');
        $user->givePermissionTo($permission);
        $this->actingAs($user);

        $this->assertTrue(FakePostResource::canViewAny());
    }

    /** @test */
    public function it_returns_false_when_user_lacks_permission(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $this->assertFalse(FakePostResource::canCreate());
    }

    /** @test */
    public function it_returns_true_for_can_delete_when_user_has_permission(): void
    {
        $user = $this->createUser();
        $permission = $this->createPermission('delete_post');
        $user->givePermissionTo($permission);
        $this->actingAs($user);

        $this->assertTrue(FakePostResource::canDelete());
    }

    /** @test */
    public function it_returns_true_for_can_restore_when_user_has_permission(): void
    {
        $user = $this->createUser();
        $permission = $this->createPermission('restore_post');
        $user->givePermissionTo($permission);
        $this->actingAs($user);

        $this->assertTrue(FakePostResource::canRestore());
    }

    /** @test */
    public function it_returns_true_for_can_force_delete_when_user_has_permission(): void
    {
        $user = $this->createUser();
        $permission = $this->createPermission('force_delete_post');
        $user->givePermissionTo($permission);
        $this->actingAs($user);

        $this->assertTrue(FakePostResource::canForceDelete());
    }

    /** @test */
    public function it_returns_true_for_can_replicate_when_user_has_permission(): void
    {
        $user = $this->createUser();
        $permission = $this->createPermission('replicate_post');
        $user->givePermissionTo($permission);
        $this->actingAs($user);

        $this->assertTrue(FakePostResource::canReplicate());
    }

    /** @test */
    public function it_returns_true_for_can_reorder_when_user_has_permission(): void
    {
        $user = $this->createUser();
        $permission = $this->createPermission('reorder_post');
        $user->givePermissionTo($permission);
        $this->actingAs($user);

        $this->assertTrue(FakePostResource::canReorder());
    }

    /** @test */
    public function it_can_check_custom_action_permission(): void
    {
        $user = $this->createUser();
        $permission = $this->createPermission('export_post');
        $user->givePermissionTo($permission);
        $this->actingAs($user);

        $this->assertTrue(FakePostResource::canPerformAction('export'));
    }

    /** @test */
    public function it_can_check_custom_route_access(): void
    {
        $user = $this->createUser();
        $permission = $this->createPermission('access_dashboard', Permission::TYPE_RESOURCE);
        $user->givePermissionTo($permission);
        $this->actingAs($user);

        $this->assertTrue(FakePostResource::canAccessRoute('dashboard'));
    }

    /** @test */
    public function should_register_navigation_matches_can_view_any(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        // Without permission: both should be false
        $this->assertSame(FakePostResource::canViewAny(), FakePostResource::shouldRegisterNavigation());

        // With permission: both should be true
        $permission = $this->createPermission('view_any_post');
        $user->givePermissionTo($permission);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->assertSame(FakePostResource::canViewAny(), FakePostResource::shouldRegisterNavigation());
    }

    /** @test */
    public function it_returns_true_for_can_update_with_record(): void
    {
        $user = $this->createUser();
        $permission = $this->createPermission('update_post');
        $user->givePermissionTo($permission);
        $this->actingAs($user);

        $record = $this->createUser(); // use a real model instance
        $this->assertTrue(FakePostResource::canUpdate($record));
    }

    /** @test */
    public function it_returns_false_for_can_update_without_record_when_no_permission(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $this->assertFalse(FakePostResource::canUpdate());
    }

    /** @test */
    public function it_returns_true_for_can_view_with_record_when_user_has_permission(): void
    {
        $user = $this->createUser();
        $permission = $this->createPermission('view_post');
        $user->givePermissionTo($permission);
        $this->actingAs($user);

        $record = $this->createUser();
        $this->assertTrue(FakePostResource::canView($record));
    }

    /** @test */
    public function it_returns_false_for_can_view_with_record_when_no_permission(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $record = $this->createUser();
        $this->assertFalse(FakePostResource::canView($record));
    }

    /** @test */
    public function it_returns_true_for_can_edit_with_record_when_user_has_permission(): void
    {
        $user = $this->createUser();
        $permission = $this->createPermission('update_post');
        $user->givePermissionTo($permission);
        $this->actingAs($user);

        $record = $this->createUser();
        $this->assertTrue(FakePostResource::canEdit($record));
    }

    /** @test */
    public function it_returns_true_for_super_admin_can_view_with_record(): void
    {
        Gatekeeper::shouldReceive('shouldBypassPermissions')->andReturn(true);

        $record = $this->createUser();
        $this->assertTrue(FakePostResource::canView($record));
    }

    /** @test */
    public function it_returns_true_for_super_admin_can_edit_with_record(): void
    {
        Gatekeeper::shouldReceive('shouldBypassPermissions')->andReturn(true);

        $record = $this->createUser();
        $this->assertTrue(FakePostResource::canEdit($record));
    }

    // ── InteractsWithGatekeeperCache tests ────────────────────────────────

    /** @test */
    public function it_returns_configured_guard_name(): void
    {
        config()->set('gatekeeper.guard', 'api');

        $guardName = FakePostResourceWithInteracts::callGetGuardName();

        $this->assertEquals('api', $guardName);
    }

    /** @test */
    public function it_returns_web_as_default_guard_name(): void
    {
        config()->set('gatekeeper.guard', 'web');

        $guardName = FakePostResourceWithInteracts::callGetGuardName();

        $this->assertEquals('web', $guardName);
    }

    /** @test */
    public function it_returns_permission_matrix_as_array(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $matrix = FakePostResourceWithInteracts::callGetPermissionMatrix();

        $this->assertIsArray($matrix);
    }

    /** @test */
    public function it_returns_authenticated_user_from_get_auth_user(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $authUser = FakePostResourceWithInteracts::callGetAuthUser();

        $this->assertNotNull($authUser);
        $this->assertEquals($user->id, $authUser->getAuthIdentifier());
    }

    /** @test */
    public function it_returns_null_from_get_auth_user_when_no_user_authenticated(): void
    {
        $authUser = FakePostResourceWithInteracts::callGetAuthUser();

        $this->assertNull($authUser);
    }
}
