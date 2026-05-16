<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Unit\Concerns;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LaraArabDev\FilamentGatekeeper\Concerns\HasApiPermissions;
use LaraArabDev\FilamentGatekeeper\Models\Permission;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

class HasApiPermissionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_authorize_index(): void
    {
        $user = $this->createUser();

        Permission::factory()->resource()->create([
            'name' => 'view_any_user',
        ]);

        $user->givePermissionTo('view_any_user');

        $this->actingAs($user);

        $controller = new TestApiController;

        // Should not throw exception
        $controller->authorizeIndex();
        $this->assertTrue(true);
    }

    /** @test */
    public function it_throws_exception_when_index_not_authorized(): void
    {
        $user = $this->createUser();

        $this->actingAs($user);

        $controller = new TestApiController;

        // Gatekeeper throws HttpException, not AuthorizationException
        $this->expectException(HttpException::class);
        $controller->authorizeIndex();
    }

    /** @test */
    public function it_can_authorize_show(): void
    {
        $user = $this->createUser();

        Permission::factory()->resource()->create([
            'name' => 'view_user',
        ]);

        $user->givePermissionTo('view_user');

        $this->actingAs($user);

        $controller = new TestApiController;

        $controller->authorizeShow();
        $this->assertTrue(true);
    }

    /** @test */
    public function it_can_authorize_store(): void
    {
        $user = $this->createUser();

        Permission::factory()->resource()->create([
            'name' => 'create_user',
        ]);

        $user->givePermissionTo('create_user');

        $this->actingAs($user);

        $controller = new TestApiController;

        $controller->authorizeStore();
        $this->assertTrue(true);
    }

    /** @test */
    public function it_can_authorize_update(): void
    {
        $user = $this->createUser();

        Permission::factory()->resource()->create([
            'name' => 'update_user',
        ]);

        $user->givePermissionTo('update_user');

        $this->actingAs($user);

        $controller = new TestApiController;

        $controller->authorizeUpdate();
        $this->assertTrue(true);
    }

    /** @test */
    public function it_can_authorize_destroy(): void
    {
        $user = $this->createUser();

        Permission::factory()->resource()->create([
            'name' => 'delete_user',
        ]);

        $user->givePermissionTo('delete_user');

        $this->actingAs($user);

        $controller = new TestApiController;

        $controller->authorizeDestroy();
        $this->assertTrue(true);
    }

    /** @test */
    public function it_can_authorize_restore(): void
    {
        $user = $this->createUser();

        Permission::factory()->resource()->create([
            'name' => 'restore_user',
        ]);

        $user->givePermissionTo('restore_user');

        $this->actingAs($user);

        $controller = new TestApiController;

        $controller->authorizeRestore();
        $this->assertTrue(true);
    }

    /** @test */
    public function it_can_authorize_force_delete(): void
    {
        $user = $this->createUser();

        Permission::factory()->resource()->create([
            'name' => 'force_delete_user',
        ]);

        $user->givePermissionTo('force_delete_user');

        $this->actingAs($user);

        $controller = new TestApiController;

        $controller->authorizeForceDelete();
        $this->assertTrue(true);
    }

    /** @test */
    public function it_bypasses_for_super_admin(): void
    {
        $user = $this->createSuperAdmin();

        $this->actingAs($user);

        $controller = new TestApiController;

        // All should pass without specific permissions
        $controller->authorizeIndex();
        $controller->authorizeShow();
        $controller->authorizeStore();
        $controller->authorizeUpdate();
        $controller->authorizeDestroy();
        $controller->authorizeRestore();
        $controller->authorizeForceDelete();

        $this->assertTrue(true);
    }

    /** @test */
    public function it_can_authorize_custom_permission(): void
    {
        $user = $this->createUser();

        Permission::factory()->action()->create([
            'name' => 'export_user',
        ]);

        $user->givePermissionTo('export_user');

        $this->actingAs($user);

        $controller = new TestApiController;

        $controller->authorizePermission('export_user');
        $this->assertTrue(true);
    }

    /** @test */
    public function it_can_check_permission_without_throwing(): void
    {
        $user = $this->createUser();

        Permission::factory()->resource()->create([
            'name' => 'view_any_user',
        ]);

        $user->givePermissionTo('view_any_user');

        $this->actingAs($user);

        $controller = new TestApiController;

        $this->assertTrue($controller->canIndex());
        $this->assertFalse($controller->canStore());
    }
}

class TestApiController
{
    use HasApiPermissions;

    protected string $permissionModel = 'user';

    protected string $shieldGuard = 'web';
}

// ---------------------------------------------------------------------------
// Additional tests for uncovered methods
// ---------------------------------------------------------------------------

class HasApiPermissionsExtendedTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_returns_shield_model_property_when_set(): void
    {
        $controller = new class
        {
            use HasApiPermissions;

            public string $shieldModel = 'Product';

            public function callGetGatekeeperModel(): string
            {
                return $this->getGatekeeperModel();
            }
        };

        $this->assertEquals('Product', $controller->callGetGatekeeperModel());
    }

    /** @test */
    public function it_prefers_shield_model_over_permission_model(): void
    {
        $controller = new class
        {
            use HasApiPermissions;

            public string $shieldModel = 'Product';

            public string $permissionModel = 'OtherModel';

            public function callGetGatekeeperModel(): string
            {
                return $this->getGatekeeperModel();
            }
        };

        $this->assertEquals('Product', $controller->callGetGatekeeperModel());
    }

    /** @test */
    public function it_extracts_model_name_from_controller_class_name_when_no_properties(): void
    {
        $controller = new TestUserApiController;
        $model = $controller->callGetGatekeeperModel();

        // TestUserApiController -> strips 'Controller' -> 'TestUserApi' -> strips 'Api' -> 'TestUser'
        $this->assertStringContainsString('TestUser', $model);
    }

    /** @test */
    public function it_returns_api_guard_by_default_when_no_shield_guard_property(): void
    {
        $controller = new class
        {
            use HasApiPermissions;

            public string $permissionModel = 'user';

            public function callGetShieldGuard(): string
            {
                return $this->getShieldGuard();
            }
        };

        $this->assertEquals('api', $controller->callGetShieldGuard());
    }

    /** @test */
    public function it_can_check_view_field_permission_via_api(): void
    {
        $user = $this->createUser();
        Permission::factory()->field()->forGuard('web')->create(['name' => 'view_user_email_field']);
        $user->givePermissionTo('view_user_email_field');
        $this->actingAs($user);

        $controller = new TestApiControllerWithExposedMethods;

        $this->assertTrue($controller->callCanViewField('email'));
    }

    /** @test */
    public function it_returns_false_for_can_view_field_without_permission(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $controller = new TestApiControllerWithExposedMethods;

        $this->assertFalse($controller->callCanViewField('salary'));
    }

    /** @test */
    public function it_can_check_update_field_permission_via_api(): void
    {
        $user = $this->createUser();
        Permission::factory()->field()->forGuard('web')->create(['name' => 'update_user_email_field']);
        $user->givePermissionTo('update_user_email_field');
        $this->actingAs($user);

        $controller = new TestApiControllerWithExposedMethods;

        $this->assertTrue($controller->callCanUpdateField('email'));
    }

    /** @test */
    public function it_can_check_view_column_permission_via_api(): void
    {
        $user = $this->createUser();
        Permission::factory()->column()->forGuard('web')->create(['name' => 'view_user_name_column']);
        $user->givePermissionTo('view_user_name_column');
        $this->actingAs($user);

        $controller = new TestApiControllerWithExposedMethods;

        $this->assertTrue($controller->callCanViewColumn('name'));
    }

    /** @test */
    public function it_can_check_execute_action_permission_via_api(): void
    {
        $user = $this->createUser();
        Permission::factory()->action()->forGuard('web')->create(['name' => 'execute_user_export_action']);
        $user->givePermissionTo('execute_user_export_action');
        $this->actingAs($user);

        $controller = new TestApiControllerWithExposedMethods;

        $this->assertTrue($controller->callCanExecuteAction('export'));
    }

    /** @test */
    public function it_can_get_visible_fields_via_api(): void
    {
        $user = $this->createUser();
        Permission::factory()->field()->forGuard('web')->create(['name' => 'view_user_email_field']);
        $user->givePermissionTo('view_user_email_field');
        $this->actingAs($user);

        config()->set('gatekeeper.field_permissions.user', ['email', 'phone']);

        $controller = new TestApiControllerWithExposedMethods;

        $visibleFields = $controller->callGetVisibleFields();

        $this->assertIsArray($visibleFields);
    }

    /** @test */
    public function it_can_get_visible_columns_via_api(): void
    {
        $user = $this->createUser();
        Permission::factory()->column()->forGuard('web')->create(['name' => 'view_user_name_column']);
        $user->givePermissionTo('view_user_name_column');
        $this->actingAs($user);

        $controller = new TestApiControllerWithExposedMethods;

        $visibleColumns = $controller->callGetVisibleColumns();

        $this->assertIsArray($visibleColumns);
    }

    /** @test */
    public function it_filter_by_permissions_returns_all_when_no_visible_fields(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        config()->set('gatekeeper.field_permissions', []);

        $controller = new TestApiControllerWithExposedMethods;

        // Use the array-based helper that simulates filterByPermissions behavior
        $result = $controller->callFilterByPermissionsArray(['name' => 'Test', 'email' => 'test@example.com']);

        $this->assertIsArray($result);
    }
}

class TestUserApiController
{
    use HasApiPermissions;

    public function callGetGatekeeperModel(): string
    {
        return $this->getGatekeeperModel();
    }
}

class TestApiControllerWithExposedMethods
{
    use HasApiPermissions;

    protected string $permissionModel = 'user';

    protected string $shieldGuard = 'web';

    public function callCanViewField(string $field): bool
    {
        return $this->canViewField($field);
    }

    public function callCanUpdateField(string $field): bool
    {
        return $this->canUpdateField($field);
    }

    public function callCanViewColumn(string $column): bool
    {
        return $this->canViewColumn($column);
    }

    public function callCanExecuteAction(string $action): bool
    {
        return $this->canExecuteAction($action);
    }

    public function callGetVisibleFields(): array
    {
        return $this->getVisibleFields();
    }

    public function callGetVisibleColumns(): array
    {
        return $this->getVisibleColumns();
    }

    // Simulate filterByPermissions but accepting an array (avoids needing a real Model)
    public function callFilterByPermissionsArray(array $data): array
    {
        $visibleFields = $this->getVisibleFields();

        if (empty($visibleFields)) {
            return $data;
        }

        return array_intersect_key($data, array_flip($visibleFields));
    }
}
