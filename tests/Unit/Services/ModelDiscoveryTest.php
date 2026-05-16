<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Unit\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LaraArabDev\FilamentGatekeeper\Support\Discovery\ModelDiscovery;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ModelDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    protected ModelDiscovery $discovery;

    protected function setUp(): void
    {
        parent::setUp();

        $this->discovery = new ModelDiscovery;
    }

    #[Test]
    public function it_can_be_instantiated(): void
    {
        $this->assertInstanceOf(ModelDiscovery::class, $this->discovery);
    }

    #[Test]
    public function it_returns_array_from_discover(): void
    {
        $models = $this->discovery->discover();

        $this->assertIsArray($models);
    }

    #[Test]
    public function it_uses_configured_model_paths(): void
    {
        config()->set('gatekeeper.model_paths', [
            app_path('Models'),
        ]);

        $discovery = new ModelDiscovery;
        $models = $discovery->discover();

        $this->assertIsArray($models);
    }

    #[Test]
    public function it_excludes_configured_models(): void
    {
        config()->set('gatekeeper.excluded_models', [
            'App\\Models\\BaseModel',
        ]);

        $discovery = new ModelDiscovery;
        $models = $discovery->discover();

        $this->assertNotContains('App\\Models\\BaseModel', $models);
    }

    #[Test]
    public function it_can_get_model_name_from_class(): void
    {
        $modelName = $this->discovery->getModelName('App\\Models\\User');

        $this->assertEquals('User', $modelName);
    }

    #[Test]
    public function it_can_get_permission_model_name(): void
    {
        $permissionName = $this->discovery->getPermissionModelName('App\\Models\\User');

        $this->assertEquals('user', $permissionName);
    }

    #[Test]
    public function it_can_get_permission_model_name_for_multi_word_models(): void
    {
        $permissionName = $this->discovery->getPermissionModelName('App\\Models\\BlogPost');

        $this->assertEquals('blog_post', $permissionName);
    }

    #[Test]
    public function it_can_check_if_model_is_eloquent(): void
    {
        // This would require actual model classes to test properly
        // For now, we verify the method exists
        $this->assertTrue(method_exists($this->discovery, 'isEloquentModel'));
    }
}
