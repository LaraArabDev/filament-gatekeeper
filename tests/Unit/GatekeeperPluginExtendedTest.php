<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Unit;

use LaraArabDev\FilamentGatekeeper\GatekeeperPlugin;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class GatekeeperPluginExtendedTest extends TestCase
{
    #[Test]
    public function it_can_set_and_get_navigation_icon(): void
    {
        $plugin = GatekeeperPlugin::make()->navigationIcon('heroicon-o-lock-closed');
        $this->assertSame('heroicon-o-lock-closed', $plugin->getNavigationIcon());
    }

    #[Test]
    public function it_has_default_navigation_icon(): void
    {
        $plugin = GatekeeperPlugin::make();
        $this->assertSame('heroicon-o-shield-check', $plugin->getNavigationIcon());
    }

    #[Test]
    public function it_can_set_and_get_panels(): void
    {
        $plugin = GatekeeperPlugin::make()->panels(['admin', 'tenant']);
        $this->assertSame(['admin', 'tenant'], $plugin->getPanels());
    }

    #[Test]
    public function it_has_empty_panels_by_default(): void
    {
        $plugin = GatekeeperPlugin::make();
        $this->assertSame([], $plugin->getPanels());
    }

    #[Test]
    public function it_can_set_and_get_guards(): void
    {
        $plugin = GatekeeperPlugin::make()->guards(['web', 'api']);
        $this->assertSame(['web', 'api'], $plugin->getGuards());
    }

    #[Test]
    public function it_has_web_guard_by_default(): void
    {
        $plugin = GatekeeperPlugin::make();
        $this->assertSame(['web'], $plugin->getGuards());
    }

    #[Test]
    public function it_can_set_modify_role_resource_callback(): void
    {
        $callback = fn () => null;
        $plugin = GatekeeperPlugin::make()->modifyRoleResourceUsing($callback);
        $this->assertSame($callback, $plugin->getModifyRoleResourceUsing());
    }

    #[Test]
    public function it_has_null_modify_role_resource_callback_by_default(): void
    {
        $plugin = GatekeeperPlugin::make();
        $this->assertNull($plugin->getModifyRoleResourceUsing());
    }

    #[Test]
    public function it_returns_is_bypass_for_super_admin_enabled(): void
    {
        $plugin = GatekeeperPlugin::make()->bypassForSuperAdmin(true);
        $this->assertTrue($plugin->isBypassForSuperAdminEnabled());

        $plugin2 = GatekeeperPlugin::make()->bypassForSuperAdmin(false);
        $this->assertFalse($plugin2->isBypassForSuperAdminEnabled());
    }

    #[Test]
    public function it_returns_is_role_resource_enabled(): void
    {
        $this->assertTrue(GatekeeperPlugin::make()->isRoleResourceEnabled());
        $this->assertFalse(GatekeeperPlugin::make()->roleResource(false)->isRoleResourceEnabled());
    }

    #[Test]
    public function it_returns_is_permission_resource_enabled(): void
    {
        $this->assertTrue(GatekeeperPlugin::make()->isPermissionResourceEnabled());
        $this->assertFalse(GatekeeperPlugin::make()->permissionResource(false)->isPermissionResourceEnabled());
    }

    #[Test]
    public function it_returns_is_field_permissions_enabled(): void
    {
        $this->assertTrue(GatekeeperPlugin::make()->isFieldPermissionsEnabled());
        $this->assertFalse(GatekeeperPlugin::make()->enableFieldPermissions(false)->isFieldPermissionsEnabled());
    }

    #[Test]
    public function it_returns_is_column_permissions_enabled(): void
    {
        $this->assertTrue(GatekeeperPlugin::make()->isColumnPermissionsEnabled());
        $this->assertFalse(GatekeeperPlugin::make()->enableColumnPermissions(false)->isColumnPermissionsEnabled());
    }

    #[Test]
    public function it_returns_is_action_permissions_enabled(): void
    {
        $this->assertTrue(GatekeeperPlugin::make()->isActionPermissionsEnabled());
        $this->assertFalse(GatekeeperPlugin::make()->enableActionPermissions(false)->isActionPermissionsEnabled());
    }

    #[Test]
    public function it_returns_is_relation_permissions_enabled(): void
    {
        $this->assertTrue(GatekeeperPlugin::make()->isRelationPermissionsEnabled());
        $this->assertFalse(GatekeeperPlugin::make()->enableRelationPermissions(false)->isRelationPermissionsEnabled());
    }

    #[Test]
    public function it_can_set_modify_permission_resource_callback(): void
    {
        $callback = fn () => null;
        $plugin = GatekeeperPlugin::make()->modifyPermissionResourceUsing($callback);
        $this->assertSame($callback, $plugin->getModifyPermissionResourceUsing());
    }

    #[Test]
    public function it_has_null_modify_permission_resource_callback_by_default(): void
    {
        $plugin = GatekeeperPlugin::make();
        $this->assertNull($plugin->getModifyPermissionResourceUsing());
    }

    #[Test]
    public function it_can_set_navigation_group(): void
    {
        $plugin = GatekeeperPlugin::make()->navigationGroup('Security');
        $this->assertSame('Security', $plugin->getNavigationGroup());
    }

    #[Test]
    public function it_can_set_navigation_sort(): void
    {
        $plugin = GatekeeperPlugin::make()->navigationSort(10);
        $this->assertSame(10, $plugin->getNavigationSort());
    }

    #[Test]
    public function it_can_set_super_admin_role(): void
    {
        $plugin = GatekeeperPlugin::make()->superAdminRole('admin');
        $this->assertSame('admin', $plugin->getSuperAdminRole());
    }
}
