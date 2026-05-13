<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Unit\Concerns;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LaraArabDev\FilamentGatekeeper\Concerns\HasWidgetPermissions;
use LaraArabDev\FilamentGatekeeper\Models\Permission;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;

class HasWidgetPermissionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_allows_view_when_user_has_widget_permission(): void
    {
        $user = $this->createUser();

        Permission::factory()->widget()->create([
            'name' => 'view_widget_stats_overview',
        ]);

        $user->givePermissionTo('view_widget_stats_overview');

        $this->actingAs($user);

        $this->assertTrue(StatsOverviewWidget::canView());
    }

    /** @test */
    public function it_denies_view_without_widget_permission(): void
    {
        $user = $this->createUser();

        $this->actingAs($user);

        $this->assertFalse(StatsOverviewWidget::canView());
    }

    /** @test */
    public function it_bypasses_widget_permissions_for_super_admin(): void
    {
        $user = $this->createSuperAdmin();

        $this->actingAs($user);

        $this->assertTrue(StatsOverviewWidget::canView());
        $this->assertTrue(RevenueChartWidget::canView());
    }

    /** @test */
    public function it_generates_correct_widget_permission_name(): void
    {
        // StatsOverviewWidget -> view_widget_stats_overview
        $this->assertEquals('view_widget_stats_overview', StatsOverviewWidget::getWidgetPermissionNamePublic());
    }

    /** @test */
    public function it_strips_widget_suffix_from_class_name(): void
    {
        // RevenueChartWidget -> view_widget_revenue_chart
        $this->assertEquals('view_widget_revenue_chart', RevenueChartWidget::getWidgetPermissionNamePublic());
    }

    /** @test */
    public function it_generates_snake_case_permission_name(): void
    {
        // LatestOrdersWidget -> view_widget_latest_orders
        $this->assertEquals('view_widget_latest_orders', LatestOrdersWidget::getWidgetPermissionNamePublic());
    }

    /** @test */
    public function it_checks_different_widgets_independently(): void
    {
        $user = $this->createUser();

        Permission::factory()->widget()->create(['name' => 'view_widget_stats_overview']);

        $user->givePermissionTo('view_widget_stats_overview');

        $this->actingAs($user);

        $this->assertTrue(StatsOverviewWidget::canView());
        $this->assertFalse(RevenueChartWidget::canView());
    }

    /** @test */
    public function it_controls_visibility_via_shouldBeVisible(): void
    {
        $user = $this->createUser();

        $this->actingAs($user);

        $this->assertFalse(StatsOverviewWidget::shouldBeVisible());

        Permission::factory()->widget()->create(['name' => 'view_widget_stats_overview']);
        $user->givePermissionTo('view_widget_stats_overview');

        $this->assertTrue(StatsOverviewWidget::shouldBeVisible());
    }

    /** @test */
    public function it_shouldBeVisible_matches_canView(): void
    {
        $user = $this->createUser();

        Permission::factory()->widget()->create(['name' => 'view_widget_stats_overview']);
        $user->givePermissionTo('view_widget_stats_overview');

        $this->actingAs($user);

        $this->assertEquals(StatsOverviewWidget::canView(), StatsOverviewWidget::shouldBeVisible());
    }
}

class StatsOverviewWidget
{
    use HasWidgetPermissions;

    public static function getWidgetPermissionNamePublic(): string
    {
        return static::getWidgetPermissionName();
    }
}

class RevenueChartWidget
{
    use HasWidgetPermissions;

    public static function getWidgetPermissionNamePublic(): string
    {
        return static::getWidgetPermissionName();
    }
}

class LatestOrdersWidget
{
    use HasWidgetPermissions;

    public static function getWidgetPermissionNamePublic(): string
    {
        return static::getWidgetPermissionName();
    }
}
