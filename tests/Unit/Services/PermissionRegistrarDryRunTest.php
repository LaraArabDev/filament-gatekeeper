<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Unit\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LaraArabDev\FilamentGatekeeper\Models\Permission;
use LaraArabDev\FilamentGatekeeper\Services\PermissionRegistrar;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class PermissionRegistrarDryRunTest extends TestCase
{
    use RefreshDatabase;

    protected PermissionRegistrar $registrar;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registrar = app(PermissionRegistrar::class);
    }

    #[Test]
    public function it_dry_run_does_not_create_permissions(): void
    {
        config()->set('gatekeeper.field_permissions', ['User' => ['email', 'phone']]);
        config()->set('gatekeeper.guards', ['web']);

        $this->registrar->dryRun(true);
        $this->registrar->syncFieldPermissions();

        $this->assertEquals(0, Permission::count());
    }

    #[Test]
    public function it_dry_run_logs_operations(): void
    {
        config()->set('gatekeeper.field_permissions', ['User' => ['email']]);
        config()->set('gatekeeper.guards', ['web']);

        $this->registrar->dryRun(true);
        $this->registrar->syncFieldPermissions();

        $log = $this->registrar->getSyncLog();
        $this->assertIsArray($log);
    }

    #[Test]
    public function it_dry_run_false_creates_permissions(): void
    {
        config()->set('gatekeeper.field_permissions', ['User' => ['email']]);
        config()->set('gatekeeper.guards', ['web']);

        $this->registrar->dryRun(false);
        $this->registrar->syncFieldPermissions();

        $this->assertGreaterThan(0, Permission::count());
    }

    #[Test]
    public function it_dry_run_does_not_create_column_permissions(): void
    {
        config()->set('gatekeeper.column_permissions', ['User' => ['email', 'salary']]);
        config()->set('gatekeeper.guards', ['web']);

        $this->registrar->dryRun(true);
        $this->registrar->syncColumnPermissions();

        $this->assertEquals(0, Permission::columns()->count());
    }

    #[Test]
    public function it_dry_run_does_not_create_resource_permissions(): void
    {
        config()->set('gatekeeper.guards', ['web']);

        $this->registrar->dryRun(true);
        $this->registrar->syncResourcePermissions();

        $this->assertEquals(0, Permission::resources()->count());
    }

    #[Test]
    public function it_dry_run_does_not_create_page_permissions(): void
    {
        config()->set('gatekeeper.guards', ['web']);

        $this->registrar->dryRun(true);
        $this->registrar->syncPagePermissions();

        $this->assertEquals(0, Permission::pages()->count());
    }

    #[Test]
    public function it_dry_run_does_not_create_widget_permissions(): void
    {
        config()->set('gatekeeper.guards', ['web']);

        $this->registrar->dryRun(true);
        $this->registrar->syncWidgetPermissions();

        $this->assertEquals(0, Permission::widgets()->count());
    }

    #[Test]
    public function it_dry_run_does_not_create_action_permissions(): void
    {
        config()->set('gatekeeper.custom_actions', ['User' => ['export', 'import']]);
        config()->set('gatekeeper.guards', ['web']);

        $this->registrar->dryRun(true);
        $this->registrar->syncActionPermissions();

        $this->assertEquals(0, Permission::actions()->count());
    }

    #[Test]
    public function it_dry_run_does_not_create_relation_permissions(): void
    {
        config()->set('gatekeeper.relation_permissions', ['User' => ['posts', 'roles']]);
        config()->set('gatekeeper.guards', ['web']);

        $this->registrar->dryRun(true);
        $this->registrar->syncRelationPermissions();

        $this->assertEquals(0, Permission::relations()->count());
    }

    #[Test]
    public function it_can_toggle_dry_run_off(): void
    {
        config()->set('gatekeeper.field_permissions', ['User' => ['email']]);
        config()->set('gatekeeper.guards', ['web']);

        $this->registrar->dryRun(true);
        $this->registrar->dryRun(false);

        $this->registrar->syncFieldPermissions();

        $this->assertGreaterThan(0, Permission::count());
    }

    #[Test]
    public function it_dry_run_logs_field_operations(): void
    {
        config()->set('gatekeeper.field_permissions', ['User' => ['email', 'phone']]);
        config()->set('gatekeeper.guards', ['web']);

        $this->registrar->dryRun(true);
        $this->registrar->syncFieldPermissions();

        $log = $this->registrar->getSyncLog();

        // The log should contain 'field' entries with "Would create" messages
        $this->assertArrayHasKey('field', $log);
        $this->assertGreaterThan(0, count($log['field']));
    }

    #[Test]
    public function it_dry_run_logs_action_operations(): void
    {
        config()->set('gatekeeper.custom_actions', ['User' => ['export']]);
        config()->set('gatekeeper.guards', ['web']);

        $this->registrar->dryRun(true);
        $this->registrar->syncActionPermissions();

        $log = $this->registrar->getSyncLog();

        $this->assertArrayHasKey('action', $log);
        $this->assertGreaterThan(0, count($log['action']));
    }

    #[Test]
    public function it_dry_run_logs_relation_operations(): void
    {
        config()->set('gatekeeper.relation_permissions', ['User' => ['posts']]);
        config()->set('gatekeeper.guards', ['web']);

        $this->registrar->dryRun(true);
        $this->registrar->syncRelationPermissions();

        $log = $this->registrar->getSyncLog();

        $this->assertArrayHasKey('relation', $log);
        $this->assertGreaterThan(0, count($log['relation']));
    }

    #[Test]
    public function it_dry_run_does_not_affect_database_on_sync_all(): void
    {
        config()->set('gatekeeper.field_permissions', ['User' => ['email']]);
        config()->set('gatekeeper.column_permissions', ['User' => ['email']]);
        config()->set('gatekeeper.custom_actions', ['User' => ['export']]);
        config()->set('gatekeeper.relation_permissions', ['User' => ['posts']]);
        config()->set('gatekeeper.super_admin.enabled', true);
        config()->set('gatekeeper.guards', ['web']);

        $this->registrar->dryRun(true);
        $this->registrar->syncAll();

        $this->assertEquals(0, Permission::count());
    }
}
