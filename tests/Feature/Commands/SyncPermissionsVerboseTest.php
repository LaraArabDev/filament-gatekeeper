<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Feature\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LaraArabDev\FilamentGatekeeper\Models\Permission;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests for SyncPermissionsCommand verbose output — previously uncovered.
 */
class SyncPermissionsVerboseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('gatekeeper.super_admin.enabled', false);
        config()->set('gatekeeper.discovery.resources', []);
        config()->set('gatekeeper.discovery.models', []);
        config()->set('gatekeeper.discovery.pages', []);
        config()->set('gatekeeper.discovery.widgets', []);
        config()->set('gatekeeper.field_discovery.enabled', false);
        config()->set('gatekeeper.column_discovery.enabled', false);
    }

    #[Test]
    public function it_shows_verbose_output_when_verbose_flag_is_used(): void
    {
        config()->set('gatekeeper.field_permissions', ['User' => ['email']]);
        config()->set('gatekeeper.permission_prefixes.field', ['view']);
        config()->set('gatekeeper.guards', ['web' => ['enabled' => true]]);

        $this->artisan('gatekeeper:sync', ['--verbose' => true])
            ->assertSuccessful();
    }

    #[Test]
    public function it_shows_dry_run_output_correctly(): void
    {
        config()->set('gatekeeper.field_permissions', ['User' => ['email', 'name']]);
        config()->set('gatekeeper.permission_prefixes.field', ['view', 'update']);
        config()->set('gatekeeper.guards', ['web' => ['enabled' => true]]);

        $this->artisan('gatekeeper:sync', ['--dry-run' => true])
            ->expectsOutputToContain('dry-run mode')
            ->assertSuccessful();

        // No permissions created in dry-run
        $this->assertEquals(0, Permission::count());
    }

    #[Test]
    public function it_displays_results_table_after_sync(): void
    {
        config()->set('gatekeeper.field_permissions', ['User' => ['email']]);
        config()->set('gatekeeper.permission_prefixes.field', ['view']);
        config()->set('gatekeeper.guards', ['web' => ['enabled' => true]]);

        $this->artisan('gatekeeper:sync')
            ->expectsOutputToContain('Permission Type')
            ->assertSuccessful();
    }

    #[Test]
    public function it_displays_only_message_when_syncing_specific_type(): void
    {
        config()->set('gatekeeper.guards', ['web' => ['enabled' => true]]);
        config()->set('gatekeeper.permission_prefixes.resource', ['view_any', 'create']);

        $this->artisan('gatekeeper:sync', ['--only' => 'relations'])
            ->expectsOutputToContain('relations')
            ->assertSuccessful();
    }
}
