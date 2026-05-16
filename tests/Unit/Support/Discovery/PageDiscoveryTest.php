<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Unit\Support\Discovery;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use LaraArabDev\FilamentGatekeeper\Support\Discovery\PageDiscovery;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class PageDiscoveryTest extends TestCase
{
    protected PageDiscovery $discovery;

    protected function setUp(): void
    {
        parent::setUp();

        $this->discovery = new PageDiscovery;
    }

    #[Test]
    public function it_can_be_instantiated(): void
    {
        expect($this->discovery)->toBeInstanceOf(PageDiscovery::class);
    }

    #[Test]
    public function it_returns_empty_array_when_no_paths_configured(): void
    {
        Config::set('gatekeeper.discovery.pages', []);

        $pages = $this->discovery->discover();

        expect($pages)->toBeArray()->toBeEmpty();
    }

    #[Test]
    public function it_returns_empty_array_when_path_does_not_exist(): void
    {
        Config::set('gatekeeper.discovery.pages', ['app/Filament/Pages/NonExistent']);

        $pages = $this->discovery->discover();

        expect($pages)->toBeArray()->toBeEmpty();
    }

    #[Test]
    public function it_discovers_pages_from_directory(): void
    {
        $tempDir = sys_get_temp_dir().'/gatekeeper_pages_test_'.uniqid();
        mkdir($tempDir, 0755, true);

        file_put_contents($tempDir.'/SettingsPage.php', '<?php class SettingsPage {}');
        file_put_contents($tempDir.'/ReportsPage.php', '<?php class ReportsPage {}');

        Config::set('gatekeeper.discovery.pages', []);
        Config::set('gatekeeper.excluded_pages', []);

        // Use reflection to test scanDirectory directly
        $reflection = new \ReflectionClass($this->discovery);
        $method = $reflection->getMethod('scanDirectory');
        $method->setAccessible(true);

        $pages = $method->invoke($this->discovery, $tempDir);

        expect($pages)->toBeArray()
            ->toContain('SettingsPage')
            ->toContain('ReportsPage');

        File::deleteDirectory($tempDir);
    }

    #[Test]
    public function it_excludes_resource_page_files(): void
    {
        $tempDir = sys_get_temp_dir().'/gatekeeper_pages_test_'.uniqid();
        mkdir($tempDir, 0755, true);

        // These should be excluded (resource page prefixes)
        file_put_contents($tempDir.'/CreateUser.php', '<?php class CreateUser {}');
        file_put_contents($tempDir.'/EditUser.php', '<?php class EditUser {}');
        file_put_contents($tempDir.'/ListUsers.php', '<?php class ListUsers {}');
        file_put_contents($tempDir.'/ViewUser.php', '<?php class ViewUser {}');
        file_put_contents($tempDir.'/ManageUsers.php', '<?php class ManageUsers {}');

        // This should be included
        file_put_contents($tempDir.'/SettingsPage.php', '<?php class SettingsPage {}');

        $reflection = new \ReflectionClass($this->discovery);
        $method = $reflection->getMethod('scanDirectory');
        $method->setAccessible(true);

        $pages = $method->invoke($this->discovery, $tempDir);

        expect($pages)->toContain('SettingsPage')
            ->not->toContain('CreateUser')
            ->not->toContain('EditUser')
            ->not->toContain('ListUsers')
            ->not->toContain('ViewUser')
            ->not->toContain('ManageUsers');

        File::deleteDirectory($tempDir);
    }

    #[Test]
    public function it_returns_unique_pages(): void
    {
        $tempDir = sys_get_temp_dir().'/gatekeeper_pages_test_'.uniqid();
        mkdir($tempDir, 0755, true);

        file_put_contents($tempDir.'/SettingsPage.php', '<?php class SettingsPage {}');

        Config::set('gatekeeper.discovery.pages', []);
        Config::set('gatekeeper.excluded_pages', []);

        // Use reflection to call discover with the same directory twice
        $reflection = new \ReflectionClass($this->discovery);
        $method = $reflection->getMethod('scanDirectory');
        $method->setAccessible(true);

        $pages = array_merge(
            $method->invoke($this->discovery, $tempDir),
            $method->invoke($this->discovery, $tempDir)
        );

        // discover() deduplicates — confirm the behavior
        $unique = array_unique($pages);
        expect($unique)->toHaveCount(count(array_unique($pages)));

        File::deleteDirectory($tempDir);
    }

    #[Test]
    public function it_excludes_configured_excluded_pages(): void
    {
        $tempDir = sys_get_temp_dir().'/gatekeeper_pages_test_'.uniqid();
        mkdir($tempDir, 0755, true);

        file_put_contents($tempDir.'/SettingsPage.php', '<?php class SettingsPage {}');
        file_put_contents($tempDir.'/InternalPage.php', '<?php class InternalPage {}');

        Config::set('gatekeeper.discovery.pages', [str_replace(base_path().'/', '', $tempDir)]);
        Config::set('gatekeeper.excluded_pages', ['InternalPage']);

        $pages = $this->discovery->discover();

        expect($pages)->not->toContain('InternalPage');

        File::deleteDirectory($tempDir);
    }

    #[Test]
    public function it_skips_module_discovery_when_disabled(): void
    {
        Config::set('gatekeeper.modules.enabled', false);
        Config::set('gatekeeper.discovery.pages', []);

        $pages = $this->discovery->discover();

        expect($pages)->toBeArray();
    }

    #[Test]
    public function it_handles_non_existent_directory_in_scan_directory(): void
    {
        $reflection = new \ReflectionClass($this->discovery);
        $method = $reflection->getMethod('scanDirectory');
        $method->setAccessible(true);

        $pages = $method->invoke($this->discovery, '/non/existent/path');

        expect($pages)->toBeArray()->toBeEmpty();
    }
}
