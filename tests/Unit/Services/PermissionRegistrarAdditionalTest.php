<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Unit\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LaraArabDev\FilamentGatekeeper\Models\Permission;
use LaraArabDev\FilamentGatekeeper\Models\Role;
use LaraArabDev\FilamentGatekeeper\Services\PermissionRegistrar;
use LaraArabDev\FilamentGatekeeper\Support\Discovery\ColumnDiscovery;
use LaraArabDev\FilamentGatekeeper\Support\Discovery\FieldDiscovery;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;

class PermissionRegistrarAdditionalTest extends TestCase
{
    use RefreshDatabase;

    protected PermissionRegistrar $registrar;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registrar = app(PermissionRegistrar::class);

        config()->set('gatekeeper.super_admin.enabled', true);
        config()->set('gatekeeper.discovery.resources', []);
        config()->set('gatekeeper.discovery.models', []);
        config()->set('gatekeeper.discovery.pages', []);
        config()->set('gatekeeper.discovery.widgets', []);
        config()->set('gatekeeper.field_discovery.enabled', false);
        config()->set('gatekeeper.column_discovery.enabled', false);
        config()->set('gatekeeper.field_permissions', []);
        config()->set('gatekeeper.column_permissions', []);
        config()->set('gatekeeper.custom_actions', []);
        config()->set('gatekeeper.relation_permissions', []);
    }

    /** @test */
    public function it_can_get_field_discovery_service(): void
    {
        $discovery = $this->registrar->getFieldDiscovery();
        $this->assertInstanceOf(FieldDiscovery::class, $discovery);
    }

    /** @test */
    public function it_can_get_column_discovery_service(): void
    {
        $discovery = $this->registrar->getColumnDiscovery();
        $this->assertInstanceOf(ColumnDiscovery::class, $discovery);
    }

    /** @test */
    public function it_can_get_permission_prefixes_for_resource(): void
    {
        $prefixes = $this->registrar->getPermissionPrefixes('resource');
        $this->assertIsArray($prefixes);
        $this->assertNotEmpty($prefixes);
    }

    /** @test */
    public function it_can_get_permission_prefixes_for_field(): void
    {
        $prefixes = $this->registrar->getPermissionPrefixes('field');
        $this->assertIsArray($prefixes);
        $this->assertContains('view', $prefixes);
    }

    /** @test */
    public function it_can_get_permission_prefixes_for_unknown_type(): void
    {
        $prefixes = $this->registrar->getPermissionPrefixes('nonexistent_type');
        $this->assertIsArray($prefixes);
        $this->assertEmpty($prefixes);
    }

    /** @test */
    public function sync_super_admin_role_in_dry_run_mode_does_not_create_role(): void
    {
        config()->set('gatekeeper.super_admin.enabled', true);
        config()->set('gatekeeper.super_admin.role', 'super-admin');

        $this->registrar->dryRun(true)->syncSuperAdminRole();

        // Dry run should NOT create the role
        $this->assertDatabaseMissing('roles', ['name' => 'super-admin']);

        // But should log the action
        $log = $this->registrar->getSyncLog();
        $this->assertArrayHasKey('super_admin', $log);
        $this->assertStringContainsString('Would create', $log['super_admin'][0]);
    }

    /** @test */
    public function sync_super_admin_role_live_mode_creates_role_and_syncs_permissions(): void
    {
        config()->set('gatekeeper.super_admin.enabled', true);
        config()->set('gatekeeper.super_admin.role', 'super-admin');

        Permission::factory()->resource()->forGuard('web')->create(['name' => 'view_any_user']);
        Permission::factory()->resource()->forGuard('web')->create(['name' => 'create_user']);

        $this->registrar->dryRun(false)->syncSuperAdminRole();

        $role = Role::where('name', 'super-admin')->where('guard_name', 'web')->first();
        $this->assertNotNull($role);
        $this->assertEquals(2, $role->permissions()->count());

        $log = $this->registrar->getSyncLog();
        $this->assertArrayHasKey('super_admin', $log);
        $this->assertStringContainsString('Synced', $log['super_admin'][0]);
    }

    /** @test */
    public function sync_all_runs_all_sync_methods(): void
    {
        config()->set('gatekeeper.field_permissions', ['User' => ['email']]);
        config()->set('gatekeeper.column_permissions', ['User' => ['name']]);
        config()->set('gatekeeper.custom_actions', ['Post' => ['export']]);
        config()->set('gatekeeper.relation_permissions', ['User' => ['roles']]);

        $log = $this->registrar->syncAll();

        $this->assertIsArray($log);

        // Field, column, action, relation sync should create permissions
        $this->assertDatabaseHas('permissions', ['name' => 'view_user_email_field']);
        $this->assertDatabaseHas('permissions', ['name' => 'view_user_name_column']);
        $this->assertDatabaseHas('permissions', ['name' => 'execute_post_export_action']);
        $this->assertDatabaseHas('permissions', ['name' => 'view_user_roles_relation']);
    }

    /** @test */
    public function create_or_update_permission_creates_new_permission(): void
    {
        $this->registrar->createOrUpdatePermission(
            'view_any_test_resource',
            'web',
            Permission::TYPE_RESOURCE,
            'test_resource'
        );

        $this->assertDatabaseHas('permissions', [
            'name' => 'view_any_test_resource',
            'guard_name' => 'web',
            'type' => Permission::TYPE_RESOURCE,
        ]);
    }

    /** @test */
    public function create_or_update_permission_updates_existing_permission(): void
    {
        // Create first
        $this->registrar->createOrUpdatePermission('view_any_test2', 'web', Permission::TYPE_RESOURCE, 'test2');

        // Call again (update/idempotent)
        $this->registrar->createOrUpdatePermission('view_any_test2', 'web', Permission::TYPE_RESOURCE, 'test2_updated');

        // Should still exist (not duplicated)
        $this->assertEquals(1, Permission::where('name', 'view_any_test2')->count());
    }

    /** @test */
    public function delete_field_permissions_with_guard_filter(): void
    {
        Permission::factory()->field()->forGuard('web')->create([
            'name' => 'view_product_price_field',
            'entity' => 'product',
        ]);
        Permission::factory()->field()->forGuard('api')->create([
            'name' => 'view_product_price_field',
            'entity' => 'product',
        ]);

        $count = $this->registrar->dryRun(false)->deleteFieldPermissions('product', null, 'web');

        $this->assertGreaterThanOrEqual(1, $count);
        $this->assertDatabaseMissing('permissions', ['name' => 'view_product_price_field', 'guard_name' => 'web']);
        $this->assertDatabaseHas('permissions', ['name' => 'view_product_price_field', 'guard_name' => 'api']);
    }

    /** @test */
    public function delete_field_permissions_in_dry_run_does_not_delete(): void
    {
        Permission::factory()->field()->create([
            'name' => 'view_user_salary_field',
            'entity' => 'user',
        ]);

        $count = $this->registrar->dryRun(true)->deleteFieldPermissions('user');

        $this->assertGreaterThanOrEqual(1, $count);
        $this->assertDatabaseHas('permissions', ['name' => 'view_user_salary_field']);

        $log = $this->registrar->getSyncLog();
        $this->assertArrayHasKey('delete_field', $log);
        $this->assertStringContainsString('Would delete', $log['delete_field'][0]);
    }

    /** @test */
    public function delete_column_permissions_with_specific_columns(): void
    {
        Permission::factory()->column()->create([
            'name' => 'view_user_email_column',
            'entity' => 'user',
        ]);
        Permission::factory()->column()->create([
            'name' => 'view_user_name_column',
            'entity' => 'user',
        ]);

        $count = $this->registrar->dryRun(false)->deleteColumnPermissions('user', ['email']);

        $this->assertDatabaseMissing('permissions', ['name' => 'view_user_email_column']);
        $this->assertDatabaseHas('permissions', ['name' => 'view_user_name_column']);
    }

    /** @test */
    public function delete_column_permissions_in_dry_run(): void
    {
        Permission::factory()->column()->create([
            'name' => 'view_post_title_column',
            'entity' => 'post',
        ]);

        $count = $this->registrar->dryRun(true)->deleteColumnPermissions('post');

        $this->assertGreaterThanOrEqual(1, $count);
        $this->assertDatabaseHas('permissions', ['name' => 'view_post_title_column']);

        $log = $this->registrar->getSyncLog();
        $this->assertArrayHasKey('delete_column', $log);
        $this->assertStringContainsString('Would delete', $log['delete_column'][0]);
    }
}
