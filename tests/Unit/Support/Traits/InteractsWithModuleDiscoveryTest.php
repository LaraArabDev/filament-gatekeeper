<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Unit\Support\Traits;

use LaraArabDev\FilamentGatekeeper\Support\Traits\InteractsWithModuleDiscovery;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;

class ConcreteModuleDiscoverer
{
    use InteractsWithModuleDiscovery;

    public function discover(string $pattern): array
    {
        return $this->discoverFromModules($pattern);
    }

    public function moduleEnabled(): bool
    {
        return $this->isModuleDiscoveryEnabled();
    }

    protected function scanDirectory(string $directory, string $pathPattern): array
    {
        // Return the directory name as a stand-in for discovered classes
        return [basename($directory)];
    }
}

class InteractsWithModuleDiscoveryTest extends TestCase
{
    private ConcreteModuleDiscoverer $discoverer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->discoverer = new ConcreteModuleDiscoverer();
    }

    /** @test */
    public function it_returns_false_when_module_discovery_is_disabled(): void
    {
        config()->set('gatekeeper.modules.enabled', false);

        $this->assertFalse($this->discoverer->moduleEnabled());
    }

    /** @test */
    public function it_returns_true_when_module_discovery_is_enabled(): void
    {
        config()->set('gatekeeper.modules.enabled', true);

        $this->assertTrue($this->discoverer->moduleEnabled());
    }

    /** @test */
    public function it_returns_empty_when_modules_path_does_not_exist(): void
    {
        config()->set('gatekeeper.modules.path', '/non/existent/path/abc123');

        $result = $this->discoverer->discover('{module}/Models');

        $this->assertSame([], $result);
    }

    /** @test */
    public function it_discovers_from_module_directories(): void
    {
        $tmpDir = sys_get_temp_dir() . '/gatekeeper_module_disc_' . uniqid();
        // Create module dirs with a Models subdirectory so scanDirectory is called
        mkdir($tmpDir . '/Blog/Models', 0755, true);
        mkdir($tmpDir . '/Shop/Models', 0755, true);

        config()->set('gatekeeper.modules.path', $tmpDir);

        // {module} is replaced with the full module path (e.g. /tmp/.../Blog)
        // so the pattern '{module}/Models' resolves to /tmp/.../Blog/Models
        $result = $this->discoverer->discover('{module}/Models');

        // scanDirectory returns basename of the Models directory (which is 'Models')
        $this->assertCount(2, $result);
        $this->assertContains('Models', $result);

        // Cleanup
        rmdir($tmpDir . '/Blog/Models');
        rmdir($tmpDir . '/Blog');
        rmdir($tmpDir . '/Shop/Models');
        rmdir($tmpDir . '/Shop');
        rmdir($tmpDir);
    }

    /** @test */
    public function it_skips_non_existent_sub_directories(): void
    {
        $tmpDir = sys_get_temp_dir() . '/gatekeeper_module_disc2_' . uniqid();
        mkdir($tmpDir . '/Blog', 0755, true);
        mkdir($tmpDir . '/Shop', 0755, true);

        config()->set('gatekeeper.modules.path', $tmpDir);

        // Pattern resolves to /tmp/.../Blog/NonExistentSubDir - doesn't exist
        $result = $this->discoverer->discover('{module}/NonExistentSubDir');

        $this->assertSame([], $result);

        // Cleanup
        rmdir($tmpDir . '/Blog');
        rmdir($tmpDir . '/Shop');
        rmdir($tmpDir);
    }
}
