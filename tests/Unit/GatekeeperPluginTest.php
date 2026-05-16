<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LaraArabDev\FilamentGatekeeper\GatekeeperPlugin;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class GatekeeperPluginTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_be_instantiated(): void
    {
        $plugin = GatekeeperPlugin::make();

        $this->assertInstanceOf(GatekeeperPlugin::class, $plugin);
    }

    #[Test]
    public function it_has_correct_id(): void
    {
        $plugin = GatekeeperPlugin::make();

        $this->assertEquals('gatekeeper', $plugin->getId());
    }

    #[Test]
    public function it_can_set_super_admin_role(): void
    {
        $plugin = GatekeeperPlugin::make()
            ->superAdminRole('admin');

        $this->assertEquals('admin', $plugin->getSuperAdminRole());
    }

    #[Test]
    public function it_can_enable_bypass_for_super_admin(): void
    {
        $plugin = GatekeeperPlugin::make()
            ->bypassForSuperAdmin(true);

        $this->assertTrue($plugin->shouldBypassForSuperAdmin());
    }

    #[Test]
    public function it_can_disable_bypass_for_super_admin(): void
    {
        $plugin = GatekeeperPlugin::make()
            ->bypassForSuperAdmin(false);

        $this->assertFalse($plugin->shouldBypassForSuperAdmin());
    }

    #[Test]
    public function it_can_enable_field_permissions(): void
    {
        $plugin = GatekeeperPlugin::make()
            ->enableFieldPermissions();

        $this->assertTrue($plugin->hasFieldPermissions());
    }

    #[Test]
    public function it_can_enable_column_permissions(): void
    {
        $plugin = GatekeeperPlugin::make()
            ->enableColumnPermissions();

        $this->assertTrue($plugin->hasColumnPermissions());
    }

    #[Test]
    public function it_can_enable_action_permissions(): void
    {
        $plugin = GatekeeperPlugin::make()
            ->enableActionPermissions();

        $this->assertTrue($plugin->hasActionPermissions());
    }

    #[Test]
    public function it_can_enable_relation_permissions(): void
    {
        $plugin = GatekeeperPlugin::make()
            ->enableRelationPermissions();

        $this->assertTrue($plugin->hasRelationPermissions());
    }

    #[Test]
    public function it_can_set_navigation_group(): void
    {
        $plugin = GatekeeperPlugin::make()
            ->navigationGroup('Access Control');

        $this->assertEquals('Access Control', $plugin->getNavigationGroup());
    }

    #[Test]
    public function it_can_set_navigation_sort(): void
    {
        $plugin = GatekeeperPlugin::make()
            ->navigationSort(5);

        $this->assertEquals(5, $plugin->getNavigationSort());
    }

    #[Test]
    public function it_can_chain_configuration_methods(): void
    {
        $plugin = GatekeeperPlugin::make()
            ->superAdminRole('super-admin')
            ->bypassForSuperAdmin(true)
            ->enableFieldPermissions()
            ->enableColumnPermissions()
            ->enableActionPermissions()
            ->enableRelationPermissions()
            ->navigationGroup('Security')
            ->navigationSort(1);

        $this->assertEquals('super-admin', $plugin->getSuperAdminRole());
        $this->assertTrue($plugin->shouldBypassForSuperAdmin());
        $this->assertTrue($plugin->hasFieldPermissions());
        $this->assertTrue($plugin->hasColumnPermissions());
        $this->assertTrue($plugin->hasActionPermissions());
        $this->assertTrue($plugin->hasRelationPermissions());
        $this->assertEquals('Security', $plugin->getNavigationGroup());
        $this->assertEquals(1, $plugin->getNavigationSort());
    }

    #[Test]
    public function it_can_disable_role_resource(): void
    {
        $plugin = GatekeeperPlugin::make()
            ->roleResource(false);

        $this->assertFalse($plugin->hasRoleResource());
    }

    #[Test]
    public function it_can_disable_permission_resource(): void
    {
        $plugin = GatekeeperPlugin::make()
            ->permissionResource(false);

        $this->assertFalse($plugin->hasPermissionResource());
    }

    #[Test]
    public function it_uses_default_values(): void
    {
        $plugin = GatekeeperPlugin::make();

        // Check default values
        $this->assertEquals('super-admin', $plugin->getSuperAdminRole());
        $this->assertTrue($plugin->shouldBypassForSuperAdmin());
        $this->assertTrue($plugin->hasRoleResource());
        $this->assertTrue($plugin->hasPermissionResource());
    }
}
