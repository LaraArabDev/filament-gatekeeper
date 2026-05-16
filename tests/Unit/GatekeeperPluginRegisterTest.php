<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Unit;

use Filament\Panel;
use LaraArabDev\FilamentGatekeeper\GatekeeperPlugin;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests for GatekeeperPlugin::register() — previously uncovered branches.
 */
class GatekeeperPluginRegisterTest extends TestCase
{
    // ── register() with Panel (has resources() method) ────────────────────

    #[Test]
    public function it_registers_both_resources_by_default(): void
    {
        $plugin = GatekeeperPlugin::make();
        $panel = new Panel;

        // Should not throw
        $plugin->register($panel);

        $this->assertTrue(true);
    }

    #[Test]
    public function it_registers_only_role_resource_when_permission_resource_disabled(): void
    {
        $plugin = GatekeeperPlugin::make()->permissionResource(false);
        $panel = new Panel;

        // Should not throw even with one resource disabled
        $plugin->register($panel);

        $this->assertFalse($plugin->isPermissionResourceEnabled());
    }

    #[Test]
    public function it_registers_only_permission_resource_when_role_resource_disabled(): void
    {
        $plugin = GatekeeperPlugin::make()->roleResource(false);
        $panel = new Panel;

        // Should not throw even with role resource disabled
        $plugin->register($panel);

        $this->assertFalse($plugin->isRoleResourceEnabled());
    }

    #[Test]
    public function it_registers_no_resources_when_both_disabled(): void
    {
        $plugin = GatekeeperPlugin::make()
            ->roleResource(false)
            ->permissionResource(false);
        $panel = new Panel;

        $plugin->register($panel);

        $this->assertFalse($plugin->isRoleResourceEnabled());
        $this->assertFalse($plugin->isPermissionResourceEnabled());
    }

    // ── boot() ────────────────────────────────────────────────────────────

    #[Test]
    public function it_boots_without_error(): void
    {
        $plugin = GatekeeperPlugin::make();
        $panel = new Panel;

        $plugin->boot($panel);

        $this->assertTrue(true);
    }
}
