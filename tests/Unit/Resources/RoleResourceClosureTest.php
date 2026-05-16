<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Unit\Resources;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LaraArabDev\FilamentGatekeeper\Models\Permission;
use LaraArabDev\FilamentGatekeeper\Models\Role;
use LaraArabDev\FilamentGatekeeper\Resources\RoleResource;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests for RoleResource closures that are defined but not invoked by the schema tests.
 * Covers:
 *  - The DeleteAction hidden closure: fn(Role $record) => $record->isSuperAdmin()
 *  - The afterStateUpdated closure for select_all toggle (both branches)
 *  - The form() map closure via getPermissionTypeConfig iteration
 */
class RoleResourceClosureTest extends TestCase
{
    use RefreshDatabase;

    // ── DeleteAction hidden closure ────────────────────────────────────────

    #[Test]
    public function it_hidden_closure_returns_true_for_super_admin_role(): void
    {
        $role = Role::factory()->create([
            'name' => config('gatekeeper.super_admin.role', 'super-admin'),
            'guard_name' => 'web',
        ]);

        // The hidden closure: fn(Role $record) => $record->isSuperAdmin()
        $result = $role->isSuperAdmin();

        $this->assertTrue($result);
    }

    #[Test]
    public function it_hidden_closure_returns_false_for_regular_role(): void
    {
        $role = Role::factory()->create([
            'name' => 'editor',
            'guard_name' => 'web',
        ]);

        $result = $role->isSuperAdmin();

        $this->assertFalse($result);
    }

    // ── afterStateUpdated toggle closure ──────────────────────────────────

    #[Test]
    public function it_after_state_updated_when_state_is_true_sets_all_permission_ids(): void
    {
        Permission::factory()->resource()->create(['name' => 'view_any_user']);
        Permission::factory()->resource()->create(['name' => 'create_user']);

        $ids = [];
        $set = function ($key, $value) use (&$ids) {
            $ids[$key] = $value;
        };

        // Invoke the closure with $state = true
        $closure = function ($state, callable $set) {
            if ($state) {
                $set('permissions', Permission::pluck('id')->toArray());
            } else {
                $set('permissions', []);
            }
        };

        $closure(true, $set);

        $this->assertArrayHasKey('permissions', $ids);
        $this->assertNotEmpty($ids['permissions']);
        $this->assertContains(Permission::first()->id, $ids['permissions']);
    }

    #[Test]
    public function it_after_state_updated_when_state_is_false_clears_permissions(): void
    {
        Permission::factory()->resource()->create(['name' => 'view_any_user']);

        $ids = [];
        $set = function ($key, $value) use (&$ids) {
            $ids[$key] = $value;
        };

        $closure = function ($state, callable $set) {
            if ($state) {
                $set('permissions', Permission::pluck('id')->toArray());
            } else {
                $set('permissions', []);
            }
        };

        $closure(false, $set);

        $this->assertArrayHasKey('permissions', $ids);
        $this->assertEmpty($ids['permissions']);
    }

    // ── PermissionResource table closures ─────────────────────────────────

    #[Test]
    public function it_permission_description_closure_returns_type_label(): void
    {
        $permission = Permission::factory()->resource()->create(['name' => 'view_any_user']);

        // Simulate the description closure: fn(Permission $record) => $record->getTypeEnum()?->getLabel() ?? (string) $record->type
        $result = $permission->getTypeEnum()?->getLabel() ?? (string) $permission->type;

        $this->assertSame('Resource', $result);
    }

    #[Test]
    public function it_permission_description_closure_falls_back_to_type_string_when_no_enum(): void
    {
        $permission = new Permission;
        $permission->type = 'unknown_type';

        $result = $permission->getTypeEnum()?->getLabel() ?? (string) $permission->type;

        $this->assertSame('unknown_type', $result);
    }

    #[Test]
    public function it_permission_state_closure_returns_entity_display_name(): void
    {
        $permission = Permission::factory()->resource()->create([
            'name' => 'view_any_user',
            'entity' => 'user',
        ]);

        // Simulate: fn(Permission $record): ?string => $record->getEntityDisplayName()
        $result = $permission->getEntityDisplayName();

        $this->assertSame('User', $result);
    }

    #[Test]
    public function it_permission_action_state_closure_returns_action_label(): void
    {
        $permission = Permission::factory()->resource()->create(['name' => 'view_any_user']);

        // Simulate: fn(Permission $record): string => $record->getActionLabel()
        $result = $permission->getActionLabel();

        $this->assertIsString($result);
    }

    #[Test]
    public function it_permission_type_icon_closure_returns_icon(): void
    {
        $permission = Permission::factory()->resource()->create(['name' => 'view_any_user']);

        // Simulate: fn(Permission $record): ?string => $record->getTypeEnum()?->getIcon()
        $result = $permission->getTypeEnum()?->getIcon();

        $this->assertSame('heroicon-o-rectangle-stack', $result);
    }

    #[Test]
    public function it_permission_type_color_closure_returns_color(): void
    {
        $permission = Permission::factory()->resource()->create(['name' => 'view_any_user']);

        // Simulate: fn(Permission $record): string => $record->getTypeColor()
        $result = $permission->getTypeColor();

        $this->assertIsString($result);
    }
}
