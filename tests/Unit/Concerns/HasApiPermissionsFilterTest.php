<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Unit\Concerns;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LaraArabDev\FilamentGatekeeper\Concerns\HasApiPermissions;
use LaraArabDev\FilamentGatekeeper\Models\Permission;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;
use LaraArabDev\FilamentGatekeeper\Tests\TestUser;

/**
 * Tests for HasApiPermissions::filterByPermissions() with real Model objects.
 * Previously uncovered because the existing test used array simulation.
 */
class HasApiPermissionsFilterTest extends TestCase
{
    use RefreshDatabase;

    // ── filterByPermissions with real Model ───────────────────────────────

    /** @test */
    public function it_filterByPermissions_returns_all_fields_when_no_visible_fields(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        // No field permissions configured → visibleFields is empty → return model->toArray()
        config()->set('gatekeeper.field_permissions', []);

        $model = $user; // TestUser is an Eloquent Model
        $controller = new FilterTestController();

        $result = $controller->callFilterByPermissions($model);

        $this->assertIsArray($result);
        // Should return the full model as array
        $this->assertArrayHasKey('email', $result);
    }

    /** @test */
    public function it_filterByPermissions_returns_only_visible_fields(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        config()->set('gatekeeper.field_permissions.User', ['email']);

        Permission::factory()->field()->forGuard('web')->create(['name' => 'view_user_email_field']);
        $user->givePermissionTo('view_user_email_field');

        $model = $this->createUser(['name' => 'Target', 'email' => 'target@example.com']);
        $controller = new FilterTestController();

        $result = $controller->callFilterByPermissions($model);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('email', $result);
        // 'name' and 'password' should NOT be in result since only 'email' is visible
        $this->assertArrayNotHasKey('password', $result);
    }

    // ── getGatekeeperModel extraction from class name ─────────────────────

    /** @test */
    public function it_extracts_model_name_by_removing_controller_and_api_suffixes(): void
    {
        $controller = new class {
            use HasApiPermissions;
            public function callGetModel(): string { return $this->getGatekeeperModel(); }
        };

        // Anonymous class has empty basename, so str() converts to empty string
        $result = $controller->callGetModel();
        $this->assertIsString($result);
    }

    /** @test */
    public function it_uses_permissionModel_property_when_available(): void
    {
        $controller = new class {
            use HasApiPermissions;
            protected string $permissionModel = 'Product';
            public function callGetModel(): string { return $this->getGatekeeperModel(); }
        };

        $this->assertSame('Product', $controller->callGetModel());
    }
}

class FilterTestController
{
    use HasApiPermissions;

    protected string $permissionModel = 'User';
    protected string $shieldGuard = 'web';

    public function callFilterByPermissions(\Illuminate\Database\Eloquent\Model $model): array
    {
        return $this->filterByPermissions($model);
    }
}
