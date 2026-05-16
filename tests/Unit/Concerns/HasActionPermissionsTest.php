<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Unit\Concerns;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LaraArabDev\FilamentGatekeeper\Concerns\HasActionPermissions;
use LaraArabDev\FilamentGatekeeper\Models\Permission;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class HasActionPermissionsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_check_execute_action_permission(): void
    {
        $user = $this->createUser();

        Permission::factory()->action()->create([
            'name' => 'execute_test_model_export_action',
        ]);

        $user->givePermissionTo('execute_test_model_export_action');

        $this->actingAs($user);

        $testClass = $this->createTestResourceWithActionPermissions();

        $this->assertTrue($testClass::canExecuteAction('export'));
    }

    #[Test]
    public function it_denies_execute_action_without_permission(): void
    {
        $user = $this->createUser();

        $this->actingAs($user);

        $testClass = $this->createTestResourceWithActionPermissions();

        $this->assertFalse($testClass::canExecuteAction('export'));
    }

    #[Test]
    public function it_can_check_multiple_action_permissions(): void
    {
        $user = $this->createUser();

        Permission::factory()->action()->create(['name' => 'execute_test_model_export_action']);
        Permission::factory()->action()->create(['name' => 'execute_test_model_import_action']);

        $user->givePermissionTo(['execute_test_model_export_action', 'execute_test_model_import_action']);

        $this->actingAs($user);

        $testClass = $this->createTestResourceWithActionPermissions();

        $this->assertTrue($testClass::canExecuteAction('export'));
        $this->assertTrue($testClass::canExecuteAction('import'));
        $this->assertFalse($testClass::canExecuteAction('delete'));
    }

    #[Test]
    public function it_bypasses_action_permissions_for_super_admin(): void
    {
        $user = $this->createSuperAdmin();

        $this->actingAs($user);

        $testClass = $this->createTestResourceWithActionPermissions();

        $this->assertTrue($testClass::canExecuteAction('export'));
        $this->assertTrue($testClass::canExecuteAction('import'));
        $this->assertTrue($testClass::canExecuteAction('any_action'));
    }

    #[Test]
    public function it_generates_correct_action_permission_names(): void
    {
        $testClass = $this->createTestResourceWithActionPermissions();

        $this->assertEquals('execute_test_model_export_action', $testClass::getActionPermissionName('export'));
        $this->assertEquals('execute_test_model_import_action', $testClass::getActionPermissionName('import'));
    }

    #[Test]
    public function it_can_get_all_action_permissions(): void
    {
        config()->set('gatekeeper.custom_actions.TestModel', ['export', 'import', 'approve']);

        $testClass = $this->createTestResourceWithActionPermissions();

        $permissions = $testClass::getAllActionPermissions();

        $this->assertContains('execute_test_model_export_action', $permissions);
        $this->assertContains('execute_test_model_import_action', $permissions);
        $this->assertContains('execute_test_model_approve_action', $permissions);
    }

    #[Test]
    public function it_can_get_permitted_actions(): void
    {
        $user = $this->createUser();

        Permission::factory()->action()->create(['name' => 'execute_test_model_export_action']);
        Permission::factory()->action()->create(['name' => 'execute_test_model_import_action']);

        $user->givePermissionTo(['execute_test_model_export_action', 'execute_test_model_import_action']);

        $this->actingAs($user);

        $testClass = $this->createTestResourceWithActionPermissions();

        config()->set('gatekeeper.custom_actions.TestModel', ['export', 'import', 'approve']);

        $permittedActions = $testClass::getPermittedActions();

        $this->assertContains('export', $permittedActions);
        $this->assertContains('import', $permittedActions);
        $this->assertNotContains('approve', $permittedActions);
    }

    protected function createTestResourceWithActionPermissions(): string
    {
        return TestResourceWithActions::class;
    }
}

class TestResourceWithActions
{
    use HasActionPermissions;

    protected static ?string $model = TestModelForActions::class;

    public static function getModelName(): string
    {
        return 'TestModel';
    }
}

class TestModelForActions
{
    //
}

// ---------------------------------------------------------------------------
// Additional tests for uncovered methods
// ---------------------------------------------------------------------------

class HasActionPermissionsExtendedTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_returns_true_for_is_action_hidden_when_no_permission(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $this->assertTrue(TestResourceWithActions::isActionHidden('export'));
    }

    #[Test]
    public function it_returns_false_for_is_action_hidden_when_has_permission(): void
    {
        $user = $this->createUser();

        Permission::factory()->action()->create(['name' => 'execute_test_model_export_action']);
        $user->givePermissionTo('execute_test_model_export_action');

        $this->actingAs($user);

        $this->assertFalse(TestResourceWithActions::isActionHidden('export'));
    }

    #[Test]
    public function it_returns_all_configured_actions_for_super_admin_in_get_available_actions(): void
    {
        $user = $this->createSuperAdmin();
        $this->actingAs($user);

        config()->set('gatekeeper.custom_actions.TestModel', ['export', 'import', 'approve']);

        $actions = TestResourceWithActions::getAvailableActions();

        $this->assertContains('export', $actions);
        $this->assertContains('import', $actions);
        $this->assertContains('approve', $actions);
    }

    #[Test]
    public function it_filters_actions_for_regular_user_in_get_available_actions(): void
    {
        $user = $this->createUser();
        Permission::factory()->action()->create(['name' => 'execute_test_model_export_action']);
        $user->givePermissionTo('execute_test_model_export_action');
        $this->actingAs($user);

        config()->set('gatekeeper.custom_actions.TestModel', ['export', 'import']);

        $actions = TestResourceWithActions::getAvailableActions();

        $this->assertContains('export', $actions);
        $this->assertNotContains('import', $actions);
    }

    #[Test]
    public function it_applies_action_permissions_to_objects_with_get_name_and_visible(): void
    {
        $user = $this->createUser();
        Permission::factory()->action()->create(['name' => 'execute_test_model_export_action']);
        $user->givePermissionTo('execute_test_model_export_action');
        $this->actingAs($user);

        $action = new class
        {
            public string $name = 'export';

            public mixed $visibleCallback = null;

            public function getName(): string
            {
                return $this->name;
            }

            public function visible(callable $callback): static
            {
                $this->visibleCallback = $callback;

                return $this;
            }
        };

        $result = TestResourceWithActions::applyActionPermissions([$action]);

        $this->assertCount(1, $result);
        $this->assertNotNull($action->visibleCallback);
    }

    #[Test]
    public function it_applies_action_permissions_to_plain_objects_without_get_name(): void
    {
        $plainObject = new \stdClass;

        $result = TestResourceWithActions::applyActionPermissions([$plainObject]);

        $this->assertCount(1, $result);
        $this->assertSame($plainObject, $result[0]);
    }

    #[Test]
    public function it_filters_actions_by_permission_for_objects_with_get_name(): void
    {
        $user = $this->createUser();
        Permission::factory()->action()->create(['name' => 'execute_test_model_export_action']);
        $user->givePermissionTo('execute_test_model_export_action');
        $this->actingAs($user);

        $allowedAction = new class
        {
            public function getName(): string
            {
                return 'export';
            }
        };
        $deniedAction = new class
        {
            public function getName(): string
            {
                return 'import';
            }
        };

        $result = TestResourceWithActions::filterActions([$allowedAction, $deniedAction]);

        $this->assertCount(1, array_values($result));
        $this->assertSame($allowedAction, array_values($result)[0]);
    }

    #[Test]
    public function it_keeps_plain_objects_without_get_name_in_filter_actions(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $plainObject = new \stdClass;

        $result = TestResourceWithActions::filterActions([$plainObject]);

        $this->assertCount(1, $result);
    }

    #[Test]
    public function it_can_check_bulk_action_permission(): void
    {
        $user = $this->createUser();
        Permission::factory()->action()->create(['name' => 'execute_test_model_bulk_delete_action']);
        $user->givePermissionTo('execute_test_model_bulk_delete_action');
        $this->actingAs($user);

        $this->assertTrue(TestResourceWithActions::canExecuteBulkAction('delete'));
    }

    #[Test]
    public function it_denies_bulk_action_without_permission(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $this->assertFalse(TestResourceWithActions::canExecuteBulkAction('delete'));
    }

    #[Test]
    public function it_uses_model_property_for_action_permission_model_name_when_no_get_model_name(): void
    {
        // Class has $model property but NO getModelName() method
        $name = TestResourceActionsWithModelProp::getActionPermissionName('export');
        $this->assertStringContainsString('test_model_for_actions_prop', $name);
    }

    #[Test]
    public function it_falls_back_to_class_basename_for_action_permission_model_name(): void
    {
        // Class has neither getModelName() nor $model property
        $name = TestResourceActionsNoModel::getActionPermissionName('export');
        $this->assertStringContainsString('test_resource_actions_no_model', $name);
    }
}

class TestResourceActionsWithModelProp
{
    use HasActionPermissions;

    // Has $model but NO getModelName() method
    protected static string $model = TestModelForActionsProp::class;
}

class TestModelForActionsProp {}

class TestResourceActionsNoModel
{
    use HasActionPermissions;
    // No $model property, no getModelName() method
}
