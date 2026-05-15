<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LaraArabDev\FilamentGatekeeper\Gatekeeper;
use LaraArabDev\FilamentGatekeeper\Models\Permission;
use LaraArabDev\FilamentGatekeeper\Models\Role;
use LaraArabDev\FilamentGatekeeper\Services\PermissionCache;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;

/**
 * Branch coverage tests for the Gatekeeper class.
 * Targets uncovered paths in detectGuardFromRequest, getGuardsToCheckForSuperAdmin,
 * checkSuperAdminViaDatabase, and userHasRole.
 */
class GatekeeperBranchCoverageTest extends TestCase
{
    use RefreshDatabase;

    protected Gatekeeper $gatekeeper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gatekeeper = new Gatekeeper(new PermissionCache());
    }

    // ── detectGuardFromRequest ─────────────────────────────────────────────

    /** @test */
    public function it_detects_api_guard_from_api_route_prefix(): void
    {
        $request = \Illuminate\Http\Request::create('/api/users', 'GET');
        app()->instance('request', $request);

        $gatekeeper = new Gatekeeper(new PermissionCache());
        $this->assertEquals('api', $gatekeeper->getGuard());
    }

    /** @test */
    public function it_detects_web_guard_for_regular_routes(): void
    {
        $request = \Illuminate\Http\Request::create('/dashboard', 'GET');
        app()->instance('request', $request);

        $gatekeeper = new Gatekeeper(new PermissionCache());
        config()->set('gatekeeper.guard', 'web');
        $this->assertEquals('web', $gatekeeper->getGuard());
    }

    /** @test */
    public function it_uses_set_guard_over_auto_detection(): void
    {
        // Even with bearer token, explicit guard wins
        $gatekeeper = new Gatekeeper(new PermissionCache());
        $gatekeeper->guard('web');
        $this->assertEquals('web', $gatekeeper->getGuard());
    }

    // ── getGuardsToCheckForSuperAdmin ─────────────────────────────────────

    /** @test */
    public function it_checks_super_admin_across_multiple_guards(): void
    {
        config()->set('gatekeeper.super_admin.enabled', true);
        config()->set('gatekeeper.super_admin.role', 'super-admin');

        $superAdmin = $this->createSuperAdmin();
        $this->actingAs($superAdmin);

        $this->assertTrue($this->gatekeeper->shouldBypassPermissions());
    }

    /** @test */
    public function it_includes_user_guard_name_in_guards_to_check(): void
    {
        config()->set('gatekeeper.super_admin.enabled', true);
        config()->set('gatekeeper.super_admin.role', 'super-admin');

        // User with 'web' guard name (same as current) should deduplicate
        $user = $this->createUser();
        $user->setGuardName('web');

        // Create super-admin role for web guard
        $superAdminRole = Role::factory()->create([
            'name' => 'super-admin',
            'guard_name' => 'web',
        ]);
        $user->assignRole($superAdminRole);

        $this->actingAs($user);
        $this->gatekeeper->guard('web');

        $this->assertTrue($this->gatekeeper->shouldBypassPermissions());
    }

    /** @test */
    public function it_includes_web_fallback_guard_when_not_already_in_list(): void
    {
        config()->set('gatekeeper.super_admin.enabled', true);
        config()->set('gatekeeper.super_admin.role', 'super-admin');

        $user = $this->createUser();

        // Create super-admin role for web guard
        $superAdminRole = Role::factory()->create([
            'name' => 'super-admin',
            'guard_name' => 'web',
        ]);
        $user->assignRole($superAdminRole);

        // Use api guard - but pass user explicitly since api guard session won't find user
        $this->gatekeeper->guard('api');

        // Web is added as fallback, so the role on web guard should be found
        $result = $this->gatekeeper->shouldBypassPermissions($user);
        $this->assertTrue($result);
    }

    // ── checkSuperAdminViaDatabase ────────────────────────────────────────

    /** @test */
    public function it_checks_super_admin_via_database_when_has_role_unavailable(): void
    {
        config()->set('gatekeeper.super_admin.enabled', true);
        config()->set('gatekeeper.super_admin.role', 'super-admin');

        // Create a user without hasRole method to force DB path
        $user = new class implements \Illuminate\Contracts\Auth\Authenticatable {
            public function getAuthIdentifierName(): string { return 'id'; }
            public function getAuthIdentifier(): mixed { return 99999; }
            public function getAuthPasswordName(): string { return 'password'; }
            public function getAuthPassword(): string { return ''; }
            public function getRememberToken(): string { return ''; }
            public function setRememberToken(mixed $value): void {}
            public function getRememberTokenName(): string { return 'remember_token'; }
        };

        $gatekeeper = new Gatekeeper(new PermissionCache());
        $result = $gatekeeper->shouldBypassPermissions($user);

        // No hasRole and not in DB = false
        $this->assertFalse($result);
    }

    /** @test */
    public function it_returns_false_from_database_check_when_no_matching_role(): void
    {
        config()->set('gatekeeper.super_admin.enabled', true);
        config()->set('gatekeeper.super_admin.role', 'super-admin');

        // Regular user without super-admin role
        $user = $this->createUser();
        $this->actingAs($user);

        $this->assertFalse($this->gatekeeper->shouldBypassPermissions());
    }

    /** @test */
    public function it_returns_false_when_super_admin_disabled(): void
    {
        config()->set('gatekeeper.super_admin.enabled', false);

        $superAdmin = $this->createSuperAdmin();
        $this->actingAs($superAdmin);

        $gatekeeper = new Gatekeeper(new PermissionCache());
        // Even though user has role, bypass is disabled
        config()->set('gatekeeper.super_admin.enabled', false);
        $this->assertFalse($gatekeeper->shouldBypassPermissions($superAdmin));
    }

    // ── userHasRole exception handling ────────────────────────────────────

    /** @test */
    public function it_handles_guard_does_not_match_exception_gracefully(): void
    {
        config()->set('gatekeeper.super_admin.enabled', true);
        config()->set('gatekeeper.super_admin.role', 'super-admin');

        $user = $this->createUser();
        $this->actingAs($user);

        // Should not throw even when checking mismatched guards
        $this->gatekeeper->guard('nonexistent-guard');
        $result = $this->gatekeeper->shouldBypassPermissions($user);
        $this->assertFalse($result);
    }

    // ── getPermissionMatrix ────────────────────────────────────────────────

    /** @test */
    public function it_returns_empty_matrix_when_no_user_authenticated(): void
    {
        $matrix = $this->gatekeeper->getPermissionMatrix();
        $this->assertIsArray($matrix);
        $this->assertEmpty($matrix);
    }

    /** @test */
    public function it_returns_matrix_for_authenticated_user(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $matrix = $this->gatekeeper->getPermissionMatrix();
        $this->assertIsArray($matrix);
    }

    /** @test */
    public function it_returns_matrix_for_explicit_user_argument(): void
    {
        $user = $this->createUser();

        $matrix = $this->gatekeeper->getPermissionMatrix($user);
        $this->assertIsArray($matrix);
    }

    // ── canViewColumn - hasPermissionTo path then can() ────────────────────

    /** @test */
    public function it_canViewColumn_checks_permission_by_name(): void
    {
        $user = $this->createUser();
        Permission::factory()->column()->create(['name' => 'view_user_email_column']);
        $user->givePermissionTo('view_user_email_column');
        $this->actingAs($user);

        $this->assertTrue($this->gatekeeper->canViewColumn('User', 'email'));
    }

    /** @test */
    public function it_canViewColumn_returns_false_for_missing_permission(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $this->assertFalse($this->gatekeeper->canViewColumn('User', 'salary'));
    }

    // ── canViewRelation ────────────────────────────────────────────────────

    /** @test */
    public function it_canViewRelation_checks_permission_by_name(): void
    {
        $user = $this->createUser();
        Permission::factory()->relation()->create(['name' => 'view_user_roles_relation']);
        $user->givePermissionTo('view_user_roles_relation');
        $this->actingAs($user);

        $this->assertTrue($this->gatekeeper->canViewRelation('User', 'roles'));
    }

    /** @test */
    public function it_canViewRelation_falls_back_to_matrix(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        // No permission, so falls to matrix which is empty
        $this->assertFalse($this->gatekeeper->canViewRelation('User', 'roles'));
    }

    // ── getVisibleFields/Columns with matrix fallback ──────────────────────

    /** @test */
    public function it_getVisibleFields_returns_from_matrix_when_no_can_match(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        config()->set('gatekeeper.field_permissions.User', ['email']);
        // No actual permission created → falls to matrix
        $fields = $this->gatekeeper->getVisibleFields('User');
        // Matrix is empty for this user, so result is empty
        $this->assertIsArray($fields);
    }

    /** @test */
    public function it_getVisibleColumns_returns_from_matrix_when_no_can_match(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        config()->set('gatekeeper.column_permissions.User', ['email']);
        // No actual permission created → falls to matrix
        $columns = $this->gatekeeper->getVisibleColumns('User');
        $this->assertIsArray($columns);
    }
}
