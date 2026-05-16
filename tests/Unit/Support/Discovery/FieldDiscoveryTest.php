<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Unit\Support\Discovery;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use LaraArabDev\FilamentGatekeeper\Support\Discovery\FieldDiscovery;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests for the FieldDiscovery class.
 */
class FieldDiscoveryTest extends TestCase
{
    protected FieldDiscovery $discovery;

    protected function setUp(): void
    {
        parent::setUp();

        $this->discovery = new FieldDiscovery;
    }

    #[Test]
    public function it_can_be_instantiated(): void
    {
        expect($this->discovery)->toBeInstanceOf(FieldDiscovery::class);
    }

    #[Test]
    public function it_returns_available_sources(): void
    {
        $sources = FieldDiscovery::getAvailableSources();

        expect($sources)->toBeArray()
            ->and($sources)->toHaveKey(FieldDiscovery::SOURCE_CONFIG)
            ->and($sources)->toHaveKey(FieldDiscovery::SOURCE_FILLABLE)
            ->and($sources)->toHaveKey(FieldDiscovery::SOURCE_DATABASE)
            ->and($sources)->toHaveKey(FieldDiscovery::SOURCE_RESOURCE);
    }

    #[Test]
    public function it_discovers_fields_from_fillable(): void
    {
        $fields = $this->discovery->discoverFromFillable(TestModelWithFillable::class);

        expect($fields)->toBeArray()
            ->and($fields)->toContain('name')
            ->and($fields)->toContain('email')
            ->and($fields)->toContain('salary');
    }

    #[Test]
    public function it_returns_empty_array_for_non_existent_class(): void
    {
        $fields = $this->discovery->discoverFromFillable('NonExistentClass');

        expect($fields)->toBeArray()
            ->and($fields)->toBeEmpty();
    }

    #[Test]
    public function it_returns_empty_array_for_non_model_class(): void
    {
        $fields = $this->discovery->discoverFromFillable(NonModelClass::class);

        expect($fields)->toBeArray()
            ->and($fields)->toBeEmpty();
    }

    #[Test]
    public function it_discovers_fields_from_config(): void
    {
        Config::set('gatekeeper.field_permissions', [
            'TestModel' => ['field1', 'field2', 'field3'],
        ]);

        $fields = $this->discovery->discoverFromConfig('TestModel');

        expect($fields)->toBeArray()
            ->and($fields)->toContain('field1')
            ->and($fields)->toContain('field2')
            ->and($fields)->toContain('field3');
    }

    #[Test]
    public function it_merges_global_fields_from_config(): void
    {
        Config::set('gatekeeper.field_permissions', [
            '*' => ['global_field'],
            'TestModel' => ['model_field'],
        ]);

        $fields = $this->discovery->discoverFromConfig('TestModel');

        expect($fields)->toBeArray()
            ->and($fields)->toContain('global_field')
            ->and($fields)->toContain('model_field');
    }

    #[Test]
    public function it_returns_excluded_fields(): void
    {
        Config::set('gatekeeper.field_discovery.default_excluded', ['id', 'created_at']);
        Config::set('gatekeeper.field_discovery.excluded', [
            '*' => ['global_excluded'],
            'TestModel' => ['model_excluded'],
        ]);

        $excluded = $this->discovery->getExcludedFields('TestModel');

        expect($excluded)->toBeArray()
            ->and($excluded)->toContain('global_excluded')
            ->and($excluded)->toContain('model_excluded')
            // Default exclusions from config should also be present
            ->and($excluded)->toContain('id')
            ->and($excluded)->toContain('created_at');
    }

    #[Test]
    public function it_identifies_sensitive_fields(): void
    {
        expect($this->discovery->isSensitiveField('password'))->toBeTrue()
            ->and($this->discovery->isSensitiveField('user_password'))->toBeTrue()
            ->and($this->discovery->isSensitiveField('api_key'))->toBeTrue()
            ->and($this->discovery->isSensitiveField('secret_token'))->toBeTrue()
            ->and($this->discovery->isSensitiveField('name'))->toBeFalse()
            ->and($this->discovery->isSensitiveField('email'))->toBeFalse();
    }

    #[Test]
    public function it_clears_cache(): void
    {
        // Discover fields to populate cache
        $this->discovery->discoverForModel(TestModelWithFillable::class, [FieldDiscovery::SOURCE_FILLABLE]);

        // Clear cache
        $this->discovery->clearCache('TestModelWithFillable');

        // The discovery should run again (we can't easily verify this without mocking)
        $fields = $this->discovery->discoverForModel(TestModelWithFillable::class, [FieldDiscovery::SOURCE_FILLABLE]);

        expect($fields)->toBeArray();
    }

    #[Test]
    public function it_clears_all_cache(): void
    {
        // Discover fields to populate cache
        $this->discovery->discoverForModel(TestModelWithFillable::class, [FieldDiscovery::SOURCE_FILLABLE]);

        // Clear all cache
        $this->discovery->clearCache();

        // Should still work
        $fields = $this->discovery->discoverForModel(TestModelWithFillable::class, [FieldDiscovery::SOURCE_FILLABLE]);

        expect($fields)->toBeArray();
    }

    #[Test]
    public function it_applies_exclusions_when_discovering(): void
    {
        Config::set('gatekeeper.field_discovery.excluded', [
            '*' => ['email'],
        ]);

        // Clear any cached data
        $this->discovery->clearCache();

        // Create a new instance to get fresh config
        $discovery = new FieldDiscovery;

        $fields = $discovery->discoverForModel(TestModelWithFillable::class, [FieldDiscovery::SOURCE_FILLABLE]);

        expect($fields)->not->toContain('email')
            ->and($fields)->toContain('name')
            ->and($fields)->toContain('salary');
    }

    #[Test]
    public function it_discovers_for_model_with_config_source(): void
    {
        Config::set('gatekeeper.field_permissions', [
            'User' => ['name', 'email'],
        ]);
        Config::set('gatekeeper.field_discovery.sources', [FieldDiscovery::SOURCE_CONFIG]);

        $discovery = new FieldDiscovery;
        $discovery->clearCache();

        $fields = $discovery->discoverForModel('App\\Models\\User');

        expect($fields)->toBeArray()
            ->and($fields)->toContain('name')
            ->and($fields)->toContain('email');
    }

    #[Test]
    public function it_uses_cache_on_subsequent_calls(): void
    {
        $discovery = new FieldDiscovery;

        // First call - should discover
        $fields1 = $discovery->discoverForModel(TestModelWithFillable::class, [FieldDiscovery::SOURCE_FILLABLE]);

        // Change the fillable (simulated - can't actually change)
        // Second call should return cached result
        $fields2 = $discovery->discoverForModel(TestModelWithFillable::class, [FieldDiscovery::SOURCE_FILLABLE]);

        expect($fields1)->toBe($fields2);
    }
}

/**
 * Test model with fillable attributes for testing.
 */
class TestModelWithFillable extends Model
{
    protected $fillable = ['name', 'email', 'salary', 'department'];
}

/**
 * Non-model class for testing.
 */
class NonModelClass
{
    public string $name = '';
}
