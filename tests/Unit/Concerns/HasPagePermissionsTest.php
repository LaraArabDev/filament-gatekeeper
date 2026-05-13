<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Unit\Concerns;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LaraArabDev\FilamentGatekeeper\Concerns\HasPagePermissions;
use LaraArabDev\FilamentGatekeeper\Models\Permission;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;

class HasPagePermissionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_allows_access_when_user_has_page_permission(): void
    {
        $user = $this->createUser();

        Permission::factory()->page()->create([
            'name' => 'view_page_settings',
        ]);

        $user->givePermissionTo('view_page_settings');

        $this->actingAs($user);

        $this->assertTrue(SettingsPage::canAccess());
    }

    /** @test */
    public function it_denies_access_without_page_permission(): void
    {
        $user = $this->createUser();

        $this->actingAs($user);

        $this->assertFalse(SettingsPage::canAccess());
    }

    /** @test */
    public function it_bypasses_page_permissions_for_super_admin(): void
    {
        $user = $this->createSuperAdmin();

        $this->actingAs($user);

        $this->assertTrue(SettingsPage::canAccess());
        $this->assertTrue(ReportsPage::canAccess());
    }

    /** @test */
    public function it_generates_correct_page_permission_name(): void
    {
        // SettingsPage -> view_page_settings
        $this->assertEquals('view_page_settings', SettingsPage::getPagePermissionNamePublic());
    }

    /** @test */
    public function it_strips_page_suffix_from_class_name(): void
    {
        // ReportsPage -> view_page_reports
        $this->assertEquals('view_page_reports', ReportsPage::getPagePermissionNamePublic());
    }

    /** @test */
    public function it_generates_snake_case_permission_name(): void
    {
        // UserManagementPage -> view_page_user_management
        $this->assertEquals('view_page_user_management', UserManagementPage::getPagePermissionNamePublic());
    }

    /** @test */
    public function it_controls_navigation_visibility(): void
    {
        $user = $this->createUser();

        $this->actingAs($user);

        // No permission → should not show in nav
        $this->assertFalse(SettingsPage::shouldRegisterNavigation());

        Permission::factory()->page()->create(['name' => 'view_page_settings']);
        $user->givePermissionTo('view_page_settings');

        // With permission → should show in nav
        $this->assertTrue(SettingsPage::shouldRegisterNavigation());
    }

    /** @test */
    public function it_returns_null_navigation_badge_by_default(): void
    {
        $this->assertNull(SettingsPage::getNavigationBadge());
    }

    /** @test */
    public function it_checks_different_pages_independently(): void
    {
        $user = $this->createUser();

        Permission::factory()->page()->create(['name' => 'view_page_settings']);

        $user->givePermissionTo('view_page_settings');

        $this->actingAs($user);

        $this->assertTrue(SettingsPage::canAccess());
        $this->assertFalse(ReportsPage::canAccess());
    }
}

class SettingsPage
{
    use HasPagePermissions;

    public static function getPagePermissionNamePublic(): string
    {
        return static::getPagePermissionName();
    }
}

class ReportsPage
{
    use HasPagePermissions;

    public static function getPagePermissionNamePublic(): string
    {
        return static::getPagePermissionName();
    }
}

class UserManagementPage
{
    use HasPagePermissions;

    public static function getPagePermissionNamePublic(): string
    {
        return static::getPagePermissionName();
    }
}
