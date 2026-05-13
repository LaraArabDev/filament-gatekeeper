<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LaraArabDev\FilamentGatekeeper\Facades\Gatekeeper as GatekeeperFacade;
use LaraArabDev\FilamentGatekeeper\Models\Permission;
use LaraArabDev\FilamentGatekeeper\Models\Role;
use LaraArabDev\FilamentGatekeeper\Services\PermissionCache;
use LaraArabDev\FilamentGatekeeper\Gatekeeper;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;

class GatekeeperTest extends TestCase
{
    use RefreshDatabase;

    protected Gatekeeper $shieldManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->shieldManager = new Gatekeeper(new PermissionCache());
    }

    /** @test */
    public function it_can_check_permission(): void
    {
        $user = $this->createUser();

        $permission = Permission::factory()->resource()->create([
            'name' => 'view_any_user',
        ]);

        $user->givePermissionTo('view_any_user');

        $this->actingAs($user);

        $this->assertTrue($this->shieldManager->can('view_any_user'));
        $this->assertFalse($this->shieldManager->can('create_user'));
    }

    /** @test */
    public function it_can_authorize_permission(): void
    {
        $user = $this->createUser();

        Permission::factory()->resource()->create([
            'name' => 'view_any_user',
        ]);

        $user->givePermissionTo('view_any_user');

        $this->actingAs($user);

        // Should not throw exception
        $this->shieldManager->authorize('view_any_user');
        $this->assertTrue(true);
    }

    /** @test */
    public function it_throws_exception_when_unauthorized(): void
    {
        $user = $this->createUser();

        $this->actingAs($user);

        // Gatekeeper::authorize() throws HttpException (via abort(403))
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->shieldManager->authorize('view_any_user');
    }

    /** @test */
    public function it_bypasses_for_super_admin(): void
    {
        $user = $this->createSuperAdmin();

        $this->actingAs($user);

        // Super admin should have all permissions
        $this->assertTrue($this->shieldManager->can('view_any_user'));
        $this->assertTrue($this->shieldManager->can('create_user'));
        $this->assertTrue($this->shieldManager->can('any_permission_that_does_not_exist'));
    }

    /** @test */
    public function it_can_check_super_admin_status(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $regularUser = $this->createUser(['email' => 'regular@example.com']);

        $this->actingAs($superAdmin);
        $this->assertTrue($this->shieldManager->isSuperAdmin());

        $this->actingAs($regularUser);
        $this->assertFalse($this->shieldManager->isSuperAdmin());
    }

    /** @test */
    public function it_can_change_guard(): void
    {
        // Create user with web guard
        $user = $this->createUser();

        // Create permission with web guard
        $webPermission = Permission::factory()->forGuard('web')->resource()->create([
            'name' => 'view_any_user',
        ]);

        // Create permission with api guard
        $apiPermission = Permission::factory()->forGuard('api')->resource()->create([
            'name' => 'view_any_user',
        ]);

        // Create role with web guard and assign permission to it
        $webRole = Role::factory()->forGuard('web')->create(['name' => 'web-user']);
        $webRole->givePermissionTo($webPermission);

        // Create role with api guard and assign permission to it
        $apiRole = Role::factory()->forGuard('api')->create(['name' => 'api-user']);
        $apiRole->givePermissionTo($apiPermission);

        // Assign web role to user
        $user->assignRole($webRole);

        $this->actingAs($user, 'web');

        // Web guard should have the permission
        $this->assertTrue($this->shieldManager->guard('web')->can('view_any_user'));

        // API guard should not have the permission (different guard)
        $this->assertFalse($this->shieldManager->guard('api')->can('view_any_user'));

        // Create API user and assign API role
        $apiUser = $this->createUser(['email' => 'api@example.com']);
        $apiUser->setGuardName('api');

        // Directly insert role assignment to avoid guard mismatch
        \DB::table('model_has_roles')->insert([
            'role_id' => $apiRole->id,
            'model_type' => get_class($apiUser),
            'model_id' => $apiUser->id,
        ]);

        // Clear permission cache to ensure fresh check
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($apiUser, 'api');

        // API guard should have the permission
        $apiShield = $this->shieldManager->guard('api');
        $this->assertTrue($apiShield->can('view_any_user'));

        // Note: When checking with different guard, Gatekeeper uses Auth::guard($guard)->user()
        // which may return the same user if they're authenticated in multiple guards.
        // The key test is that Gatekeeper can change guards and check permissions correctly.
    }

    /** @test */
    public function it_can_get_current_guard(): void
    {
        $this->assertEquals('web', $this->shieldManager->getGuard());

        $this->shieldManager->guard('api');
        $this->assertEquals('api', $this->shieldManager->getGuard());
    }

    /** @test */
    public function it_works_via_facade(): void
    {
        $user = $this->createUser();

        Permission::factory()->resource()->create([
            'name' => 'view_any_user',
        ]);

        $user->givePermissionTo('view_any_user');

        $this->actingAs($user);

        $this->assertTrue(GatekeeperFacade::can('view_any_user'));
        $this->assertFalse(GatekeeperFacade::can('create_user'));
    }

    /** @test */
    public function it_can_check_field_permission(): void
    {
        $user = $this->createUser();

        Permission::factory()->field()->create([
            'name' => 'view_user_email_field',
        ]);

        $user->givePermissionTo('view_user_email_field');

        $this->actingAs($user);

        $this->assertTrue($this->shieldManager->canViewField('User', 'email'));
        $this->assertFalse($this->shieldManager->canViewField('User', 'salary'));
    }

    /** @test */
    public function it_can_check_update_field_permission(): void
    {
        $user = $this->createUser();

        Permission::factory()->field()->create([
            'name' => 'update_user_email_field',
        ]);

        $user->givePermissionTo('update_user_email_field');

        $this->actingAs($user);

        $this->assertTrue($this->shieldManager->canUpdateField('User', 'email'));
        $this->assertFalse($this->shieldManager->canUpdateField('User', 'salary'));
    }

    /** @test */
    public function it_can_check_column_permission(): void
    {
        $user = $this->createUser();

        Permission::factory()->column()->create([
            'name' => 'view_user_email_column',
        ]);

        $user->givePermissionTo('view_user_email_column');

        $this->actingAs($user);

        $this->assertTrue($this->shieldManager->canViewColumn('User', 'email'));
        $this->assertFalse($this->shieldManager->canViewColumn('User', 'salary'));
    }

    /** @test */
    public function it_can_check_action_permission(): void
    {
        $user = $this->createUser();

        Permission::factory()->action()->create([
            'name' => 'execute_user_export_action',
        ]);

        $user->givePermissionTo('execute_user_export_action');

        $this->actingAs($user);

        $this->assertTrue($this->shieldManager->canExecuteAction('User', 'export'));
        $this->assertFalse($this->shieldManager->canExecuteAction('User', 'delete'));
    }

    /** @test */
    public function it_can_check_relation_permission(): void
    {
        $user = $this->createUser();

        Permission::factory()->relation()->create([
            'name' => 'view_user_roles_relation',
        ]);

        $user->givePermissionTo('view_user_roles_relation');

        $this->actingAs($user);

        $this->assertTrue($this->shieldManager->canViewRelation('User', 'roles'));
        $this->assertFalse($this->shieldManager->canViewRelation('User', 'posts'));
    }

    /** @test */
    public function it_can_get_visible_fields(): void
    {
        $user = $this->createUser();

        // Configure fields in config first
        config()->set('gatekeeper.field_permissions.User', ['email', 'phone']);

        Permission::factory()->field()->create(['name' => 'view_user_email_field']);
        Permission::factory()->field()->create(['name' => 'view_user_phone_field']);

        $user->givePermissionTo('view_user_email_field', 'view_user_phone_field');

        $this->actingAs($user);

        $visibleFields = $this->shieldManager->getVisibleFields('User');

        $this->assertContains('email', $visibleFields);
        $this->assertContains('phone', $visibleFields);
    }

    /** @test */
    public function it_can_get_visible_columns(): void
    {
        $user = $this->createUser();

        // Configure columns in config first
        config()->set('gatekeeper.column_permissions.User', ['email', 'created_at']);

        Permission::factory()->column()->create(['name' => 'view_user_email_column']);
        Permission::factory()->column()->create(['name' => 'view_user_created_at_column']);

        $user->givePermissionTo('view_user_email_column', 'view_user_created_at_column');

        $this->actingAs($user);

        $visibleColumns = $this->shieldManager->getVisibleColumns('User');

        $this->assertContains('email', $visibleColumns);
        $this->assertContains('created_at', $visibleColumns);
    }

    /** @test */
    public function it_can_clear_cache(): void
    {
        // This should not throw any exceptions
        $this->shieldManager->clearCache();
        $this->assertTrue(true);
    }

    /** @test */
    public function it_returns_null_when_no_user_authenticated(): void
    {
        // Not acting as any user
        $this->assertNull($this->shieldManager->user());
    }

    /** @test */
    public function it_can_returns_false_when_no_user(): void
    {
        $this->assertFalse($this->shieldManager->can('view_any_user'));
    }

    /** @test */
    public function it_can_use_api_convenience_method(): void
    {
        $this->assertEquals('api', $this->shieldManager->api()->getGuard());
    }

    /** @test */
    public function it_can_use_web_convenience_method(): void
    {
        $this->assertEquals('web', $this->shieldManager->web()->getGuard());
    }

    /** @test */
    public function it_supports_or_logic_in_permissions(): void
    {
        $user = $this->createUser();

        Permission::factory()->resource()->create(['name' => 'view_any_user']);
        $user->givePermissionTo('view_any_user');

        $this->actingAs($user);

        // Has first permission in pipe list
        $this->assertTrue($this->shieldManager->can('view_any_user|create_user'));

        // Has neither
        $this->assertFalse($this->shieldManager->can('delete_user|create_user'));
    }

    /** @test */
    public function it_returns_empty_permission_matrix_when_no_user(): void
    {
        $matrix = $this->shieldManager->getPermissionMatrix();
        $this->assertIsArray($matrix);
        $this->assertEmpty($matrix);
    }

    /** @test */
    public function it_can_access_cache_instance(): void
    {
        $this->assertInstanceOf(\LaraArabDev\FilamentGatekeeper\Services\PermissionCache::class, $this->shieldManager->cache());
    }

    /** @test */
    public function it_canViewField_returns_false_when_no_user(): void
    {
        $this->assertFalse($this->shieldManager->canViewField('User', 'email'));
    }

    /** @test */
    public function it_canUpdateField_returns_false_when_no_user(): void
    {
        $this->assertFalse($this->shieldManager->canUpdateField('User', 'email'));
    }

    /** @test */
    public function it_canViewColumn_returns_false_when_no_user(): void
    {
        $this->assertFalse($this->shieldManager->canViewColumn('User', 'email'));
    }

    /** @test */
    public function it_canExecuteAction_returns_false_when_no_user(): void
    {
        $this->assertFalse($this->shieldManager->canExecuteAction('User', 'export'));
    }

    /** @test */
    public function it_canViewRelation_returns_false_when_no_user(): void
    {
        $this->assertFalse($this->shieldManager->canViewRelation('User', 'posts'));
    }

    /** @test */
    public function it_getVisibleFields_returns_empty_when_no_user(): void
    {
        $this->assertEmpty($this->shieldManager->getVisibleFields('User'));
    }

    /** @test */
    public function it_getVisibleColumns_returns_empty_when_no_user(): void
    {
        $this->assertEmpty($this->shieldManager->getVisibleColumns('User'));
    }

    /** @test */
    public function it_getVisibleFields_returns_all_fields_for_super_admin(): void
    {
        $user = $this->createSuperAdmin();
        $this->actingAs($user);

        config()->set('gatekeeper.field_permissions.User', ['email', 'phone', 'salary']);

        $fields = $this->shieldManager->getVisibleFields('User');

        $this->assertContains('email', $fields);
        $this->assertContains('phone', $fields);
        $this->assertContains('salary', $fields);
    }

    /** @test */
    public function it_getVisibleColumns_returns_all_columns_for_super_admin(): void
    {
        $user = $this->createSuperAdmin();
        $this->actingAs($user);

        config()->set('gatekeeper.column_permissions.User', ['name', 'email', 'created_at']);

        $columns = $this->shieldManager->getVisibleColumns('User');

        $this->assertContains('name', $columns);
        $this->assertContains('email', $columns);
        $this->assertContains('created_at', $columns);
    }

    /** @test */
    public function it_getVisibleFields_returns_empty_when_no_config(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        config()->set('gatekeeper.field_permissions', []);

        $fields = $this->shieldManager->getVisibleFields('User');
        $this->assertEmpty($fields);
    }

    /** @test */
    public function it_getVisibleColumns_returns_empty_when_no_config(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        config()->set('gatekeeper.column_permissions', []);

        $columns = $this->shieldManager->getVisibleColumns('User');
        $this->assertEmpty($columns);
    }

    /** @test */
    public function it_shouldBypassPermissions_returns_false_when_super_admin_disabled(): void
    {
        // Create super admin first (createSuperAdmin sets enabled=true internally)
        $user = $this->createSuperAdmin();
        // Then disable the super admin bypass
        config()->set('gatekeeper.super_admin.enabled', false);
        $this->actingAs($user);

        $this->assertFalse($this->shieldManager->shouldBypassPermissions());
    }

    /** @test */
    public function it_shouldBypassPermissions_returns_false_with_no_user(): void
    {
        $this->assertFalse($this->shieldManager->shouldBypassPermissions());
    }

    /** @test */
    public function it_canViewField_bypasses_for_super_admin(): void
    {
        $user = $this->createSuperAdmin();
        $this->actingAs($user);

        $this->assertTrue($this->shieldManager->canViewField('User', 'salary'));
        $this->assertTrue($this->shieldManager->canUpdateField('User', 'salary'));
    }

    /** @test */
    public function it_canViewColumn_bypasses_for_super_admin(): void
    {
        $user = $this->createSuperAdmin();
        $this->actingAs($user);

        $this->assertTrue($this->shieldManager->canViewColumn('User', 'salary'));
    }

    /** @test */
    public function it_canExecuteAction_bypasses_for_super_admin(): void
    {
        $user = $this->createSuperAdmin();
        $this->actingAs($user);

        $this->assertTrue($this->shieldManager->canExecuteAction('User', 'export'));
    }

    /** @test */
    public function it_canViewRelation_bypasses_for_super_admin(): void
    {
        $user = $this->createSuperAdmin();
        $this->actingAs($user);

        $this->assertTrue($this->shieldManager->canViewRelation('User', 'posts'));
    }
}
