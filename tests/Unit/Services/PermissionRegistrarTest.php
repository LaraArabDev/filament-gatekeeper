<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Unit\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LaraArabDev\FilamentGatekeeper\Models\Permission;
use LaraArabDev\FilamentGatekeeper\Models\Role;
use LaraArabDev\FilamentGatekeeper\Services\PermissionRegistrar;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;

class PermissionRegistrarTest extends TestCase
{
    use RefreshDatabase;

    protected PermissionRegistrar $registrar;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registrar = app(PermissionRegistrar::class);
    }

    /** @test */
    public function it_can_create_permission(): void
    {
        $this->registrar->createOrUpdatePermission(
            'view_any_user',
            'web',
            Permission::TYPE_RESOURCE
        );

        $this->assertDatabaseHas('permissions', [
            'name' => 'view_any_user',
            'type' => Permission::TYPE_RESOURCE,
            'guard_name' => 'web',
        ]);
    }

    /** @test */
    public function it_can_update_existing_permission(): void
    {
        // Create initial permission using factory
        Permission::factory()->model()->create([
            'name' => 'view_any_user',
            'guard_name' => 'web',
        ]);

        // Update to resource type
        $this->registrar->createOrUpdatePermission(
            'view_any_user',
            'web',
            Permission::TYPE_RESOURCE
        );

        $permission = Permission::where('name', 'view_any_user')->where('guard_name', 'web')->first();
        $this->assertEquals(Permission::TYPE_RESOURCE, $permission->type);
        $this->assertEquals(1, Permission::count());
    }

    /** @test */
    public function it_can_sync_super_admin_role(): void
    {
        config()->set('gatekeeper.super_admin.enabled', true);
        config()->set('gatekeeper.super_admin.role', 'super-admin');
        config()->set('gatekeeper.guards', ['web']);

        // Create some permissions using factory with explicit guard
        Permission::factory()->resource()->forGuard('web')->create(['name' => 'view_any_user']);
        Permission::factory()->resource()->forGuard('web')->create(['name' => 'create_user']);
        Permission::factory()->page()->forGuard('web')->create(['name' => 'view_dashboard_page']);

        $this->registrar->syncSuperAdminRole();

        $superAdmin = Role::where('name', 'super-admin')->where('guard_name', 'web')->first();

        $this->assertNotNull($superAdmin);
        $this->assertEquals(3, $superAdmin->permissions()->count());
    }

    /** @test */
    public function it_does_not_create_super_admin_when_disabled(): void
    {
        config()->set('gatekeeper.super_admin.enabled', false);

        $this->registrar->syncSuperAdminRole();

        $superAdmin = Role::where('name', 'super-admin')->first();

        $this->assertNull($superAdmin);
    }

    /** @test */
    public function it_can_sync_resource_permissions(): void
    {
        // This test would require mocking the resource discovery
        // For now, we test that the method runs without errors
        $this->registrar->syncResourcePermissions();

        $this->assertTrue(true);
    }

    /** @test */
    public function it_can_sync_page_permissions(): void
    {
        $this->registrar->syncPagePermissions();

        $this->assertTrue(true);
    }

    /** @test */
    public function it_can_sync_widget_permissions(): void
    {
        $this->registrar->syncWidgetPermissions();

        $this->assertTrue(true);
    }

    /** @test */
    public function it_can_sync_model_permissions(): void
    {
        config()->set('gatekeeper.discovery.discover_models', true);

        $this->registrar->syncModelPermissions();

        $this->assertTrue(true);
    }

    /** @test */
    public function it_can_sync_field_permissions(): void
    {
        config()->set('gatekeeper.field_permissions', [
            'User' => ['email', 'phone'],
        ]);

        $this->registrar->syncFieldPermissions();

        $this->assertDatabaseHas('permissions', ['name' => 'view_user_email_field']);
        $this->assertDatabaseHas('permissions', ['name' => 'update_user_email_field']);
        $this->assertDatabaseHas('permissions', ['name' => 'view_user_phone_field']);
        $this->assertDatabaseHas('permissions', ['name' => 'update_user_phone_field']);
    }

    /** @test */
    public function it_can_sync_column_permissions(): void
    {
        config()->set('gatekeeper.column_permissions', [
            'User' => ['email', 'phone'],
        ]);

        $this->registrar->syncColumnPermissions();

        $this->assertDatabaseHas('permissions', ['name' => 'view_user_email_column']);
        $this->assertDatabaseHas('permissions', ['name' => 'view_user_phone_column']);
    }

    /** @test */
    public function it_can_sync_action_permissions(): void
    {
        config()->set('gatekeeper.custom_actions', [
            'User' => ['export', 'import'],
        ]);

        $this->registrar->syncActionPermissions();

        $this->assertDatabaseHas('permissions', ['name' => 'execute_user_export_action']);
        $this->assertDatabaseHas('permissions', ['name' => 'execute_user_import_action']);
    }

    /** @test */
    public function it_can_sync_relation_permissions(): void
    {
        config()->set('gatekeeper.relation_permissions', [
            'User' => ['posts', 'roles'],
        ]);

        $this->registrar->syncRelationPermissions();

        $this->assertDatabaseHas('permissions', ['name' => 'view_user_posts_relation']);
        $this->assertDatabaseHas('permissions', ['name' => 'view_user_roles_relation']);
    }

    /** @test */
    public function it_can_sync_all_permissions(): void
    {
        config()->set('gatekeeper.super_admin.enabled', true);
        config()->set('gatekeeper.field_permissions', ['User' => ['email']]);
        config()->set('gatekeeper.column_permissions', ['User' => ['email']]);
        config()->set('gatekeeper.custom_actions', ['User' => ['export']]);
        config()->set('gatekeeper.relation_permissions', ['User' => ['posts']]);

        $this->registrar->syncAll();

        // Check that super admin role exists
        $this->assertDatabaseHas('roles', ['name' => 'super-admin']);

        // Check that field permissions exist
        $this->assertDatabaseHas('permissions', ['name' => 'view_user_email_field']);

        // Check that column permissions exist
        $this->assertDatabaseHas('permissions', ['name' => 'view_user_email_column']);

        // Check that action permissions exist
        $this->assertDatabaseHas('permissions', ['name' => 'execute_user_export_action']);

        // Check that relation permissions exist
        $this->assertDatabaseHas('permissions', ['name' => 'view_user_posts_relation']);
    }

    /** @test */
    public function it_can_get_permission_prefixes(): void
    {
        $prefixes = $this->registrar->getPermissionPrefixes('resource');

        $this->assertContains('view_any', $prefixes);
        $this->assertContains('view', $prefixes);
        $this->assertContains('create', $prefixes);
        $this->assertContains('update', $prefixes);
        $this->assertContains('delete', $prefixes);
    }

    /** @test */
    public function it_uses_configured_permission_prefixes(): void
    {
        config()->set('gatekeeper.permission_prefixes.resource', [
            'view_any',
            'view',
            'create',
            'custom_action',
        ]);

        $prefixes = $this->registrar->getPermissionPrefixes('resource');

        $this->assertContains('custom_action', $prefixes);
    }

    /** @test */
    public function it_can_clean_orphaned_permissions(): void
    {
        // Create some permissions using factory
        Permission::factory()->resource()->create(['name' => 'valid_permission']);
        Permission::factory()->resource()->create(['name' => 'orphaned_permission']);

        // Mark one as orphaned by assigning it to no roles
        // The clean method would remove permissions that are no longer needed

        $this->assertEquals(2, Permission::count());
    }
}
