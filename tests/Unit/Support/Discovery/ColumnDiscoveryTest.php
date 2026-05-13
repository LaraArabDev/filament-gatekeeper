<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Unit\Support\Discovery;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use LaraArabDev\FilamentGatekeeper\Support\Discovery\ColumnDiscovery;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;

/**
 * Tests for the ColumnDiscovery class.
 */
class ColumnDiscoveryTest extends TestCase
{
    protected ColumnDiscovery $discovery;

    protected function setUp(): void
    {
        parent::setUp();

        $this->discovery = new ColumnDiscovery();
    }

    /** @test */
    public function it_can_be_instantiated(): void
    {
        expect($this->discovery)->toBeInstanceOf(ColumnDiscovery::class);
    }

    /** @test */
    public function it_returns_available_sources(): void
    {
        $sources = ColumnDiscovery::getAvailableSources();

        expect($sources)->toBeArray()
            ->and($sources)->toHaveKey(ColumnDiscovery::SOURCE_CONFIG)
            ->and($sources)->toHaveKey(ColumnDiscovery::SOURCE_DATABASE)
            ->and($sources)->toHaveKey(ColumnDiscovery::SOURCE_RESOURCE);
    }

    /** @test */
    public function it_discovers_columns_from_config(): void
    {
        Config::set('gatekeeper.column_permissions', [
            'TestModel' => ['column1', 'column2', 'column3'],
        ]);

        $columns = $this->discovery->discoverFromConfig('TestModel');

        expect($columns)->toBeArray()
            ->and($columns)->toContain('column1')
            ->and($columns)->toContain('column2')
            ->and($columns)->toContain('column3');
    }

    /** @test */
    public function it_merges_global_columns_from_config(): void
    {
        Config::set('gatekeeper.column_permissions', [
            '*' => ['global_column'],
            'TestModel' => ['model_column'],
        ]);

        $columns = $this->discovery->discoverFromConfig('TestModel');

        expect($columns)->toBeArray()
            ->and($columns)->toContain('global_column')
            ->and($columns)->toContain('model_column');
    }

    /** @test */
    public function it_returns_excluded_columns(): void
    {
        Config::set('gatekeeper.column_discovery.default_excluded', ['password', 'remember_token']);
        Config::set('gatekeeper.column_discovery.excluded', [
            '*' => ['global_excluded'],
            'TestModel' => ['model_excluded'],
        ]);

        $excluded = $this->discovery->getExcludedColumns('TestModel');

        expect($excluded)->toBeArray()
            ->and($excluded)->toContain('global_excluded')
            ->and($excluded)->toContain('model_excluded')
            // Default exclusions from config should also be present
            ->and($excluded)->toContain('password')
            ->and($excluded)->toContain('remember_token');
    }

    /** @test */
    public function it_identifies_sensitive_columns(): void
    {
        config()->set('gatekeeper.column_discovery.sensitive_patterns', [
            'password',
            'secret',
            'token',
            'ssn',
            'social_security',
            'credit_card',
            'card_number',
            'cvv',
            'pin',
            'api_key',
            'private_key',
            'salary',
            'income',
        ]);

        expect($this->discovery->isSensitiveColumn('password'))->toBeTrue()
            ->and($this->discovery->isSensitiveColumn('user_password'))->toBeTrue()
            ->and($this->discovery->isSensitiveColumn('salary'))->toBeTrue()
            ->and($this->discovery->isSensitiveColumn('income_amount'))->toBeTrue()
            ->and($this->discovery->isSensitiveColumn('api_key'))->toBeTrue()
            ->and($this->discovery->isSensitiveColumn('name'))->toBeFalse()
            ->and($this->discovery->isSensitiveColumn('email'))->toBeFalse();
    }

    /** @test */
    public function it_clears_cache(): void
    {
        Config::set('gatekeeper.column_permissions', [
            'TestModel' => ['col1', 'col2'],
        ]);

        // Discover columns to populate cache
        $this->discovery->discoverForModel('App\\Models\\TestModel', [ColumnDiscovery::SOURCE_CONFIG]);

        // Clear cache
        $this->discovery->clearCache('TestModel');

        // Should still work
        $columns = $this->discovery->discoverForModel('App\\Models\\TestModel', [ColumnDiscovery::SOURCE_CONFIG]);

        expect($columns)->toBeArray();
    }

    /** @test */
    public function it_clears_all_cache(): void
    {
        Config::set('gatekeeper.column_permissions', [
            'TestModel' => ['col1', 'col2'],
        ]);

        // Discover columns to populate cache
        $this->discovery->discoverForModel('App\\Models\\TestModel', [ColumnDiscovery::SOURCE_CONFIG]);

        // Clear all cache
        $this->discovery->clearCache();

        // Should still work
        $columns = $this->discovery->discoverForModel('App\\Models\\TestModel', [ColumnDiscovery::SOURCE_CONFIG]);

        expect($columns)->toBeArray();
    }

    /** @test */
    public function it_applies_exclusions_when_discovering(): void
    {
        Config::set('gatekeeper.column_permissions', [
            'User' => ['name', 'email', 'salary', 'department'],
        ]);
        Config::set('gatekeeper.column_discovery.excluded', [
            '*' => ['email'],
        ]);

        // Create a new instance to get fresh config
        $discovery = new ColumnDiscovery();
        $discovery->clearCache();

        $columns = $discovery->discoverForModel('App\\Models\\User', [ColumnDiscovery::SOURCE_CONFIG]);

        expect($columns)->not->toContain('email')
            ->and($columns)->toContain('name')
            ->and($columns)->toContain('salary');
    }

    /** @test */
    public function it_discovers_for_model_with_config_source(): void
    {
        Config::set('gatekeeper.column_permissions', [
            'User' => ['id', 'name', 'created_at'],
        ]);
        Config::set('gatekeeper.column_discovery.sources', [ColumnDiscovery::SOURCE_CONFIG]);

        $discovery = new ColumnDiscovery();
        $discovery->clearCache();

        $columns = $discovery->discoverForModel('App\\Models\\User');

        expect($columns)->toBeArray()
            ->and($columns)->toContain('id')
            ->and($columns)->toContain('name')
            ->and($columns)->toContain('created_at');
    }

    /** @test */
    public function it_uses_cache_on_subsequent_calls(): void
    {
        Config::set('gatekeeper.column_permissions', [
            'CacheTestModel' => ['col1', 'col2'],
        ]);

        $discovery = new ColumnDiscovery();
        $discovery->clearCache();

        // First call - should discover
        $columns1 = $discovery->discoverForModel('App\\Models\\CacheTestModel', [ColumnDiscovery::SOURCE_CONFIG]);

        // Second call should return cached result
        $columns2 = $discovery->discoverForModel('App\\Models\\CacheTestModel', [ColumnDiscovery::SOURCE_CONFIG]);

        expect($columns1)->toBe($columns2);
    }

    /** @test */
    public function it_returns_empty_array_for_non_existent_class_from_database(): void
    {
        $columns = $this->discovery->discoverFromDatabase('NonExistentClass');

        expect($columns)->toBeArray()
            ->and($columns)->toBeEmpty();
    }

    /** @test */
    public function it_returns_empty_array_for_non_model_class_from_database(): void
    {
        $columns = $this->discovery->discoverFromDatabase(ColumnTestNonModelClass::class);

        expect($columns)->toBeArray()
            ->and($columns)->toBeEmpty();
    }

    /** @test */
    public function it_returns_empty_array_for_non_existent_class_from_resource(): void
    {
        $columns = $this->discovery->discoverFromResource('NonExistentClass');

        expect($columns)->toBeArray()
            ->and($columns)->toBeEmpty();
    }
}

/**
 * Non-model class for testing.
 */
class ColumnTestNonModelClass
{
    public string $name = '';
}
