<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Unit\Concerns;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LaraArabDev\FilamentGatekeeper\Concerns\HasActionPermissions;
use LaraArabDev\FilamentGatekeeper\Models\Permission;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;

class HasActionPermissionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
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

    /** @test */
    public function it_denies_execute_action_without_permission(): void
    {
        $user = $this->createUser();

        $this->actingAs($user);

        $testClass = $this->createTestResourceWithActionPermissions();

        $this->assertFalse($testClass::canExecuteAction('export'));
    }

    /** @test */
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

    /** @test */
    public function it_bypasses_action_permissions_for_super_admin(): void
    {
        $user = $this->createSuperAdmin();

        $this->actingAs($user);

        $testClass = $this->createTestResourceWithActionPermissions();

        $this->assertTrue($testClass::canExecuteAction('export'));
        $this->assertTrue($testClass::canExecuteAction('import'));
        $this->assertTrue($testClass::canExecuteAction('any_action'));
    }

    /** @test */
    public function it_generates_correct_action_permission_names(): void
    {
        $testClass = $this->createTestResourceWithActionPermissions();

        $this->assertEquals('execute_test_model_export_action', $testClass::getActionPermissionName('export'));
        $this->assertEquals('execute_test_model_import_action', $testClass::getActionPermissionName('import'));
    }

    /** @test */
    public function it_can_get_all_action_permissions(): void
    {
        config()->set('gatekeeper.custom_actions.TestModel', ['export', 'import', 'approve']);

        $testClass = $this->createTestResourceWithActionPermissions();

        $permissions = $testClass::getAllActionPermissions();

        $this->assertContains('execute_test_model_export_action', $permissions);
        $this->assertContains('execute_test_model_import_action', $permissions);
        $this->assertContains('execute_test_model_approve_action', $permissions);
    }

    /** @test */
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
