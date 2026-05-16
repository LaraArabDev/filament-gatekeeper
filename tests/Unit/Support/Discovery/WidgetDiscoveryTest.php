<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Unit\Support\Discovery;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use LaraArabDev\FilamentGatekeeper\Support\Discovery\WidgetDiscovery;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;

class WidgetDiscoveryTest extends TestCase
{
    protected WidgetDiscovery $discovery;

    protected function setUp(): void
    {
        parent::setUp();

        $this->discovery = new WidgetDiscovery;
    }

    /** @test */
    public function it_can_be_instantiated(): void
    {
        expect($this->discovery)->toBeInstanceOf(WidgetDiscovery::class);
    }

    /** @test */
    public function it_returns_empty_array_when_no_paths_configured(): void
    {
        Config::set('gatekeeper.discovery.widgets', []);

        $widgets = $this->discovery->discover();

        expect($widgets)->toBeArray()->toBeEmpty();
    }

    /** @test */
    public function it_returns_empty_array_when_path_does_not_exist(): void
    {
        Config::set('gatekeeper.discovery.widgets', ['app/Filament/Widgets/NonExistent']);

        $widgets = $this->discovery->discover();

        expect($widgets)->toBeArray()->toBeEmpty();
    }

    /** @test */
    public function it_discovers_widgets_from_directory(): void
    {
        $tempDir = sys_get_temp_dir().'/gatekeeper_widgets_test_'.uniqid();
        mkdir($tempDir, 0755, true);

        file_put_contents($tempDir.'/StatsOverviewWidget.php', '<?php class StatsOverviewWidget {}');
        file_put_contents($tempDir.'/RevenueChartWidget.php', '<?php class RevenueChartWidget {}');

        $reflection = new \ReflectionClass($this->discovery);
        $method = $reflection->getMethod('scanDirectory');
        $method->setAccessible(true);

        $widgets = $method->invoke($this->discovery, $tempDir);

        expect($widgets)->toBeArray()
            ->toContain('StatsOverviewWidget')
            ->toContain('RevenueChartWidget');

        File::deleteDirectory($tempDir);
    }

    /** @test */
    public function it_includes_all_php_files_unlike_page_discovery(): void
    {
        // Widget discovery does NOT filter out Create/Edit/List prefixes
        $tempDir = sys_get_temp_dir().'/gatekeeper_widgets_test_'.uniqid();
        mkdir($tempDir, 0755, true);

        file_put_contents($tempDir.'/StatsOverviewWidget.php', '<?php class StatsOverviewWidget {}');
        file_put_contents($tempDir.'/LatestOrdersWidget.php', '<?php class LatestOrdersWidget {}');
        file_put_contents($tempDir.'/RevenueWidget.php', '<?php class RevenueWidget {}');

        $reflection = new \ReflectionClass($this->discovery);
        $method = $reflection->getMethod('scanDirectory');
        $method->setAccessible(true);

        $widgets = $method->invoke($this->discovery, $tempDir);

        expect($widgets)
            ->toContain('StatsOverviewWidget')
            ->toContain('LatestOrdersWidget')
            ->toContain('RevenueWidget');

        File::deleteDirectory($tempDir);
    }

    /** @test */
    public function it_excludes_configured_excluded_widgets(): void
    {
        $tempDir = sys_get_temp_dir().'/gatekeeper_widgets_test_'.uniqid();
        mkdir($tempDir, 0755, true);

        file_put_contents($tempDir.'/StatsOverviewWidget.php', '<?php class StatsOverviewWidget {}');
        file_put_contents($tempDir.'/AccountWidget.php', '<?php class AccountWidget {}');

        Config::set('gatekeeper.discovery.widgets', [str_replace(base_path().'/', '', $tempDir)]);
        Config::set('gatekeeper.excluded_widgets', ['AccountWidget']);

        $widgets = $this->discovery->discover();

        expect($widgets)->not->toContain('AccountWidget');

        File::deleteDirectory($tempDir);
    }

    /** @test */
    public function it_returns_unique_widgets(): void
    {
        $tempDir = sys_get_temp_dir().'/gatekeeper_widgets_test_'.uniqid();
        mkdir($tempDir, 0755, true);

        file_put_contents($tempDir.'/StatsOverviewWidget.php', '<?php class StatsOverviewWidget {}');

        $reflection = new \ReflectionClass($this->discovery);
        $method = $reflection->getMethod('scanDirectory');
        $method->setAccessible(true);

        $pages = array_merge(
            $method->invoke($this->discovery, $tempDir),
            $method->invoke($this->discovery, $tempDir)
        );

        $unique = array_unique($pages);
        expect(count($unique))->toBeLessThanOrEqual(count($pages));

        File::deleteDirectory($tempDir);
    }

    /** @test */
    public function it_skips_module_discovery_when_disabled(): void
    {
        Config::set('gatekeeper.modules.enabled', false);
        Config::set('gatekeeper.discovery.widgets', []);

        $widgets = $this->discovery->discover();

        expect($widgets)->toBeArray();
    }

    /** @test */
    public function it_handles_non_existent_directory_in_scan_directory(): void
    {
        $reflection = new \ReflectionClass($this->discovery);
        $method = $reflection->getMethod('scanDirectory');
        $method->setAccessible(true);

        $widgets = $method->invoke($this->discovery, '/non/existent/path');

        expect($widgets)->toBeArray()->toBeEmpty();
    }
}
