<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Unit\Support\Discovery;

use LaraArabDev\FilamentGatekeeper\Support\Discovery\ModuleDiscovery;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;

class ModuleDiscoveryTest extends TestCase
{
    private ModuleDiscovery $discovery;

    protected function setUp(): void
    {
        parent::setUp();
        $this->discovery = new ModuleDiscovery();
    }

    /** @test */
    public function it_returns_false_when_modules_disabled(): void
    {
        config()->set('gatekeeper.modules.enabled', false);

        $this->assertFalse($this->discovery->isEnabled());
    }

    /** @test */
    public function it_returns_true_when_modules_enabled(): void
    {
        config()->set('gatekeeper.modules.enabled', true);

        $this->assertTrue($this->discovery->isEnabled());
    }

    /** @test */
    public function it_returns_empty_array_when_modules_disabled(): void
    {
        config()->set('gatekeeper.modules.enabled', false);

        $this->assertSame([], $this->discovery->getModules());
    }

    /** @test */
    public function it_returns_empty_array_when_modules_path_does_not_exist(): void
    {
        config()->set('gatekeeper.modules.enabled', true);
        config()->set('gatekeeper.modules.path', '/non/existent/path');

        $this->assertSame([], $this->discovery->getModules());
    }

    /** @test */
    public function it_discovers_modules_from_directory(): void
    {
        $tmpDir = sys_get_temp_dir() . '/gatekeeper_modules_test_' . uniqid();
        mkdir($tmpDir . '/Blog', 0755, true);
        mkdir($tmpDir . '/Shop', 0755, true);

        config()->set('gatekeeper.modules.enabled', true);
        config()->set('gatekeeper.modules.path', $tmpDir);

        $modules = $this->discovery->getModules();

        $this->assertContains('Blog', $modules);
        $this->assertContains('Shop', $modules);

        // Cleanup
        rmdir($tmpDir . '/Blog');
        rmdir($tmpDir . '/Shop');
        rmdir($tmpDir);
    }

    /** @test */
    public function it_returns_correct_module_namespace(): void
    {
        config()->set('gatekeeper.modules.namespace', 'Modules');

        $namespace = $this->discovery->getModuleNamespace('Blog');

        $this->assertSame('Modules\\Blog', $namespace);
    }

    /** @test */
    public function it_returns_correct_model_class(): void
    {
        config()->set('gatekeeper.modules.namespace', 'Modules');

        $class = $this->discovery->getModelClass('Blog', 'Post');

        $this->assertSame('Modules\\Blog\\Models\\Post', $class);
    }

    /** @test */
    public function it_returns_correct_resource_class(): void
    {
        config()->set('gatekeeper.modules.namespace', 'Modules');

        $class = $this->discovery->getResourceClass('Blog', 'PostResource');

        $this->assertSame('Modules\\Blog\\Filament\\Resources\\PostResource', $class);
    }

    /** @test */
    public function it_returns_empty_models_when_modules_disabled(): void
    {
        config()->set('gatekeeper.modules.enabled', false);

        $this->assertSame([], $this->discovery->getModels());
    }

    /** @test */
    public function it_returns_empty_resources_when_modules_disabled(): void
    {
        config()->set('gatekeeper.modules.enabled', false);

        $this->assertSame([], $this->discovery->getResources());
    }

    /** @test */
    public function it_returns_empty_pages_when_modules_disabled(): void
    {
        config()->set('gatekeeper.modules.enabled', false);

        $this->assertSame([], $this->discovery->getPages());
    }

    /** @test */
    public function it_returns_empty_widgets_when_modules_disabled(): void
    {
        config()->set('gatekeeper.modules.enabled', false);

        $this->assertSame([], $this->discovery->getWidgets());
    }
}
