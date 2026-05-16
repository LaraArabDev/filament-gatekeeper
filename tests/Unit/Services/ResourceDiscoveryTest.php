<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Unit\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LaraArabDev\FilamentGatekeeper\Support\Discovery\ResourceDiscovery;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;

class ResourceDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    protected ResourceDiscovery $discovery;

    protected function setUp(): void
    {
        parent::setUp();

        $this->discovery = new ResourceDiscovery;
    }

    /** @test */
    public function it_can_be_instantiated(): void
    {
        $this->assertInstanceOf(ResourceDiscovery::class, $this->discovery);
    }

    /** @test */
    public function it_returns_array_from_discover(): void
    {
        $resources = $this->discovery->discover();

        $this->assertIsArray($resources);
    }

    /** @test */
    public function it_uses_configured_resource_paths(): void
    {
        config()->set('gatekeeper.discovery.resource_paths', [
            app_path('Filament/Resources'),
        ]);

        $discovery = new ResourceDiscovery;
        $resources = $discovery->discover();

        $this->assertIsArray($resources);
    }

    /** @test */
    public function it_can_get_resource_model_name(): void
    {
        $modelName = $this->discovery->getModelFromResource('UserResource');

        $this->assertEquals('User', $modelName);
    }

    /** @test */
    public function it_can_get_permission_name_from_resource(): void
    {
        $permissionName = $this->discovery->getPermissionName('UserResource');

        $this->assertEquals('user', $permissionName);
    }

    /** @test */
    public function it_can_get_permission_name_for_multi_word_resources(): void
    {
        $permissionName = $this->discovery->getPermissionName('BlogPostResource');

        $this->assertEquals('blog_post', $permissionName);
    }

    /** @test */
    public function it_can_discover_modules_resources(): void
    {
        config()->set('gatekeeper.modules.enabled', true);
        config()->set('gatekeeper.modules.path', base_path('Modules'));

        $discovery = new ResourceDiscovery;
        $resources = $discovery->discoverFromModules();

        $this->assertIsArray($resources);
    }
}
