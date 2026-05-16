<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Unit\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LaraArabDev\FilamentGatekeeper\Services\PermissionRegistrar;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class PermissionRegistrarSyncOnlyTest extends TestCase
{
    use RefreshDatabase;

    protected PermissionRegistrar $registrar;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registrar = app(PermissionRegistrar::class);

        // Disable super admin so syncSuperAdminRole doesn't run unexpectedly
        config()->set('gatekeeper.super_admin.enabled', false);
        // Disable auto-discovery to avoid filesystem scanning
        config()->set('gatekeeper.discovery.resources', []);
        config()->set('gatekeeper.discovery.models', []);
        config()->set('gatekeeper.discovery.pages', []);
        config()->set('gatekeeper.discovery.widgets', []);
        config()->set('gatekeeper.field_discovery.enabled', false);
        config()->set('gatekeeper.column_discovery.enabled', false);
    }

    #[Test]
    public function it_can_sync_only_resources(): void
    {
        $log = $this->registrar->syncOnly('resources');
        $this->assertIsArray($log);
    }

    #[Test]
    public function it_can_sync_only_models(): void
    {
        $log = $this->registrar->syncOnly('models');
        $this->assertIsArray($log);
    }

    #[Test]
    public function it_can_sync_only_pages(): void
    {
        $log = $this->registrar->syncOnly('pages');
        $this->assertIsArray($log);
    }

    #[Test]
    public function it_can_sync_only_widgets(): void
    {
        $log = $this->registrar->syncOnly('widgets');
        $this->assertIsArray($log);
    }

    #[Test]
    public function it_can_sync_only_fields(): void
    {
        config()->set('gatekeeper.field_permissions', []);
        $log = $this->registrar->syncOnly('fields');
        $this->assertIsArray($log);
    }

    #[Test]
    public function it_can_sync_only_columns(): void
    {
        config()->set('gatekeeper.column_permissions', []);
        $log = $this->registrar->syncOnly('columns');
        $this->assertIsArray($log);
    }

    #[Test]
    public function it_can_sync_only_actions(): void
    {
        config()->set('gatekeeper.action_permissions', []);
        $log = $this->registrar->syncOnly('actions');
        $this->assertIsArray($log);
    }

    #[Test]
    public function it_can_sync_only_relations(): void
    {
        config()->set('gatekeeper.relation_permissions', []);
        $log = $this->registrar->syncOnly('relations');
        $this->assertIsArray($log);
    }

    #[Test]
    public function it_returns_empty_log_for_unknown_sync_type(): void
    {
        $log = $this->registrar->syncOnly('unknown_type_that_does_not_exist');
        $this->assertIsArray($log);
        $this->assertSame([], $log);
    }

    #[Test]
    public function it_syncs_field_permissions_from_config_when_discovery_disabled(): void
    {
        config()->set('gatekeeper.field_permissions.User', ['email', 'name']);
        config()->set('gatekeeper.field_discovery.enabled', false);

        $this->registrar->syncOnly('fields');

        $this->assertDatabaseHas('permissions', ['name' => 'view_user_email_field']);
        $this->assertDatabaseHas('permissions', ['name' => 'view_user_name_field']);
    }

    #[Test]
    public function it_syncs_column_permissions_from_config_when_discovery_disabled(): void
    {
        config()->set('gatekeeper.column_permissions.User', ['email', 'created_at']);
        config()->set('gatekeeper.column_discovery.enabled', false);

        $this->registrar->syncOnly('columns');

        $this->assertDatabaseHas('permissions', ['name' => 'view_user_email_column']);
        $this->assertDatabaseHas('permissions', ['name' => 'view_user_created_at_column']);
    }

    #[Test]
    public function sync_log_is_reset_on_each_sync_call(): void
    {
        config()->set('gatekeeper.field_permissions.Post', ['title']);
        $this->registrar->syncOnly('fields');
        $firstLog = $this->registrar->getSyncLog();

        config()->set('gatekeeper.column_permissions.Post', ['title']);
        $this->registrar->syncOnly('columns');
        $secondLog = $this->registrar->getSyncLog();

        // Second log should only contain columns sync entries
        $this->assertArrayNotHasKey('field', $secondLog);
        $this->assertArrayHasKey('column', $secondLog);
    }
}
