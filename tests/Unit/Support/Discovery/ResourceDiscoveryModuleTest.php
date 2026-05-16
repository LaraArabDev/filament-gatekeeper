<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Unit\Support\Discovery;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LaraArabDev\FilamentGatekeeper\Support\Discovery\PageDiscovery;
use LaraArabDev\FilamentGatekeeper\Support\Discovery\ResourceDiscovery;
use LaraArabDev\FilamentGatekeeper\Support\Discovery\WidgetDiscovery;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;

/**
 * Tests for module-based discovery in ResourceDiscovery, PageDiscovery, and WidgetDiscovery.
 *
 * Covers discoverModuleResources(), discoverFromModules(), discoverModulePages(),
 * and discoverModuleWidgets() methods.
 */
class ResourceDiscoveryModuleTest extends TestCase
{
    use RefreshDatabase;

    private string $tempDir = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir().'/gatekeeper_modules_'.uniqid();
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);

        parent::tearDown();
    }

    // ---------------------------------------------------------------------------
    // ResourceDiscovery – discoverModuleResources() / discoverFromModules()
    // ---------------------------------------------------------------------------

    /** @test */
    public function resource_discover_from_modules_returns_empty_when_modules_path_does_not_exist(): void
    {
        config()->set('gatekeeper.modules.enabled', true);
        config()->set('gatekeeper.modules.path', '/non/existent/path/xyz_'.uniqid());

        $discovery = new ResourceDiscovery;
        $result = $discovery->discoverFromModules();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /** @test */
    public function resource_discover_from_modules_returns_empty_when_module_has_no_resources_dir(): void
    {
        mkdir($this->tempDir.'/Blog', 0755, true);

        config()->set('gatekeeper.modules.enabled', true);
        config()->set('gatekeeper.modules.path', $this->tempDir);

        $discovery = new ResourceDiscovery;
        $result = $discovery->discoverFromModules();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /** @test */
    public function resource_discover_from_modules_discovers_resource_files(): void
    {
        $resourcesDir = $this->tempDir.'/Blog/Filament/Resources';
        mkdir($resourcesDir, 0755, true);
        file_put_contents($resourcesDir.'/PostResource.php', '<?php class PostResource {}');
        file_put_contents($resourcesDir.'/CategoryResource.php', '<?php class CategoryResource {}');

        config()->set('gatekeeper.modules.enabled', true);
        config()->set('gatekeeper.modules.path', $this->tempDir);

        $discovery = new ResourceDiscovery;
        $result = $discovery->discoverFromModules();

        $this->assertIsArray($result);
        $this->assertContains('Post', $result);
        $this->assertContains('Category', $result);
    }

    /** @test */
    public function resource_discover_from_modules_skips_non_resource_files(): void
    {
        $resourcesDir = $this->tempDir.'/Blog/Filament/Resources';
        mkdir($resourcesDir, 0755, true);
        file_put_contents($resourcesDir.'/PostResource.php', '<?php class PostResource {}');
        file_put_contents($resourcesDir.'/helpers.php', '<?php function helper() {}');

        config()->set('gatekeeper.modules.enabled', true);
        config()->set('gatekeeper.modules.path', $this->tempDir);

        $discovery = new ResourceDiscovery;
        $result = $discovery->discoverFromModules();

        $this->assertContains('Post', $result);
        $this->assertNotContains('helpers', $result);
    }

    /** @test */
    public function resource_discover_from_modules_uses_custom_discovery_path(): void
    {
        $resourcesDir = $this->tempDir.'/Blog/Http/Resources';
        mkdir($resourcesDir, 0755, true);
        file_put_contents($resourcesDir.'/ArticleResource.php', '<?php class ArticleResource {}');

        config()->set('gatekeeper.modules.enabled', true);
        config()->set('gatekeeper.modules.path', $this->tempDir);
        config()->set('gatekeeper.modules.discovery_paths.resources', '{module}/Http/Resources');

        $discovery = new ResourceDiscovery;
        $result = $discovery->discoverFromModules();

        $this->assertContains('Article', $result);
    }

    /** @test */
    public function resource_discover_includes_module_resources_when_modules_enabled(): void
    {
        $resourcesDir = $this->tempDir.'/Shop/Filament/Resources';
        mkdir($resourcesDir, 0755, true);
        file_put_contents($resourcesDir.'/ProductResource.php', '<?php class ProductResource {}');

        config()->set('gatekeeper.modules.enabled', true);
        config()->set('gatekeeper.modules.path', $this->tempDir);
        config()->set('gatekeeper.discovery.resources', []);

        $discovery = new ResourceDiscovery;
        $result = $discovery->discover();

        $this->assertContains('Product', $result);
    }

    /** @test */
    public function resource_discover_with_multiple_modules(): void
    {
        mkdir($this->tempDir.'/Blog/Filament/Resources', 0755, true);
        mkdir($this->tempDir.'/Shop/Filament/Resources', 0755, true);

        file_put_contents($this->tempDir.'/Blog/Filament/Resources/PostResource.php', '<?php class PostResource {}');
        file_put_contents($this->tempDir.'/Shop/Filament/Resources/ProductResource.php', '<?php class ProductResource {}');

        config()->set('gatekeeper.modules.enabled', true);
        config()->set('gatekeeper.modules.path', $this->tempDir);

        $discovery = new ResourceDiscovery;
        $result = $discovery->discoverFromModules();

        $this->assertContains('Post', $result);
        $this->assertContains('Product', $result);
    }

    // ---------------------------------------------------------------------------
    // PageDiscovery – discoverModulePages()
    // ---------------------------------------------------------------------------

    /** @test */
    public function page_discover_from_modules_returns_empty_when_modules_path_does_not_exist(): void
    {
        config()->set('gatekeeper.modules.enabled', true);
        config()->set('gatekeeper.modules.path', '/non/existent/path/xyz_'.uniqid());

        $discovery = new PageDiscovery;
        $result = $discovery->discover();

        $this->assertIsArray($result);
    }

    /** @test */
    public function page_discover_from_modules_returns_empty_when_module_has_no_pages_dir(): void
    {
        mkdir($this->tempDir.'/Blog', 0755, true);

        config()->set('gatekeeper.modules.enabled', true);
        config()->set('gatekeeper.modules.path', $this->tempDir);
        config()->set('gatekeeper.discovery.pages', []);

        $discovery = new PageDiscovery;
        $result = $discovery->discover();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /** @test */
    public function page_discover_from_modules_discovers_page_files(): void
    {
        $pagesDir = $this->tempDir.'/Blog/Filament/Pages';
        mkdir($pagesDir, 0755, true);
        file_put_contents($pagesDir.'/Dashboard.php', '<?php class Dashboard {}');
        file_put_contents($pagesDir.'/Settings.php', '<?php class Settings {}');

        config()->set('gatekeeper.modules.enabled', true);
        config()->set('gatekeeper.modules.path', $this->tempDir);
        config()->set('gatekeeper.discovery.pages', []);

        $discovery = new PageDiscovery;
        $result = $discovery->discover();

        $this->assertContains('Dashboard', $result);
        $this->assertContains('Settings', $result);
    }

    /** @test */
    public function page_discover_from_modules_skips_resource_page_files(): void
    {
        $pagesDir = $this->tempDir.'/Blog/Filament/Pages';
        mkdir($pagesDir, 0755, true);
        file_put_contents($pagesDir.'/Dashboard.php', '<?php class Dashboard {}');
        file_put_contents($pagesDir.'/CreatePost.php', '<?php class CreatePost {}');
        file_put_contents($pagesDir.'/EditPost.php', '<?php class EditPost {}');
        file_put_contents($pagesDir.'/ListPosts.php', '<?php class ListPosts {}');

        config()->set('gatekeeper.modules.enabled', true);
        config()->set('gatekeeper.modules.path', $this->tempDir);
        config()->set('gatekeeper.discovery.pages', []);

        $discovery = new PageDiscovery;
        $result = $discovery->discover();

        $this->assertContains('Dashboard', $result);
        $this->assertNotContains('CreatePost', $result);
        $this->assertNotContains('EditPost', $result);
        $this->assertNotContains('ListPosts', $result);
    }

    /** @test */
    public function page_discover_from_modules_uses_custom_discovery_path(): void
    {
        $pagesDir = $this->tempDir.'/Blog/Pages';
        mkdir($pagesDir, 0755, true);
        file_put_contents($pagesDir.'/Analytics.php', '<?php class Analytics {}');

        config()->set('gatekeeper.modules.enabled', true);
        config()->set('gatekeeper.modules.path', $this->tempDir);
        config()->set('gatekeeper.modules.discovery_paths.pages', '{module}/Pages');
        config()->set('gatekeeper.discovery.pages', []);

        $discovery = new PageDiscovery;
        $result = $discovery->discover();

        $this->assertContains('Analytics', $result);
    }

    // ---------------------------------------------------------------------------
    // WidgetDiscovery – discoverModuleWidgets()
    // ---------------------------------------------------------------------------

    /** @test */
    public function widget_discover_from_modules_returns_empty_when_modules_path_does_not_exist(): void
    {
        config()->set('gatekeeper.modules.enabled', true);
        config()->set('gatekeeper.modules.path', '/non/existent/path/xyz_'.uniqid());

        $discovery = new WidgetDiscovery;
        $result = $discovery->discover();

        $this->assertIsArray($result);
    }

    /** @test */
    public function widget_discover_from_modules_returns_empty_when_module_has_no_widgets_dir(): void
    {
        mkdir($this->tempDir.'/Blog', 0755, true);

        config()->set('gatekeeper.modules.enabled', true);
        config()->set('gatekeeper.modules.path', $this->tempDir);
        config()->set('gatekeeper.discovery.widgets', []);

        $discovery = new WidgetDiscovery;
        $result = $discovery->discover();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /** @test */
    public function widget_discover_from_modules_discovers_widget_files(): void
    {
        $widgetsDir = $this->tempDir.'/Blog/Filament/Widgets';
        mkdir($widgetsDir, 0755, true);
        file_put_contents($widgetsDir.'/StatsWidget.php', '<?php class StatsWidget {}');
        file_put_contents($widgetsDir.'/RecentPostsWidget.php', '<?php class RecentPostsWidget {}');

        config()->set('gatekeeper.modules.enabled', true);
        config()->set('gatekeeper.modules.path', $this->tempDir);
        config()->set('gatekeeper.discovery.widgets', []);

        $discovery = new WidgetDiscovery;
        $result = $discovery->discover();

        $this->assertContains('StatsWidget', $result);
        $this->assertContains('RecentPostsWidget', $result);
    }

    /** @test */
    public function widget_discover_from_modules_uses_custom_discovery_path(): void
    {
        $widgetsDir = $this->tempDir.'/Blog/Widgets';
        mkdir($widgetsDir, 0755, true);
        file_put_contents($widgetsDir.'/CustomWidget.php', '<?php class CustomWidget {}');

        config()->set('gatekeeper.modules.enabled', true);
        config()->set('gatekeeper.modules.path', $this->tempDir);
        config()->set('gatekeeper.modules.discovery_paths.widgets', '{module}/Widgets');
        config()->set('gatekeeper.discovery.widgets', []);

        $discovery = new WidgetDiscovery;
        $result = $discovery->discover();

        $this->assertContains('CustomWidget', $result);
    }

    /** @test */
    public function widget_discover_with_multiple_modules(): void
    {
        mkdir($this->tempDir.'/Blog/Filament/Widgets', 0755, true);
        mkdir($this->tempDir.'/Shop/Filament/Widgets', 0755, true);

        file_put_contents($this->tempDir.'/Blog/Filament/Widgets/BlogWidget.php', '<?php class BlogWidget {}');
        file_put_contents($this->tempDir.'/Shop/Filament/Widgets/ShopWidget.php', '<?php class ShopWidget {}');

        config()->set('gatekeeper.modules.enabled', true);
        config()->set('gatekeeper.modules.path', $this->tempDir);
        config()->set('gatekeeper.discovery.widgets', []);

        $discovery = new WidgetDiscovery;
        $result = $discovery->discover();

        $this->assertContains('BlogWidget', $result);
        $this->assertContains('ShopWidget', $result);
    }

    // ---------------------------------------------------------------------------
    // Helper
    // ---------------------------------------------------------------------------

    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir) ?: [], ['.', '..']);

        foreach ($files as $file) {
            $path = $dir.DIRECTORY_SEPARATOR.$file;

            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }

        rmdir($dir);
    }
}
