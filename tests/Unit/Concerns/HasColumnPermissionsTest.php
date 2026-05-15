<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Unit\Concerns;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LaraArabDev\FilamentGatekeeper\Concerns\HasColumnPermissions;
use LaraArabDev\FilamentGatekeeper\Models\Permission;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;

class HasColumnPermissionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_check_view_column_permission(): void
    {
        $user = $this->createUser();

        Permission::factory()->column()->create([
            'name' => 'view_test_model_email_column',
        ]);

        $user->givePermissionTo('view_test_model_email_column');

        $this->actingAs($user);

        $testClass = $this->createTestResourceWithColumnPermissions();

        $this->assertTrue($testClass::canViewColumn('email'));
    }

    /** @test */
    public function it_denies_view_column_without_permission(): void
    {
        $user = $this->createUser();

        $this->actingAs($user);

        $testClass = $this->createTestResourceWithColumnPermissions();

        $this->assertFalse($testClass::canViewColumn('email'));
    }

    /** @test */
    public function it_can_check_multiple_column_permissions(): void
    {
        $user = $this->createUser();

        Permission::factory()->column()->create(['name' => 'view_test_model_email_column']);
        Permission::factory()->column()->create(['name' => 'view_test_model_phone_column']);

        $user->givePermissionTo(['view_test_model_email_column', 'view_test_model_phone_column']);

        $this->actingAs($user);

        $testClass = $this->createTestResourceWithColumnPermissions();

        $this->assertTrue($testClass::canViewColumn('email'));
        $this->assertTrue($testClass::canViewColumn('phone'));
        $this->assertFalse($testClass::canViewColumn('salary'));
    }

    /** @test */
    public function it_bypasses_column_permissions_for_super_admin(): void
    {
        $user = $this->createSuperAdmin();

        $this->actingAs($user);

        $testClass = $this->createTestResourceWithColumnPermissions();

        $this->assertTrue($testClass::canViewColumn('email'));
        $this->assertTrue($testClass::canViewColumn('salary'));
        $this->assertTrue($testClass::canViewColumn('any_column'));
    }

    /** @test */
    public function it_generates_correct_column_permission_names(): void
    {
        $testClass = $this->createTestResourceWithColumnPermissions();

        $this->assertEquals('view_test_model_email_column', $testClass::getColumnPermissionName('email'));
    }

    /** @test */
    public function it_can_get_all_column_permissions(): void
    {
        config()->set('gatekeeper.column_permissions.TestModel', ['email', 'phone', 'created_at']);

        $testClass = $this->createTestResourceWithColumnPermissions();

        $permissions = $testClass::getAllColumnPermissions();

        $this->assertContains('view_test_model_email_column', $permissions);
        $this->assertContains('view_test_model_phone_column', $permissions);
        $this->assertContains('view_test_model_created_at_column', $permissions);
    }

    /** @test */
    public function it_can_get_visible_columns(): void
    {
        $user = $this->createUser();

        Permission::factory()->column()->create(['name' => 'view_test_model_email_column']);
        Permission::factory()->column()->create(['name' => 'view_test_model_phone_column']);

        $user->givePermissionTo(['view_test_model_email_column', 'view_test_model_phone_column']);

        $this->actingAs($user);

        $testClass = $this->createTestResourceWithColumnPermissions();

        config()->set('gatekeeper.column_permissions.TestModel', ['email', 'phone', 'salary']);

        $visibleColumns = $testClass::getVisibleColumns();

        $this->assertContains('email', $visibleColumns);
        $this->assertContains('phone', $visibleColumns);
        $this->assertNotContains('salary', $visibleColumns);
    }

    protected function createTestResourceWithColumnPermissions(): string
    {
        return TestResourceWithColumns::class;
    }
}

class TestResourceWithColumns
{
    use HasColumnPermissions;

    protected static ?string $model = TestModelForColumns::class;

    public static function getModelName(): string
    {
        return 'TestModel';
    }
}

class TestModelForColumns
{
    //
}

// ---------------------------------------------------------------------------
// Additional tests for uncovered methods
// ---------------------------------------------------------------------------

class HasColumnPermissionsExtendedTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_returns_true_for_is_column_hidden_when_no_view_permission(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $this->assertTrue(TestResourceWithColumns::isColumnHidden('email'));
    }

    /** @test */
    public function it_returns_false_for_is_column_hidden_when_has_view_permission(): void
    {
        $user = $this->createUser();
        Permission::factory()->column()->create(['name' => 'view_test_model_email_column']);
        $user->givePermissionTo('view_test_model_email_column');
        $this->actingAs($user);

        $this->assertFalse(TestResourceWithColumns::isColumnHidden('email'));
    }

    /** @test */
    public function it_applies_column_permissions_to_objects_with_get_name_and_visible(): void
    {
        $user = $this->createUser();
        Permission::factory()->column()->create(['name' => 'view_test_model_email_column']);
        $user->givePermissionTo('view_test_model_email_column');
        $this->actingAs($user);

        $column = new class {
            public string $name = 'email';
            public mixed $visibleCallback = null;

            public function getName(): string { return $this->name; }
            public function visible(callable $callback): static { $this->visibleCallback = $callback; return $this; }
        };

        $result = TestResourceWithColumns::applyColumnPermissions([$column]);

        $this->assertCount(1, $result);
        $this->assertNotNull($column->visibleCallback);
    }

    /** @test */
    public function it_applies_column_permissions_to_plain_objects_without_get_name(): void
    {
        $plainObject = new \stdClass();

        $result = TestResourceWithColumns::applyColumnPermissions([$plainObject]);

        $this->assertCount(1, $result);
        $this->assertSame($plainObject, $result[0]);
    }

    /** @test */
    public function it_filters_columns_by_permission_for_objects_with_get_name(): void
    {
        $user = $this->createUser();
        Permission::factory()->column()->create(['name' => 'view_test_model_email_column']);
        $user->givePermissionTo('view_test_model_email_column');
        $this->actingAs($user);

        $allowedColumn = new class {
            public function getName(): string { return 'email'; }
        };
        $deniedColumn = new class {
            public function getName(): string { return 'salary'; }
        };

        $result = TestResourceWithColumns::filterColumns([$allowedColumn, $deniedColumn]);

        $this->assertCount(1, array_values($result));
        $this->assertSame($allowedColumn, array_values($result)[0]);
    }

    /** @test */
    public function it_keeps_plain_objects_without_get_name_in_filter_columns(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $plainObject = new \stdClass();

        $result = TestResourceWithColumns::filterColumns([$plainObject]);

        $this->assertCount(1, $result);
    }

    /** @test */
    public function it_returns_all_configured_columns_for_super_admin_in_get_visible_columns(): void
    {
        $user = $this->createSuperAdmin();
        $this->actingAs($user);

        config()->set('gatekeeper.column_permissions.TestModel', ['email', 'phone', 'salary']);

        $visibleColumns = TestResourceWithColumns::getVisibleColumns();

        $this->assertContains('email', $visibleColumns);
        $this->assertContains('phone', $visibleColumns);
        $this->assertContains('salary', $visibleColumns);
    }

    /** @test */
    public function it_uses_model_property_for_column_permission_model_name_when_no_get_model_name(): void
    {
        $name = TestResourceColumnsWithModelProp::getColumnPermissionName('email');
        $this->assertStringContainsString('test_model_for_columns_prop', $name);
    }

    /** @test */
    public function it_falls_back_to_class_basename_for_column_permission_model_name(): void
    {
        $name = TestResourceColumnsNoModel::getColumnPermissionName('email');
        $this->assertStringContainsString('test_resource_columns_no_model', $name);
    }
}

class TestResourceColumnsWithModelProp
{
    use HasColumnPermissions;

    protected static string $model = TestModelForColumnsProp::class;
}

class TestModelForColumnsProp {}

class TestResourceColumnsNoModel
{
    use HasColumnPermissions;
    // No $model property, no getModelName()
}
