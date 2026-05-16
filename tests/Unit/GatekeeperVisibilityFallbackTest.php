<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use LaraArabDev\FilamentGatekeeper\Gatekeeper;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;

/**
 * Tests for Gatekeeper visibility methods using the can() fallback path.
 *
 * These tests cover the branch where hasPermissionTo() throws PermissionDoesNotExist
 * and the code falls back to using $user->can() to check permission.
 */
class GatekeeperVisibilityFallbackTest extends TestCase
{
    use RefreshDatabase;

    protected Gatekeeper $gatekeeper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gatekeeper = app(Gatekeeper::class);
    }

    // ── getVisibleColumns with can() fallback ──────────────────────────────

    /** @test */
    public function it_returns_column_via_can_fallback_when_permission_not_in_db(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        config()->set('gatekeeper.column_permissions.User', ['email']);

        // Define a Gate that returns true for this permission
        // (permission does NOT exist in DB, so hasPermissionTo will throw PermissionDoesNotExist)
        Gate::define('view_user_email_column', fn () => true);

        $columns = $this->gatekeeper->getVisibleColumns('User');

        $this->assertContains('email', $columns);
    }

    /** @test */
    public function it_excludes_column_when_both_has_permission_to_fails_and_can_returns_false(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        config()->set('gatekeeper.column_permissions.User', ['salary']);

        // No Gate defined and no DB permission - both paths return false
        $columns = $this->gatekeeper->getVisibleColumns('User');

        $this->assertNotContains('salary', $columns);
    }

    // ── getVisibleFields with can() path ───────────────────────────────────

    /** @test */
    public function it_returns_field_via_can_when_gate_allows(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        config()->set('gatekeeper.field_permissions.User', ['name']);

        // Define Gate directly
        Gate::define('view_user_name_field', fn () => true);

        $fields = $this->gatekeeper->getVisibleFields('User');

        $this->assertContains('name', $fields);
    }

    /** @test */
    public function it_uses_matrix_fallback_when_can_returns_false_for_field(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        config()->set('gatekeeper.field_permissions.User', ['salary']);
        // No Gate defined, no DB permission - falls to matrix (empty)

        $fields = $this->gatekeeper->getVisibleFields('User');

        // Matrix is empty for this user, so result is empty
        $this->assertIsArray($fields);
        $this->assertNotContains('salary', $fields);
    }

    // ── canViewField matrix fallback ──────────────────────────────────────

    /** @test */
    public function it_falls_back_to_matrix_when_can_returns_false_for_can_view_field(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        // No permission in DB, no Gate defined - can() returns false, matrix is empty
        $result = $this->gatekeeper->canViewField('User', 'salary');

        $this->assertFalse($result);
    }

    /** @test */
    public function it_returns_true_for_can_view_field_when_gate_allows(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        // Define Gate to return true
        Gate::define('view_user_name_field', fn () => true);

        $result = $this->gatekeeper->canViewField('User', 'name');

        $this->assertTrue($result);
    }

    /** @test */
    public function it_returns_true_for_can_update_field_when_gate_allows(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        // Get prefix for update
        config()->set('gatekeeper.permission_prefixes.field', ['view', 'update']);
        Gate::define('view_user_email_field', fn () => true);

        $result = $this->gatekeeper->canViewField('User', 'email');

        $this->assertTrue($result);
    }

    // ── canUpdateField via Gate ───────────────────────────────────────────

    /** @test */
    public function it_returns_true_for_can_update_field_via_gate(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        config()->set('gatekeeper.permission_prefixes.field', ['view', 'update']);
        // 'update' is index 1
        Gate::define('update_user_salary_field', fn () => true);

        $result = $this->gatekeeper->canUpdateField('User', 'salary');

        $this->assertTrue($result);
    }

    /** @test */
    public function it_falls_back_to_matrix_for_can_update_field_when_can_returns_false(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        // No Gate, no DB permission - matrix is empty
        $result = $this->gatekeeper->canUpdateField('User', 'salary');

        $this->assertFalse($result);
    }

    // ── canViewRelation via Gate ──────────────────────────────────────────

    /** @test */
    public function it_returns_true_for_can_view_relation_via_gate_fallback(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        // Permission not in DB → hasPermissionTo throws → can() fallback
        Gate::define('view_user_posts_relation', fn () => true);

        $result = $this->gatekeeper->canViewRelation('User', 'posts');

        $this->assertTrue($result);
    }

    /** @test */
    public function it_falls_back_to_matrix_for_can_view_relation(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        // No Gate, no DB permission - matrix is empty
        $result = $this->gatekeeper->canViewRelation('User', 'posts');

        $this->assertFalse($result);
    }

    // ── canExecuteAction via Gate ─────────────────────────────────────────

    /** @test */
    public function it_returns_true_for_can_execute_action_via_gate(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        Gate::define('execute_user_export_action', fn () => true);

        $result = $this->gatekeeper->canExecuteAction('User', 'export');

        $this->assertTrue($result);
    }

    /** @test */
    public function it_falls_back_to_matrix_for_can_execute_action(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        // No Gate, no DB permission - matrix empty
        $result = $this->gatekeeper->canExecuteAction('User', 'export');

        $this->assertFalse($result);
    }

    // ── isSuperAdmin ──────────────────────────────────────────────────────

    /** @test */
    public function it_is_super_admin_returns_false_for_regular_user(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $this->assertFalse($this->gatekeeper->isSuperAdmin());
    }

    /** @test */
    public function it_is_super_admin_returns_true_for_super_admin_user(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $this->actingAs($superAdmin);

        $this->assertTrue($this->gatekeeper->isSuperAdmin());
    }
}
