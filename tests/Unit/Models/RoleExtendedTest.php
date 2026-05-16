<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Unit\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LaraArabDev\FilamentGatekeeper\Models\Permission;
use LaraArabDev\FilamentGatekeeper\Models\Role;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class RoleExtendedTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_set_and_get_field_permissions_for_model(): void
    {
        $role = $this->createRole('editor');

        // setFieldPermissionsForModel stores the array as 'fields' key
        $role->setFieldPermissionsForModel('User', ['email' => ['view' => true], 'phone' => ['view' => true]]);

        $fields = $role->getFieldPermissionsForModel('User');

        $this->assertArrayHasKey('email', $fields);
        $this->assertArrayHasKey('phone', $fields);
    }

    #[Test]
    public function it_can_set_and_get_column_permissions_for_model(): void
    {
        $role = $this->createRole('editor');

        $role->setColumnPermissionsForModel('User', ['email' => true, 'salary' => true]);

        $columns = $role->getColumnPermissionsForModel('User');

        $this->assertArrayHasKey('email', $columns);
        $this->assertArrayHasKey('salary', $columns);
    }

    #[Test]
    public function it_can_set_and_get_action_permissions_for_model(): void
    {
        $role = $this->createRole('editor');

        $role->setActionPermissionsForModel('User', ['export' => true, 'import' => true]);

        $actions = $role->getActionPermissionsForModel('User');

        $this->assertArrayHasKey('export', $actions);
        $this->assertArrayHasKey('import', $actions);
    }

    #[Test]
    public function it_can_set_and_get_relation_permissions_for_model(): void
    {
        $role = $this->createRole('editor');

        $role->setRelationPermissionsForModel('User', ['posts' => ['view'], 'roles' => ['view']]);

        $relations = $role->getRelationPermissionsForModel('User');

        $this->assertArrayHasKey('posts', $relations);
        $this->assertArrayHasKey('roles', $relations);
    }

    #[Test]
    public function it_can_check_has_field_permission(): void
    {
        $role = $this->createRole('editor');
        $role->setFieldPermissionsForModel('User', [
            'email' => ['view' => true, 'update' => false],
            'phone' => ['view' => true, 'update' => true],
        ]);

        $this->assertTrue($role->hasFieldPermission('User', 'email', 'view'));
        $this->assertFalse($role->hasFieldPermission('User', 'email', 'update'));
        $this->assertTrue($role->hasFieldPermission('User', 'phone', 'view'));
    }

    #[Test]
    public function it_can_check_has_column_permission(): void
    {
        $role = $this->createRole('editor');
        $role->setColumnPermissionsForModel('User', ['email' => true, 'salary' => false]);

        $this->assertTrue($role->hasColumnPermission('User', 'email'));
        $this->assertFalse($role->hasColumnPermission('User', 'salary'));
    }

    #[Test]
    public function it_can_check_has_action_permission(): void
    {
        $role = $this->createRole('editor');
        $role->setActionPermissionsForModel('User', ['export' => true, 'import' => false]);

        $this->assertTrue($role->hasActionPermission('User', 'export'));
        $this->assertFalse($role->hasActionPermission('User', 'import'));
    }

    #[Test]
    public function it_can_check_has_relation_permission(): void
    {
        $role = $this->createRole('editor');
        $role->setRelationPermissionsForModel('User', ['posts' => ['view'], 'roles' => []]);

        $this->assertTrue($role->hasRelationPermission('User', 'posts', 'view'));
        $this->assertFalse($role->hasRelationPermission('User', 'roles', 'view'));
    }

    #[Test]
    public function it_returns_empty_array_for_model_with_no_permissions(): void
    {
        $role = $this->createRole('editor');

        $this->assertEmpty($role->getFieldPermissionsForModel('User'));
        $this->assertEmpty($role->getColumnPermissionsForModel('User'));
        $this->assertEmpty($role->getActionPermissionsForModel('User'));
        $this->assertEmpty($role->getRelationPermissionsForModel('User'));
    }

    #[Test]
    public function it_scope_for_guard_filters_by_guard(): void
    {
        $webRole = Role::factory()->forGuard('web')->create(['name' => 'web-editor']);
        $apiRole = Role::factory()->forGuard('api')->create(['name' => 'api-editor']);

        $webRoles = Role::forGuard('web')->get();
        $apiRoles = Role::forGuard('api')->get();

        $this->assertTrue($webRoles->contains('name', 'web-editor'));
        $this->assertFalse($webRoles->contains('name', 'api-editor'));
        $this->assertTrue($apiRoles->contains('name', 'api-editor'));
    }

    #[Test]
    public function it_scope_without_super_admin_excludes_super_admin(): void
    {
        config()->set('gatekeeper.super_admin.role', 'super-admin');

        $this->createRole('super-admin');
        $this->createRole('editor');

        $roles = Role::withoutSuperAdmin()->get();

        $this->assertFalse($roles->contains('name', 'super-admin'));
        $this->assertTrue($roles->contains('name', 'editor'));
    }

    #[Test]
    public function it_can_check_is_super_admin(): void
    {
        config()->set('gatekeeper.super_admin.role', 'super-admin');

        $superAdmin = Role::factory()->superAdmin()->create();
        $editor = $this->createRole('editor');

        $this->assertTrue($superAdmin->isSuperAdmin());
        $this->assertFalse($editor->isSuperAdmin());
    }

    #[Test]
    public function it_can_get_permissions_by_type_for_extended_role(): void
    {
        $role = $this->createRole('editor');

        Permission::factory()->resource()->create(['name' => 'view_any_user']);
        Permission::factory()->field()->create(['name' => 'view_user_email_field']);

        $role->givePermissionTo(['view_any_user', 'view_user_email_field']);

        $resourcePerms = $role->getPermissionsByType('resource');
        $fieldPerms = $role->getPermissionsByType('field');

        $this->assertTrue($resourcePerms->contains('name', 'view_any_user'));
        $this->assertFalse($resourcePerms->contains('name', 'view_user_email_field'));
        $this->assertTrue($fieldPerms->contains('name', 'view_user_email_field'));
    }

    #[Test]
    public function it_overwrites_existing_field_permissions_on_set(): void
    {
        $role = $this->createRole('editor');

        $role->setFieldPermissionsForModel('User', ['email' => ['view' => true], 'phone' => ['view' => true]]);
        $role->setFieldPermissionsForModel('User', ['salary' => ['view' => true]]); // overwrite

        $fields = $role->getFieldPermissionsForModel('User');

        $this->assertArrayHasKey('salary', $fields);
        $this->assertArrayNotHasKey('email', $fields);
        $this->assertArrayNotHasKey('phone', $fields);
    }

    #[Test]
    public function it_can_sync_all_permissions_to_role(): void
    {
        $role = $this->createRole('admin');

        Permission::factory()->resource()->create(['name' => 'view_any_user', 'guard_name' => 'web']);
        Permission::factory()->field()->create(['name' => 'view_user_email_field', 'guard_name' => 'web']);

        $role->syncAllPermissions();

        $this->assertEquals(2, $role->permissions()->count());
    }

    #[Test]
    public function it_can_sync_resource_permissions_only(): void
    {
        $role = $this->createRole('admin');

        Permission::factory()->resource()->create(['name' => 'view_any_user', 'guard_name' => 'web']);
        Permission::factory()->field()->create(['name' => 'view_user_email_field', 'guard_name' => 'web']);

        $role->syncResourcePermissions();

        $this->assertEquals(1, $role->permissions()->count());
        $this->assertTrue($role->hasPermissionTo('view_any_user'));
        $this->assertFalse($role->hasPermissionTo('view_user_email_field'));
    }

    #[Test]
    public function it_can_get_configured_models_after_setting_permissions(): void
    {
        $role = $this->createRole('admin');

        $role->setFieldPermissionsForModel('User', ['email' => ['view' => true]]);
        $role->setColumnPermissionsForModel('Post', ['title' => true]);

        $models = $role->getConfiguredModels();

        $this->assertContains('User', $models);
        $this->assertContains('Post', $models);
    }

    #[Test]
    public function it_has_description_fillable(): void
    {
        $role = new Role;

        $this->assertTrue(in_array('description', $role->getFillable()));
    }
}
