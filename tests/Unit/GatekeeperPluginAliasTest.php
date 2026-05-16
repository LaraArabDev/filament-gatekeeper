<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Unit;

use Filament\Panel;
use LaraArabDev\FilamentGatekeeper\GatekeeperPlugin;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class GatekeeperPluginAliasTest extends TestCase
{
    #[Test]
    public function it_returns_plugin_id(): void
    {
        $plugin = GatekeeperPlugin::make();
        $this->assertSame('gatekeeper', $plugin->getId());
    }

    #[Test]
    public function should_bypass_for_super_admin_alias_delegates_to_is_bypass_enabled(): void
    {
        $pluginEnabled = GatekeeperPlugin::make()->bypassForSuperAdmin(true);
        $this->assertTrue($pluginEnabled->shouldBypassForSuperAdmin());

        $pluginDisabled = GatekeeperPlugin::make()->bypassForSuperAdmin(false);
        $this->assertFalse($pluginDisabled->shouldBypassForSuperAdmin());
    }

    #[Test]
    public function has_field_permissions_delegates_to_is_field_permissions_enabled(): void
    {
        $this->assertTrue(GatekeeperPlugin::make()->enableFieldPermissions(true)->hasFieldPermissions());
        $this->assertFalse(GatekeeperPlugin::make()->enableFieldPermissions(false)->hasFieldPermissions());
    }

    #[Test]
    public function has_column_permissions_delegates_to_is_column_permissions_enabled(): void
    {
        $this->assertTrue(GatekeeperPlugin::make()->enableColumnPermissions(true)->hasColumnPermissions());
        $this->assertFalse(GatekeeperPlugin::make()->enableColumnPermissions(false)->hasColumnPermissions());
    }

    #[Test]
    public function has_action_permissions_delegates_to_is_action_permissions_enabled(): void
    {
        $this->assertTrue(GatekeeperPlugin::make()->enableActionPermissions(true)->hasActionPermissions());
        $this->assertFalse(GatekeeperPlugin::make()->enableActionPermissions(false)->hasActionPermissions());
    }

    #[Test]
    public function has_relation_permissions_delegates_to_is_relation_permissions_enabled(): void
    {
        $this->assertTrue(GatekeeperPlugin::make()->enableRelationPermissions(true)->hasRelationPermissions());
        $this->assertFalse(GatekeeperPlugin::make()->enableRelationPermissions(false)->hasRelationPermissions());
    }

    #[Test]
    public function has_role_resource_delegates_to_is_role_resource_enabled(): void
    {
        $this->assertTrue(GatekeeperPlugin::make()->roleResource(true)->hasRoleResource());
        $this->assertFalse(GatekeeperPlugin::make()->roleResource(false)->hasRoleResource());
    }

    #[Test]
    public function has_permission_resource_delegates_to_is_permission_resource_enabled(): void
    {
        $this->assertTrue(GatekeeperPlugin::make()->permissionResource(true)->hasPermissionResource());
        $this->assertFalse(GatekeeperPlugin::make()->permissionResource(false)->hasPermissionResource());
    }

    #[Test]
    public function boot_method_does_not_throw(): void
    {
        $plugin = GatekeeperPlugin::make();
        $panel = new Panel;
        $plugin->boot($panel);
        $this->assertTrue(true);
    }
}
