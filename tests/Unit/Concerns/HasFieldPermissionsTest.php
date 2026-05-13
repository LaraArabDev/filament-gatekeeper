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
