<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LaraArabDev\FilamentGatekeeper\Facades\Gatekeeper;
use LaraArabDev\FilamentGatekeeper\Models\Permission;
use LaraArabDev\FilamentGatekeeper\Models\Role;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;
use Spatie\Permission\PermissionRegistrar;

class PermissionFlowTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function complete_permission_flow(): void
    {
        // Step 1: Sync permissions
        config()->set('gatekeeper.super_admin.enabled', true);
        config()->set('gatekeeper.field_permissions', ['User' => ['email', 'salary']]);
        config()->set('gatekeeper.column_permissions', ['User' => ['email', 'salary']]);
        config()->set('gatekeeper.custom_actions', ['User' => ['export']]);
        config()->set('gatekeeper.relation_permissions', ['User' => ['roles']]);

        $this->artisan('gatekeeper:sync')->assertExitCode(0);

        // Step 2: Create roles using factory
        $adminRole = Role::factory()->create(['name' => 'admin']);
        $editorRole = Role::factory()->create(['name' => 'editor']);

        // Step 3: Assign permissions to roles
        $adminRole->givePermissionTo([
            'view_user_email_field',
            'view_user_salary_field',
            'update_user_email_field',
            'view_user_email_column',
            'view_user_salary_column',
            'execute_user_export_action',
            'view_user_roles_relation',
        ]);

        $editorRole->givePermissionTo([
            'view_user_email_field',
            'view_user_email_column',
        ]);

        // Step 4: Create users using factory
        $admin = $this->createUser(['email' => 'admin@example.com']);
        $editor = $this->createUser(['email' => 'editor@example.com']);
        $guest = $this->createUser(['email' => 'guest@example.com']);

        // Step 5: Assign roles to users
        $admin->assignRole($adminRole);
        $editor->assignRole($editorRole);

        // Step 6: Test permissions for admin
        $this->actingAs($admin);

        $this->assertTrue(Gatekeeper::can('view_user_email_field'));
        $this->assertTrue(Gatekeeper::can('view_user_salary_field'));
        $this->assertTrue(Gatekeeper::can('update_user_email_field'));
        $this->assertTrue(Gatekeeper::can('view_user_email_column'));
        $this->assertTrue(Gatekeeper::can('view_user_salary_column'));
        $this->assertTrue(Gatekeeper::can('execute_user_export_action'));
        $this->assertTrue(Gatekeeper::can('view_user_roles_relation'));

        // Step 7: Test permissions for editor
        $this->actingAs($editor);

        $this->assertTrue(Gatekeeper::can('view_user_email_field'));
        $this->assertFalse(Gatekeeper::can('view_user_salary_field'));
        $this->assertFalse(Gatekeeper::can('update_user_email_field'));
        $this->assertTrue(Gatekeeper::can('view_user_email_column'));
        $this->assertFalse(Gatekeeper::can('view_user_salary_column'));
        $this->assertFalse(Gatekeeper::can('execute_user_export_action'));
        $this->assertFalse(Gatekeeper::can('view_user_roles_relation'));

        // Step 8: Test permissions for guest (no role)
        $this->actingAs($guest);

        $this->assertFalse(Gatekeeper::can('view_user_email_field'));
        $this->assertFalse(Gatekeeper::can('view_user_salary_field'));
        $this->assertFalse(Gatekeeper::can('view_user_email_column'));
    }

    /** @test */
    public function super_admin_has_all_permissions(): void
    {
        // Setup
        config()->set('gatekeeper.super_admin.enabled', true);
        config()->set('gatekeeper.super_admin.role', 'super-admin');
        config()->set('gatekeeper.field_permissions', ['User' => ['email', 'salary']]);

        $this->artisan('gatekeeper:sync')->assertExitCode(0);

        // Get the super admin role that was created by sync, or create it if it doesn't exist
        $superAdminRole = Role::where('name', 'super-admin')
            ->where('guard_name', 'web')
            ->first();

        if (! $superAdminRole) {
            $superAdminRole = Role::factory()->superAdmin()->create();
        }

        $user = $this->createUser();
        $user->assignRole($superAdminRole);

        $this->actingAs($user);

        // Super admin should have all permissions
        $this->assertTrue(Gatekeeper::can('view_user_email_field'));
        $this->assertTrue(Gatekeeper::can('view_user_salary_field'));
        $this->assertTrue(Gatekeeper::can('any_permission_that_does_not_exist'));
        $this->assertTrue(Gatekeeper::shouldBypassPermissions());
    }

    /** @test */
    public function role_permission_syncing(): void
    {
        // Create permissions using factory
        Permission::factory()->resource()->create(['name' => 'permission_1']);
        Permission::factory()->resource()->create(['name' => 'permission_2']);
        Permission::factory()->resource()->create(['name' => 'permission_3']);

        // Create role using factory
        $role = Role::factory()->create(['name' => 'test-role']);

        // Give initial permissions
        $role->givePermissionTo(['permission_1', 'permission_2']);

        $user = $this->createUser();
        $user->assignRole($role);

        $this->actingAs($user);

        $this->assertTrue(Gatekeeper::can('permission_1'));
        $this->assertTrue(Gatekeeper::can('permission_2'));
        $this->assertFalse(Gatekeeper::can('permission_3'));

        // Sync permissions (add permission_3, remove permission_1)
        $role->syncPermissions(['permission_2', 'permission_3']);

        // Clear cache
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Refresh user
        $user->refresh();

        $this->assertFalse(Gatekeeper::can('permission_1'));
        $this->assertTrue(Gatekeeper::can('permission_2'));
        $this->assertTrue(Gatekeeper::can('permission_3'));
    }

    /** @test */
    public function multiple_roles_combine_permissions(): void
    {
        Permission::factory()->resource()->create(['name' => 'role1_permission']);
        Permission::factory()->resource()->create(['name' => 'role2_permission']);
        Permission::factory()->resource()->create(['name' => 'shared_permission']);

        $role1 = Role::factory()->create(['name' => 'role1']);
        $role2 = Role::factory()->create(['name' => 'role2']);

        $role1->givePermissionTo(['role1_permission', 'shared_permission']);
        $role2->givePermissionTo(['role2_permission', 'shared_permission']);

        $user = $this->createUser();
        $user->assignRole([$role1, $role2]);

        $this->actingAs($user);

        // User should have permissions from both roles
        $this->assertTrue(Gatekeeper::can('role1_permission'));
        $this->assertTrue(Gatekeeper::can('role2_permission'));
        $this->assertTrue(Gatekeeper::can('shared_permission'));
    }

    /** @test */
    public function guard_specific_permissions(): void
    {
        Permission::factory()->forGuard('web')->resource()->create(['name' => 'web_permission']);
        Permission::factory()->forGuard('api')->resource()->create(['name' => 'api_permission']);

        $webRole = Role::factory()->forGuard('web')->create(['name' => 'web-role']);
        $webRole->givePermissionTo('web_permission');

        $user = $this->createUser();
        $user->assignRole($webRole);

        $this->actingAs($user);

        // Web guard should work
        $this->assertTrue(Gatekeeper::guard('web')->can('web_permission'));

        // API guard permission should not be accessible via web role
        $this->assertFalse(Gatekeeper::guard('api')->can('api_permission'));
    }
}
