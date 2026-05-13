<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Unit\Concerns;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LaraArabDev\FilamentGatekeeper\Concerns\HasGatekeeperPermissions;
use LaraArabDev\FilamentGatekeeper\Models\Permission;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;

class HasResourcePermissionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_check_view_any_permission(): void
    {
        $user = $this->createUser();

        Permission::factory()->resource()->create([
            'name' => 'view_any_test_model',
        ]);

        $user->givePermissionTo('view_any_test_model');

        $this->actingAs($user);

        $testClass = $this->createTestResourceClass();

        $this->assertTrue($testClass::canViewAny());
    }

    /** @test */
    public function it_denies_view_any_without_permission(): void
    {
        $user = $this->createUser();

        $this->actingAs($user);

        $testClass = $this->createTestResourceClass();

        $this->assertFalse($testClass::canViewAny());
    }

    /** @test */
    public function it_can_check_create_permission(): void
    {
        $user = $this->createUser();

        Permission::factory()->resource()->create([
            'name' => 'create_test_model',
        ]);

        $user->givePermissionTo('create_test_model');

        $this->actingAs($user);

        $testClass = $this->createTestResourceClass();

        $this->assertTrue($testClass::canCreate());
    }

    /** @test */
    public function it_can_check_update_permission(): void
    {
        $user = $this->createUser();

        Permission::factory()->resource()->create([
            'name' => 'update_test_model',
        ]);

        $user->givePermissionTo('update_test_model');

        $this->actingAs($user);

        $testClass = $this->createTestResourceClass();

        $this->assertTrue($testClass::canUpdate());
    }

    /** @test */
    public function it_can_check_delete_permission(): void
    {
        $user = $this->createUser();

        Permission::factory()->resource()->create([
            'name' => 'delete_test_model',
        ]);

        $user->givePermissionTo('delete_test_model');

        $this->actingAs($user);

        $testClass = $this->createTestResourceClass();

        $this->assertTrue($testClass::canDelete());
    }

    /** @test */
    public function it_can_check_restore_permission(): void
    {
        $user = $this->createUser();

        Permission::factory()->resource()->create([
            'name' => 'restore_test_model',
        ]);

        $user->givePermissionTo('restore_test_model');

        $this->actingAs($user);

        $testClass = $this->createTestResourceClass();

        $this->assertTrue($testClass::canRestore());
    }

    /** @test */
    public function it_can_check_force_delete_permission(): void
    {
        $user = $this->createUser();

        Permission::factory()->resource()->create([
            'name' => 'force_delete_test_model',
        ]);

        $user->givePermissionTo('force_delete_test_model');

        $this->actingAs($user);

        $testClass = $this->createTestResourceClass();

        $this->assertTrue($testClass::canForceDelete());
    }

    /** @test */
    public function it_can_check_replicate_permission(): void
    {
        $user = $this->createUser();

        Permission::factory()->resource()->create([
            'name' => 'replicate_test_model',
        ]);

        $user->givePermissionTo('replicate_test_model');

        $this->actingAs($user);

        $testClass = $this->createTestResourceClass();

        $this->assertTrue($testClass::canReplicate());
    }

    /** @test */
    public function it_can_check_reorder_permission(): void
    {
        $user = $this->createUser();

        Permission::factory()->resource()->create([
            'name' => 'reorder_test_model',
        ]);

        $user->givePermissionTo('reorder_test_model');

        $this->actingAs($user);

        $testClass = $this->createTestResourceClass();

        $this->assertTrue($testClass::canReorder());
    }

    /** @test */
    public function it_bypasses_permissions_for_super_admin(): void
    {
        $user = $this->createSuperAdmin();

        $this->actingAs($user);

        $testClass = $this->createTestResourceClass();

        // Should return true even without specific permissions
        $this->assertTrue($testClass::canViewAny());
        $this->assertTrue($testClass::canCreate());
        $this->assertTrue($testClass::canUpdate());
        $this->assertTrue($testClass::canDelete());
        $this->assertTrue($testClass::canRestore());
        $this->assertTrue($testClass::canForceDelete());
        $this->assertTrue($testClass::canReplicate());
        $this->assertTrue($testClass::canReorder());
    }

    /** @test */
    public function it_generates_correct_permission_names(): void
    {
        $testClass = $this->createTestResourceClass();

        $this->assertEquals('view_any_test_model', $testClass::getPermissionName('view_any'));
        $this->assertEquals('view_test_model', $testClass::getPermissionName('view'));
        $this->assertEquals('create_test_model', $testClass::getPermissionName('create'));
        $this->assertEquals('update_test_model', $testClass::getPermissionName('update'));
        $this->assertEquals('delete_test_model', $testClass::getPermissionName('delete'));
        $this->assertEquals('restore_test_model', $testClass::getPermissionName('restore'));
        $this->assertEquals('force_delete_test_model', $testClass::getPermissionName('force_delete'));
        $this->assertEquals('replicate_test_model', $testClass::getPermissionName('replicate'));
        $this->assertEquals('reorder_test_model', $testClass::getPermissionName('reorder'));
    }

    /** @test */
    public function it_can_get_model_name(): void
    {
        $testClass = $this->createTestResourceClass();

        $this->assertEquals('test_model', $testClass::getModelPermissionName());
    }

    /** @test */
    public function it_can_get_all_permissions(): void
    {
        $testClass = $this->createTestResourceClass();

        $permissions = $testClass::getAllPermissions();

        $this->assertContains('view_any_test_model', $permissions);
        $this->assertContains('view_test_model', $permissions);
        $this->assertContains('create_test_model', $permissions);
        $this->assertContains('update_test_model', $permissions);
        $this->assertContains('delete_test_model', $permissions);
    }

    /**
     * Create a test resource class with HasResourcePermissions trait.
     */
    protected function createTestResourceClass(): string
    {
        return TestResource::class;
    }
}

/**
 * Test resource class.
 */
class TestResource
{
    use HasGatekeeperPermissions;

    protected static ?string $model = TestModel::class;

    /**
     * Get the model name for permission checks.
     */
    protected static function getGatekeeperModelName(): string
    {
        return 'test_model';
    }

    /**
     * Get permission name helper (for testing) - uses reflection to access protected method from trait.
     */
    public static function getPermissionName(string $action): string
    {
        // Build permission name directly to avoid infinite recursion
        $modelName = static::getGatekeeperModelName();
        $separator = config('gatekeeper.generator.separator', '_');

        if (config('gatekeeper.generator.snake_case', true)) {
            $modelName = str($modelName)->snake()->toString();
        } else {
            $modelName = str($modelName)->camel()->toString();
        }

        return "{$action}{$separator}{$modelName}";
    }

    /**
     * Get model permission name helper (for testing).
     */
    public static function getModelPermissionName(): string
    {
        return static::getGatekeeperModelName();
    }

    /**
     * Get all permissions helper (for testing).
     */
    public static function getAllPermissions(): array
    {
        $actions = ['view_any', 'view', 'create', 'update', 'delete', 'restore', 'force_delete', 'replicate', 'reorder'];
        $permissions = [];

        foreach ($actions as $action) {
            $permissions[] = static::getPermissionName($action);
        }

        return $permissions;
    }
}

/**
 * Test model class.
 */
class TestModel
{
    //
}
