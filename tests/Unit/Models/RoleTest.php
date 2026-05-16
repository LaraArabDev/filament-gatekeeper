<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Unit\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LaraArabDev\FilamentGatekeeper\Models\Permission;
use LaraArabDev\FilamentGatekeeper\Models\Role;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class RoleTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_create_role(): void
    {
        $role = Role::factory()->create([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $this->assertDatabaseHas('roles', [
            'name' => 'admin',
            'guard_name' => 'web',
        ]);
    }

    #[Test]
    public function it_can_create_role_with_description(): void
    {
        $role = Role::factory()->withDescription('Administrator role with full access')->create([
            'name' => 'admin',
        ]);

        $this->assertDatabaseHas('roles', [
            'name' => 'admin',
            'description' => 'Administrator role with full access',
        ]);
    }

    #[Test]
    public function it_can_check_if_super_admin(): void
    {
        config()->set('gatekeeper.super_admin.role', 'super-admin');

        $superAdmin = Role::factory()->superAdmin()->create();

        $admin = Role::factory()->create([
            'name' => 'admin',
        ]);

        $this->assertTrue($superAdmin->isSuperAdmin());
        $this->assertFalse($admin->isSuperAdmin());
    }

    #[Test]
    public function it_can_scope_without_super_admin(): void
    {
        config()->set('gatekeeper.super_admin.role', 'super-admin');

        Role::factory()->superAdmin()->create();
        Role::factory()->create(['name' => 'admin']);
        Role::factory()->create(['name' => 'user']);

        $this->assertEquals(2, Role::withoutSuperAdmin()->count());
    }

    #[Test]
    public function it_can_scope_by_guard(): void
    {
        Role::factory()->forGuard('web')->create(['name' => 'admin']);
        Role::factory()->forGuard('api')->create(['name' => 'api-admin']);
        Role::factory()->forGuard('api')->create(['name' => 'api-user']);

        $this->assertEquals(1, Role::forGuard('web')->count());
        $this->assertEquals(2, Role::forGuard('api')->count());
    }

    #[Test]
    public function it_can_store_field_permissions_as_json(): void
    {
        $fieldPermissions = [
            'User' => [
                'fields' => [
                    'email' => ['view' => true, 'update' => false],
                    'password' => ['view' => false, 'update' => true],
                ],
                'columns' => [
                    'email' => true,
                    'created_at' => true,
                ],
                'actions' => [
                    'export' => true,
                    'delete' => false,
                ],
                'relations' => [
                    'roles' => ['view'],
                ],
            ],
        ];

        $role = Role::factory()->withFieldPermissions($fieldPermissions)->create([
            'name' => 'manager',
        ]);

        $this->assertEquals($fieldPermissions, $role->field_permissions);
    }

    #[Test]
    public function it_can_get_field_permissions_for_model(): void
    {
        $role = Role::factory()->withFieldPermissions([
            'User' => [
                'fields' => [
                    'email' => ['view' => true, 'update' => false],
                ],
            ],
        ])->create(['name' => 'manager']);

        $fieldPermissions = $role->getFieldPermissionsForModel('User');

        $this->assertEquals(['view' => true, 'update' => false], $fieldPermissions['email']);
    }

    #[Test]
    public function it_returns_empty_array_for_non_existing_model(): void
    {
        $role = Role::factory()->withFieldPermissions([])->create(['name' => 'manager']);

        $fieldPermissions = $role->getFieldPermissionsForModel('NonExistingModel');

        $this->assertEquals([], $fieldPermissions);
    }

    #[Test]
    public function it_can_check_field_permission(): void
    {
        $role = Role::factory()->withFieldPermissions([
            'User' => [
                'fields' => [
                    'email' => ['view' => true, 'update' => false],
                    'salary' => ['view' => true, 'update' => true],
                ],
            ],
        ])->create(['name' => 'manager']);

        $this->assertTrue($role->hasFieldPermission('User', 'email', 'view'));
        $this->assertFalse($role->hasFieldPermission('User', 'email', 'update'));
        $this->assertTrue($role->hasFieldPermission('User', 'salary', 'view'));
        $this->assertTrue($role->hasFieldPermission('User', 'salary', 'update'));
    }

    #[Test]
    public function it_can_check_column_permission(): void
    {
        $role = Role::factory()->withFieldPermissions([
            'User' => [
                'columns' => [
                    'email' => true,
                    'salary' => false,
                ],
            ],
        ])->create(['name' => 'manager']);

        $this->assertTrue($role->hasColumnPermission('User', 'email'));
        $this->assertFalse($role->hasColumnPermission('User', 'salary'));
    }

    #[Test]
    public function it_can_check_action_permission(): void
    {
        $role = Role::factory()->withFieldPermissions([
            'User' => [
                'actions' => [
                    'export' => true,
                    'delete' => false,
                ],
            ],
        ])->create(['name' => 'manager']);

        $this->assertTrue($role->hasActionPermission('User', 'export'));
        $this->assertFalse($role->hasActionPermission('User', 'delete'));
    }

    #[Test]
    public function it_can_check_relation_permission(): void
    {
        $role = Role::factory()->withFieldPermissions([
            'User' => [
                'relations' => [
                    'roles' => ['view'],
                    'posts' => [],
                ],
            ],
        ])->create(['name' => 'manager']);

        $this->assertTrue($role->hasRelationPermission('User', 'roles', 'view'));
        $this->assertFalse($role->hasRelationPermission('User', 'posts', 'view'));
    }

    #[Test]
    public function it_can_get_configured_models(): void
    {
        $role = Role::factory()->withFieldPermissions([
            'User' => ['fields' => []],
            'Post' => ['fields' => []],
            'Order' => ['fields' => []],
        ])->create(['name' => 'manager']);

        $models = $role->getConfiguredModels();

        $this->assertContains('User', $models);
        $this->assertContains('Post', $models);
        $this->assertContains('Order', $models);
        $this->assertCount(3, $models);
    }

    #[Test]
    public function it_can_assign_permissions(): void
    {
        $role = Role::factory()->create(['name' => 'admin']);

        $permission1 = Permission::factory()->resource()->create(['name' => 'view_any_user']);
        $permission2 = Permission::factory()->resource()->create(['name' => 'create_user']);

        $role->givePermissionTo($permission1, $permission2);

        $this->assertTrue($role->hasPermissionTo('view_any_user'));
        $this->assertTrue($role->hasPermissionTo('create_user'));
        $this->assertEquals(2, $role->permissions()->count());
    }

    #[Test]
    public function it_can_sync_permissions(): void
    {
        $role = Role::factory()->create(['name' => 'admin']);

        $permission1 = Permission::factory()->resource()->create(['name' => 'view_any_user']);
        $permission2 = Permission::factory()->resource()->create(['name' => 'create_user']);
        $permission3 = Permission::factory()->resource()->create(['name' => 'update_user']);

        $role->givePermissionTo($permission1, $permission2);

        $this->assertEquals(2, $role->permissions()->count());

        // Sync with only permission 2 and 3
        $role->syncPermissions([$permission2, $permission3]);

        $role->refresh();

        $this->assertFalse($role->hasPermissionTo('view_any_user'));
        $this->assertTrue($role->hasPermissionTo('create_user'));
        $this->assertTrue($role->hasPermissionTo('update_user'));
        $this->assertEquals(2, $role->permissions()->count());
    }

    #[Test]
    public function it_can_revoke_permissions(): void
    {
        $role = Role::factory()->create(['name' => 'admin']);

        $permission = Permission::factory()->resource()->create(['name' => 'view_any_user']);

        $role->givePermissionTo($permission);
        $this->assertTrue($role->hasPermissionTo('view_any_user'));

        $role->revokePermissionTo($permission);
        $this->assertFalse($role->hasPermissionTo('view_any_user'));
    }

    #[Test]
    public function it_can_get_permissions_by_type(): void
    {
        $role = Role::factory()->create(['name' => 'admin']);

        $resourcePermission = Permission::factory()->resource()->create(['name' => 'view_any_user']);
        $pagePermission = Permission::factory()->page()->create(['name' => 'view_dashboard_page']);
        $widgetPermission = Permission::factory()->widget()->create(['name' => 'view_stats_widget']);

        $role->givePermissionTo($resourcePermission, $pagePermission, $widgetPermission);

        $resourcePerms = $role->getPermissionsByType(Permission::TYPE_RESOURCE);
        $pagePerms = $role->getPermissionsByType(Permission::TYPE_PAGE);
        $widgetPerms = $role->getPermissionsByType(Permission::TYPE_WIDGET);

        $this->assertEquals(1, $resourcePerms->count());
        $this->assertEquals(1, $pagePerms->count());
        $this->assertEquals(1, $widgetPerms->count());
    }

    #[Test]
    public function it_has_fillable_attributes(): void
    {
        $role = new Role;

        $this->assertTrue(in_array('name', $role->getFillable()));
        $this->assertTrue(in_array('guard_name', $role->getFillable()));
        $this->assertTrue(in_array('field_permissions', $role->getFillable()));
    }

    #[Test]
    public function it_casts_field_permissions_to_array(): void
    {
        $role = Role::factory()->withFieldPermissions(['User' => ['fields' => []]])->create(['name' => 'admin']);

        $role->refresh();

        $this->assertIsArray($role->field_permissions);
    }
}
