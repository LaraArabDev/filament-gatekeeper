<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Unit\Concerns;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LaraArabDev\FilamentGatekeeper\Concerns\HasFieldPermissions;
use LaraArabDev\FilamentGatekeeper\Models\Permission;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;

class HasFieldPermissionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_check_view_field_permission(): void
    {
        $user = $this->createUser();

        Permission::factory()->field()->create([
            'name' => 'view_test_model_email_field',
        ]);

        $user->givePermissionTo('view_test_model_email_field');

        $this->actingAs($user);

        $testClass = $this->createTestResourceWithFieldPermissions();

        $this->assertTrue($testClass::canViewField('email'));
    }

    /** @test */
    public function it_denies_view_field_without_permission(): void
    {
        $user = $this->createUser();

        $this->actingAs($user);

        $testClass = $this->createTestResourceWithFieldPermissions();

        $this->assertFalse($testClass::canViewField('email'));
    }

    /** @test */
    public function it_can_check_update_field_permission(): void
    {
        $user = $this->createUser();

        Permission::factory()->field()->create([
            'name' => 'update_test_model_email_field',
        ]);

        $user->givePermissionTo('update_test_model_email_field');

        $this->actingAs($user);

        $testClass = $this->createTestResourceWithFieldPermissions();

        $this->assertTrue($testClass::canUpdateField('email'));
    }

    /** @test */
    public function it_can_check_multiple_field_permissions(): void
    {
        $user = $this->createUser();

        Permission::factory()->field()->create(['name' => 'view_test_model_email_field']);
        Permission::factory()->field()->create(['name' => 'view_test_model_phone_field']);
        Permission::factory()->field()->create(['name' => 'update_test_model_email_field']);

        $user->givePermissionTo([
            'view_test_model_email_field',
            'view_test_model_phone_field',
            'update_test_model_email_field',
        ]);

        $this->actingAs($user);

        $testClass = $this->createTestResourceWithFieldPermissions();

        $this->assertTrue($testClass::canViewField('email'));
        $this->assertTrue($testClass::canViewField('phone'));
        $this->assertTrue($testClass::canUpdateField('email'));
        $this->assertFalse($testClass::canUpdateField('phone'));
        $this->assertFalse($testClass::canViewField('salary'));
    }

    /** @test */
    public function it_bypasses_field_permissions_for_super_admin(): void
    {
        $user = $this->createSuperAdmin();

        $this->actingAs($user);

        $testClass = $this->createTestResourceWithFieldPermissions();

        $this->assertTrue($testClass::canViewField('email'));
        $this->assertTrue($testClass::canViewField('salary'));
        $this->assertTrue($testClass::canUpdateField('email'));
        $this->assertTrue($testClass::canUpdateField('salary'));
    }

    /** @test */
    public function it_generates_correct_field_permission_names(): void
    {
        $testClass = $this->createTestResourceWithFieldPermissions();

        $this->assertEquals('view_test_model_email_field', $testClass::getFieldPermissionName('view', 'email'));
        $this->assertEquals('update_test_model_email_field', $testClass::getFieldPermissionName('update', 'email'));
    }

    /** @test */
    public function it_can_get_all_field_permissions(): void
    {
        config()->set('gatekeeper.field_permissions.TestModel', ['email', 'phone', 'salary']);

        $testClass = $this->createTestResourceWithFieldPermissions();

        $permissions = $testClass::getAllFieldPermissions();

        $this->assertContains('view_test_model_email_field', $permissions);
        $this->assertContains('update_test_model_email_field', $permissions);
        $this->assertContains('view_test_model_phone_field', $permissions);
        $this->assertContains('update_test_model_phone_field', $permissions);
    }

    /** @test */
    public function it_can_get_visible_fields(): void
    {
        $user = $this->createUser();

        Permission::factory()->field()->create(['name' => 'view_test_model_email_field']);
        Permission::factory()->field()->create(['name' => 'view_test_model_phone_field']);

        $user->givePermissionTo(['view_test_model_email_field', 'view_test_model_phone_field']);

        $this->actingAs($user);

        $testClass = $this->createTestResourceWithFieldPermissions();

        config()->set('gatekeeper.field_permissions.TestModel', ['email', 'phone', 'salary']);

        $visibleFields = $testClass::getVisibleFields();

        $this->assertContains('email', $visibleFields);
        $this->assertContains('phone', $visibleFields);
        $this->assertNotContains('salary', $visibleFields);
    }

    /** @test */
    public function it_can_get_editable_fields(): void
    {
        $user = $this->createUser();

        Permission::factory()->field()->create([
            'name' => 'update_test_model_email_field',
        ]);

        $user->givePermissionTo('update_test_model_email_field');

        $this->actingAs($user);

        $testClass = $this->createTestResourceWithFieldPermissions();

        config()->set('gatekeeper.field_permissions.TestModel', ['email', 'phone', 'salary']);

        $editableFields = $testClass::getEditableFields();

        $this->assertContains('email', $editableFields);
        $this->assertNotContains('phone', $editableFields);
        $this->assertNotContains('salary', $editableFields);
    }

    protected function createTestResourceWithFieldPermissions(): string
    {
        return TestResourceWithFields::class;
    }
}

class TestResourceWithFields
{
    use HasFieldPermissions;

    protected static ?string $model = TestModelForFields::class;

    public static function getModelName(): string
    {
        return 'TestModel';
    }
}

class TestModelForFields
{
    //
}

// ---------------------------------------------------------------------------
// Additional tests for uncovered methods
// ---------------------------------------------------------------------------

class HasFieldPermissionsExtendedTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_returns_true_for_is_field_disabled_when_no_update_permission(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $this->assertTrue(TestResourceWithFields::isFieldDisabled('email'));
    }

    /** @test */
    public function it_returns_false_for_is_field_disabled_when_has_update_permission(): void
    {
        $user = $this->createUser();
        Permission::factory()->field()->create(['name' => 'update_test_model_email_field']);
        $user->givePermissionTo('update_test_model_email_field');
        $this->actingAs($user);

        $this->assertFalse(TestResourceWithFields::isFieldDisabled('email'));
    }

    /** @test */
    public function it_returns_true_for_is_field_hidden_when_no_view_permission(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $this->assertTrue(TestResourceWithFields::isFieldHidden('email'));
    }

    /** @test */
    public function it_returns_false_for_is_field_hidden_when_has_view_permission(): void
    {
        $user = $this->createUser();
        Permission::factory()->field()->create(['name' => 'view_test_model_email_field']);
        $user->givePermissionTo('view_test_model_email_field');
        $this->actingAs($user);

        $this->assertFalse(TestResourceWithFields::isFieldHidden('email'));
    }

    /** @test */
    public function it_applies_field_permissions_to_objects_with_get_name_visible_and_disabled(): void
    {
        $user = $this->createUser();
        Permission::factory()->field()->create(['name' => 'view_test_model_salary_field']);
        Permission::factory()->field()->create(['name' => 'update_test_model_salary_field']);
        $user->givePermissionTo(['view_test_model_salary_field', 'update_test_model_salary_field']);
        $this->actingAs($user);

        $component = new class {
            public string $name = 'salary';
            public mixed $visibleCallback = null;
            public mixed $disabledCallback = null;

            public function getName(): string { return $this->name; }
            public function visible(callable $callback): static { $this->visibleCallback = $callback; return $this; }
            public function disabled(callable $callback): static { $this->disabledCallback = $callback; return $this; }
        };

        $result = TestResourceWithFields::applyFieldPermissions([$component]);

        $this->assertCount(1, $result);
        $this->assertNotNull($component->visibleCallback);
        $this->assertNotNull($component->disabledCallback);
    }

    /** @test */
    public function it_applies_field_permissions_to_plain_objects_without_get_name(): void
    {
        $plainObject = new \stdClass();

        $result = TestResourceWithFields::applyFieldPermissions([$plainObject]);

        $this->assertCount(1, $result);
        $this->assertSame($plainObject, $result[0]);
    }

    /** @test */
    public function it_returns_all_configured_fields_for_super_admin_in_get_editable_fields(): void
    {
        $user = $this->createSuperAdmin();
        $this->actingAs($user);

        config()->set('gatekeeper.field_permissions.TestModel', ['email', 'phone', 'salary']);

        $editableFields = TestResourceWithFields::getEditableFields();

        $this->assertContains('email', $editableFields);
        $this->assertContains('phone', $editableFields);
        $this->assertContains('salary', $editableFields);
    }

    /** @test */
    public function it_returns_empty_array_for_get_editable_fields_when_no_fields_configured(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        config()->set('gatekeeper.field_permissions', []);

        $editableFields = TestResourceWithFields::getEditableFields();

        $this->assertEmpty($editableFields);
    }

    /** @test */
    public function it_returns_all_configured_fields_for_super_admin_in_get_visible_fields(): void
    {
        $user = $this->createSuperAdmin();
        $this->actingAs($user);

        config()->set('gatekeeper.field_permissions.TestModel', ['email', 'phone', 'salary']);

        $visibleFields = TestResourceWithFields::getVisibleFields();

        $this->assertContains('email', $visibleFields);
        $this->assertContains('phone', $visibleFields);
        $this->assertContains('salary', $visibleFields);
    }

    /** @test */
    public function it_uses_model_property_for_field_permission_model_name_when_no_get_model_name(): void
    {
        $name = TestResourceFieldsWithModelProp::getFieldPermissionName('view', 'email');
        $this->assertStringContainsString('test_model_for_fields_prop', $name);
    }

    /** @test */
    public function it_falls_back_to_class_basename_for_field_permission_model_name(): void
    {
        $name = TestResourceFieldsNoModel::getFieldPermissionName('view', 'email');
        $this->assertStringContainsString('test_resource_fields_no_model', $name);
    }
}

class TestResourceFieldsWithModelProp
{
    use HasFieldPermissions;

    protected static string $model = TestModelForFieldsProp::class;
}

class TestModelForFieldsProp {}

class TestResourceFieldsNoModel
{
    use HasFieldPermissions;
    // No $model property, no getModelName()
}
