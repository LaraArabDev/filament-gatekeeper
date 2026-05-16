<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Unit\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LaraArabDev\FilamentGatekeeper\Services\PermissionRegistrar;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class PermissionRegistrarWildcardTest extends TestCase
{
    use RefreshDatabase;

    protected PermissionRegistrar $registrar;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registrar = app(PermissionRegistrar::class);

        config()->set('gatekeeper.super_admin.enabled', false);
        config()->set('gatekeeper.discovery.resources', []);
        config()->set('gatekeeper.discovery.models', []);
        config()->set('gatekeeper.discovery.pages', []);
        config()->set('gatekeeper.discovery.widgets', []);
        config()->set('gatekeeper.field_discovery.enabled', false);
        config()->set('gatekeeper.column_discovery.enabled', false);
    }

    #[Test]
    public function it_skips_wildcard_key_in_field_permissions(): void
    {
        config()->set('gatekeeper.field_permissions', [
            '*' => ['name'],        // wildcard — should be skipped
            'User' => ['email'],    // normal — should be synced
        ]);

        $this->registrar->syncOnly('fields');

        // Wildcard key should NOT create permissions for '*'
        $this->assertDatabaseMissing('permissions', ['name' => 'view_*_name_field']);

        // Normal key should create permissions
        $this->assertDatabaseHas('permissions', ['name' => 'view_user_email_field']);
    }

    #[Test]
    public function it_skips_wildcard_key_in_column_permissions(): void
    {
        config()->set('gatekeeper.column_permissions', [
            '*' => ['status'],      // wildcard — should be skipped
            'Post' => ['title'],    // normal — should be synced
        ]);

        $this->registrar->syncOnly('columns');

        $this->assertDatabaseMissing('permissions', ['name' => 'view_*_status_column']);
        $this->assertDatabaseHas('permissions', ['name' => 'view_post_title_column']);
    }

    #[Test]
    public function it_skips_wildcard_key_in_action_permissions(): void
    {
        config()->set('gatekeeper.custom_actions', [
            '*' => ['export'],      // wildcard — should be skipped
            'Order' => ['ship'],    // normal — should be synced
        ]);

        $this->registrar->syncOnly('actions');

        $this->assertDatabaseMissing('permissions', ['name' => 'execute_*_export_action']);
        $this->assertDatabaseHas('permissions', ['name' => 'execute_order_ship_action']);
    }

    #[Test]
    public function it_skips_wildcard_key_in_relation_permissions(): void
    {
        config()->set('gatekeeper.relation_permissions', [
            '*' => ['tags'],        // wildcard — should be skipped
            'Product' => ['reviews'], // normal — should be synced
        ]);

        $this->registrar->syncOnly('relations');

        $this->assertDatabaseMissing('permissions', ['name' => 'view_*_tags_relation']);
        $this->assertDatabaseHas('permissions', ['name' => 'view_product_reviews_relation']);
    }

    #[Test]
    public function it_uses_field_discovery_when_enabled_with_missing_models_path(): void
    {
        // When field_discovery.enabled = true but the model path doesn't exist,
        // it falls back gracefully to config only
        config()->set('gatekeeper.field_discovery.enabled', true);
        config()->set('gatekeeper.discovery.models', ['/non/existent/path']);
        config()->set('gatekeeper.field_permissions', ['User' => ['email']]);

        $this->registrar->syncOnly('fields');

        // Should still sync what's in config
        $this->assertDatabaseHas('permissions', ['name' => 'view_user_email_field']);
    }

    #[Test]
    public function it_uses_column_discovery_when_enabled_with_missing_models_path(): void
    {
        config()->set('gatekeeper.column_discovery.enabled', true);
        config()->set('gatekeeper.discovery.models', ['/non/existent/path']);
        config()->set('gatekeeper.column_permissions', ['User' => ['name']]);

        $this->registrar->syncOnly('columns');

        $this->assertDatabaseHas('permissions', ['name' => 'view_user_name_column']);
    }
}
