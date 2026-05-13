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
