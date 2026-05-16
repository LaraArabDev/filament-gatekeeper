<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Unit\Concerns;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Resources\MissingValue;
use Illuminate\Support\Facades\DB;
use LaraArabDev\FilamentGatekeeper\Concerns\HasResourcePermissions as HasApiResourcePermissions;
use LaraArabDev\FilamentGatekeeper\Models\Permission;
use LaraArabDev\FilamentGatekeeper\Models\Role;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class HasResourcePermissionsForApiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_check_column_visibility_in_api_resource(): void
    {
        $user = $this->createUser();
        $user->setGuardName('api');

        $permission = Permission::factory()->column()->forGuard('api')->create([
            'name' => 'view_user_email_column',
        ]);

        // Create role with api guard and assign permission
        $role = Role::factory()->forGuard('api')->create(['name' => 'test-role']);
        $role->givePermissionTo($permission);

        // Assign role using DB directly to avoid guard mismatch
        \DB::table(config('permission.table_names.model_has_roles', 'model_has_roles'))->insert([
            'role_id' => $role->id,
            'model_type' => get_class($user),
            'model_id' => $user->id,
        ]);

        $this->actingAs($user, 'api');

        $resource = new TestApiResourceClass(['email' => 'test@example.com']);

        $result = $resource->whenCanViewColumn('email', 'test@example.com');

        $this->assertEquals('test@example.com', $result);
    }

    #[Test]
    public function it_returns_null_when_no_column_permission(): void
    {
        $user = $this->createUser();

        $this->actingAs($user, 'api');

        $resource = new TestApiResourceClass(['email' => 'test@example.com']);

        $result = $resource->whenCanViewColumn('email', 'test@example.com');

        // Should return missing value or null when permission is denied
        $this->assertNotEquals('test@example.com', $result);
    }

    #[Test]
    public function it_can_check_relation_loading_in_api_resource(): void
    {
        $user = $this->createUser([], 'api');

        $permission = Permission::factory()->relation()->forGuard('api')->create([
            'name' => 'view_user_roles_relation',
        ]);

        // Create role with api guard and assign permission
        $role = Role::factory()->forGuard('api')->create(['name' => 'test-role']);
        $role->givePermissionTo($permission);

        // Use DB directly to bypass guard check
        DB::table(config('permission.table_names.model_has_roles', 'model_has_roles'))->insert([
            'role_id' => $role->id,
            'model_type' => get_class($user),
            'model_id' => $user->id,
        ]);

        $this->actingAs($user, 'api');

        $resource = new TestApiResourceClass([]);

        // whenCanLoadRelation expects a resource class name, not a callback
        // This test verifies the permission check works
        $this->assertTrue($resource->canViewRelation('roles'));
    }

    #[Test]
    public function it_does_not_load_relation_without_permission(): void
    {
        $user = $this->createUser();

        $this->actingAs($user, 'api');

        $resource = new TestApiResourceClass([]);

        // Should return false when permission is denied
        $this->assertFalse($resource->canViewRelation('roles'));
    }

    #[Test]
    public function super_admin_can_view_all_columns(): void
    {
        $user = $this->createSuperAdmin();

        $this->actingAs($user, 'api');

        $resource = new TestApiResourceClass(['salary' => 100000]);

        $result = $resource->whenCanViewColumn('salary', 100000);

        $this->assertEquals(100000, $result);
    }

    #[Test]
    public function super_admin_can_load_all_relations(): void
    {
        $user = $this->createSuperAdmin();

        $this->actingAs($user, 'api');

        $resource = new TestApiResourceClass([]);

        // Super admin should be able to view any relation
        $this->assertTrue($resource->canViewRelation('any_relation'));
    }

    #[Test]
    public function it_can_get_permitted_attributes(): void
    {
        $user = $this->createUser([], 'api');

        $permission1 = Permission::factory()->column()->forGuard('api')->create(['name' => 'view_user_name_column']);
        $permission2 = Permission::factory()->column()->forGuard('api')->create(['name' => 'view_user_email_column']);

        // Create role with api guard and assign permissions
        $role = Role::factory()->forGuard('api')->create(['name' => 'test-role']);
        $role->givePermissionTo([$permission1, $permission2]);

        // Use DB directly to bypass guard check
        DB::table(config('permission.table_names.model_has_roles', 'model_has_roles'))->insert([
            'role_id' => $role->id,
            'model_type' => get_class($user),
            'model_id' => $user->id,
        ]);

        $this->actingAs($user, 'api');

        config()->set('gatekeeper.column_permissions.user', ['name', 'email', 'salary']);

        $resource = new TestApiResourceClass([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'salary' => 100000,
        ]);

        // permittedAttributes filters by column permissions
        // The except parameter is for fields to exclude, not columns to include
        $permitted = $resource->permittedAttributes([]);

        // Should only include columns that user has permission to view
        // Since user has permission for 'name' and 'email', those should be included
        // 'salary' should not be included as user doesn't have permission
        $this->assertArrayHasKey('name', $permitted);
        $this->assertArrayHasKey('email', $permitted);
        $this->assertArrayNotHasKey('salary', $permitted);
    }
}

/**
 * Test API Resource class using the trait.
 */
class TestApiResourceClass
{
    use HasApiResourcePermissions;

    protected string $shieldModel = 'user';

    protected $resource;

    public function __construct(array $resource)
    {
        $this->resource = (object) $resource;
    }

    public function __get($key)
    {
        return $this->resource->{$key} ?? null;
    }

    /**
     * Make methods public for testing.
     */
    public function whenCanViewColumn(string $column, mixed $value, mixed $default = null)
    {
        // Call trait method directly
        return $this->when(
            $this->shield()->canViewColumn($this->getGatekeeperModel(), $column),
            $value,
            $default
        );
    }

    public function whenCanLoadRelation(string $relation, string $resourceClass)
    {
        // Call trait method directly
        if (! $this->shield()->canViewRelation($this->getGatekeeperModel(), $relation)) {
            return $this->when(false, null);
        }

        return $this->whenLoaded($relation, function () use ($relation, $resourceClass) {
            $relationData = $this->resource->{$relation};

            if (is_iterable($relationData)) {
                return $resourceClass::collection($relationData);
            }

            return new $resourceClass($relationData);
        });
    }

    public function canViewRelation(string $relation): bool
    {
        // Call trait method directly
        return $this->shield()->canViewRelation($this->getGatekeeperModel(), $relation);
    }

    /**
     * Helper method to access trait's when method.
     */
    protected function when($condition, $value, $default = null)
    {
        return $condition ? $value : ($default ?? new MissingValue);
    }

    /**
     * Helper method to access trait's whenLoaded method.
     */
    protected function whenLoaded($relation, $value)
    {
        // For testing, just return the value
        return is_callable($value) ? $value() : $value;
    }

    public function permittedAttributes(array $except = []): array
    {
        // Convert resource object to array for testing
        $resourceArray = (array) $this->resource;

        // Filter by column permissions (for API resources, we use column permissions)
        $visibleColumns = $this->shield()->getVisibleColumns($this->getGatekeeperModel());

        if (empty($visibleColumns)) {
            // If no column permissions configured, return all except excluded
            return array_diff_key($resourceArray, array_flip($except));
        }

        // Filter to only include visible columns
        $filtered = array_intersect_key($resourceArray, array_flip($visibleColumns));

        // Remove excepted fields
        return array_diff_key($filtered, array_flip($except));
    }
}
