<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Feature\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LaraArabDev\FilamentGatekeeper\Models\Permission;
use LaraArabDev\FilamentGatekeeper\Models\Role;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;

class SyncPermissionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_syncs_permissions_successfully(): void
    {
        $this->artisan('gatekeeper:sync')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_creates_super_admin_role(): void
    {
        config()->set('gatekeeper.super_admin.enabled', true);
        config()->set('gatekeeper.super_admin.role', 'super-admin');

        $this->artisan('gatekeeper:sync')
            ->assertExitCode(0);

        $this->assertDatabaseHas('roles', [
            'name' => 'super-admin',
            'guard_name' => 'web',
        ]);
    }

    /** @test */
    public function it_can_run_dry_run(): void
    {
        $initialCount = Permission::count();

        $this->artisan('gatekeeper:sync', ['--dry-run' => true])
            ->assertExitCode(0);

        // No new permissions should be created in dry run
        $this->assertEquals($initialCount, Permission::count());
    }

    /** @test */
    public function it_can_sync_only_resources(): void
    {
        $this->artisan('gatekeeper:sync', ['--only' => 'resources'])
            ->assertExitCode(0);
    }

    /** @test */
    public function it_can_sync_only_pages(): void
    {
        $this->artisan('gatekeeper:sync', ['--only' => 'pages'])
            ->assertExitCode(0);
    }

    /** @test */
    public function it_can_sync_only_widgets(): void
    {
        $this->artisan('gatekeeper:sync', ['--only' => 'widgets'])
            ->assertExitCode(0);
    }

    /** @test */
    public function it_can_sync_only_models(): void
    {
        config()->set('gatekeeper.discovery.discover_models', true);

        $this->artisan('gatekeeper:sync', ['--only' => 'models'])
            ->assertExitCode(0);
    }

    /** @test */
    public function it_can_sync_only_fields(): void
    {
        config()->set('gatekeeper.field_permissions', [
            'User' => ['email', 'phone'],
        ]);

        $this->artisan('gatekeeper:sync', ['--only' => 'fields'])
            ->assertExitCode(0);

        $this->assertDatabaseHas('permissions', ['name' => 'view_user_email_field']);
    }

    /** @test */
    public function it_can_sync_only_columns(): void
    {
        config()->set('gatekeeper.column_permissions', [
            'User' => ['email', 'phone'],
        ]);

        $this->artisan('gatekeeper:sync', ['--only' => 'columns'])
            ->assertExitCode(0);

        $this->assertDatabaseHas('permissions', ['name' => 'view_user_email_column']);
    }

    /** @test */
    public function it_can_sync_only_actions(): void
    {
        config()->set('gatekeeper.custom_actions', [
            'User' => ['export', 'import'],
        ]);

        $this->artisan('gatekeeper:sync', ['--only' => 'actions'])
            ->assertExitCode(0);

        $this->assertDatabaseHas('permissions', ['name' => 'execute_user_export_action']);
    }

    /** @test */
    public function it_can_sync_only_relations(): void
    {
        config()->set('gatekeeper.relation_permissions', [
            'User' => ['posts', 'roles'],
        ]);

        $this->artisan('gatekeeper:sync', ['--only' => 'relations'])
            ->assertExitCode(0);

        $this->assertDatabaseHas('permissions', ['name' => 'view_user_posts_relation']);
    }

    /** @test */
    public function it_assigns_all_permissions_to_super_admin(): void
    {
        config()->set('gatekeeper.super_admin.enabled', true);
        config()->set('gatekeeper.super_admin.role', 'super-admin');
        config()->set('gatekeeper.guards', ['web']);
        config()->set('gatekeeper.field_permissions', [
            'User' => ['email'],
        ]);

        $this->artisan('gatekeeper:sync')
            ->assertExitCode(0);

        $superAdmin = Role::where('name', 'super-admin')->where('guard_name', 'web')->first();
        $totalPermissions = Permission::where('guard_name', 'web')->count();

        $this->assertNotNull($superAdmin);
        $this->assertEquals($totalPermissions, $superAdmin->permissions()->count());
    }

    /** @test */
    public function it_can_force_resync(): void
    {
        // Create an existing permission
        Permission::factory()->resource()->create([
            'name' => 'old_permission',
            'guard_name' => 'web',
        ]);

        // Sync should update existing permissions
        $this->artisan('gatekeeper:sync')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_shows_output_information(): void
    {
        config()->set('gatekeeper.super_admin.enabled', true);

        $this->artisan('gatekeeper:sync')
            ->expectsOutput('Syncing all permissions...')
            ->assertExitCode(0);
    }
}
