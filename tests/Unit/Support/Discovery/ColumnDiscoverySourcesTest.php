<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Unit\Support\Discovery;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use LaraArabDev\FilamentGatekeeper\Support\Discovery\ColumnDiscovery;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;
use LaraArabDev\FilamentGatekeeper\Tests\TestUser;

/**
 * Branch coverage tests for ColumnDiscovery:
 *  - discoverFromResource() with no resource class found
 *  - discoverFromResource() with a resource class that has no table() method
 *  - isSensitiveColumn() true/false branches
 *  - clearCache() specific model vs all cache
 *  - discoverForModel() cache hit branch (second call returns cached result)
 *  - discoverFromDatabase() with a real Eloquent model
 *  - discoverFromDatabase() with a non-Model class
 */
class ColumnDiscoverySourcesTest extends TestCase
{
    use RefreshDatabase;

    private ColumnDiscovery $discovery;

    protected function setUp(): void
    {
        parent::setUp();

        $this->discovery = new ColumnDiscovery();
    }

    // ---------------------------------------------------------------------------
    // discoverFromResource()
    // ---------------------------------------------------------------------------

    /** @test */
    public function discover_from_resource_returns_empty_when_no_resource_class_found(): void
    {
        // No resource classes exist for a made-up model
        $columns = $this->discovery->discoverFromResource('App\\Models\\NonExistentModel');

        $this->assertIsArray($columns);
        $this->assertEmpty($columns);
    }

    /** @test */
    public function discover_from_resource_returns_empty_for_non_existent_class(): void
    {
        $columns = $this->discovery->discoverFromResource('DefinitelyDoesNotExist');

        $this->assertIsArray($columns);
        $this->assertEmpty($columns);
    }

    // ---------------------------------------------------------------------------
    // discoverFromDatabase()
    // ---------------------------------------------------------------------------

    /** @test */
    public function discover_from_database_returns_columns_for_real_model(): void
    {
        // TestUser extends Authenticatable (which extends Model) and uses the 'users' table
        // that is created in TestCase::getEnvironmentSetUp()
        $columns = $this->discovery->discoverFromDatabase(TestUser::class);

        $this->assertIsArray($columns);
        // The 'users' table has at least these columns
        $this->assertContains('id', $columns);
        $this->assertContains('name', $columns);
        $this->assertContains('email', $columns);
    }

    /** @test */
    public function discover_from_database_returns_empty_for_non_existent_class(): void
    {
        $columns = $this->discovery->discoverFromDatabase('App\\Models\\CompletlyNonExistent');

        $this->assertIsArray($columns);
        $this->assertEmpty($columns);
    }

    /** @test */
    public function discover_from_database_returns_empty_for_non_model_class(): void
    {
        // stdClass does not extend Model
        $columns = $this->discovery->discoverFromDatabase(\stdClass::class);

        $this->assertIsArray($columns);
        $this->assertEmpty($columns);
    }

    // ---------------------------------------------------------------------------
    // isSensitiveColumn()
    // ---------------------------------------------------------------------------

    /** @test */
    public function is_sensitive_column_returns_true_for_password(): void
    {
        $this->assertTrue($this->discovery->isSensitiveColumn('password'));
    }

    /** @test */
    public function is_sensitive_column_returns_true_for_partial_match(): void
    {
        $this->assertTrue($this->discovery->isSensitiveColumn('user_password'));
        $this->assertTrue($this->discovery->isSensitiveColumn('reset_token'));
        $this->assertTrue($this->discovery->isSensitiveColumn('annual_salary'));
    }

    /** @test */
    public function is_sensitive_column_returns_false_for_normal_column(): void
    {
        $this->assertFalse($this->discovery->isSensitiveColumn('name'));
        $this->assertFalse($this->discovery->isSensitiveColumn('email'));
        $this->assertFalse($this->discovery->isSensitiveColumn('created_at'));
    }

    /** @test */
    public function is_sensitive_column_is_case_insensitive(): void
    {
        $this->assertTrue($this->discovery->isSensitiveColumn('PASSWORD'));
        $this->assertTrue($this->discovery->isSensitiveColumn('User_Secret'));
        $this->assertFalse($this->discovery->isSensitiveColumn('EMAIL'));
    }

    // ---------------------------------------------------------------------------
    // clearCache()
    // ---------------------------------------------------------------------------

    /** @test */
    public function clear_cache_for_specific_model_removes_only_that_model(): void
    {
        Config::set('gatekeeper.column_permissions', [
            'ModelA' => ['col_a'],
            'ModelB' => ['col_b'],
        ]);

        $discovery = new ColumnDiscovery();

        // Populate cache for two models
        $discovery->discoverForModel('App\\Models\\ModelA', [ColumnDiscovery::SOURCE_CONFIG]);
        $discovery->discoverForModel('App\\Models\\ModelB', [ColumnDiscovery::SOURCE_CONFIG]);

        // Clear only ModelA
        $discovery->clearCache('ModelA');

        // ModelA is re-discovered from scratch (still returns same result since config unchanged)
        $colsA = $discovery->discoverForModel('App\\Models\\ModelA', [ColumnDiscovery::SOURCE_CONFIG]);
        $colsB = $discovery->discoverForModel('App\\Models\\ModelB', [ColumnDiscovery::SOURCE_CONFIG]);

        $this->assertContains('col_a', $colsA);
        $this->assertContains('col_b', $colsB);
    }

    /** @test */
    public function clear_cache_for_null_removes_all_cache(): void
    {
        Config::set('gatekeeper.column_permissions', [
            'ModelX' => ['col_x'],
        ]);

        $discovery = new ColumnDiscovery();

        $discovery->discoverForModel('App\\Models\\ModelX', [ColumnDiscovery::SOURCE_CONFIG]);

        // Clear all
        $discovery->clearCache(null);

        // Should still work after cache clear
        $cols = $discovery->discoverForModel('App\\Models\\ModelX', [ColumnDiscovery::SOURCE_CONFIG]);

        $this->assertIsArray($cols);
        $this->assertContains('col_x', $cols);
    }

    // ---------------------------------------------------------------------------
    // discoverForModel() – cache hit
    // ---------------------------------------------------------------------------

    /** @test */
    public function discover_for_model_uses_cached_result_on_second_call(): void
    {
        Config::set('gatekeeper.column_permissions', [
            'CachedModel' => ['cached_col'],
        ]);

        $discovery = new ColumnDiscovery();
        $discovery->clearCache();

        // First call – discovers and populates cache
        $first = $discovery->discoverForModel('App\\Models\\CachedModel', [ColumnDiscovery::SOURCE_CONFIG]);

        // Change config so a fresh discovery would return different results
        Config::set('gatekeeper.column_permissions', [
            'CachedModel' => ['different_col'],
        ]);

        // Second call – should return the cached result (not the new config)
        $second = $discovery->discoverForModel('App\\Models\\CachedModel', [ColumnDiscovery::SOURCE_CONFIG]);

        $this->assertSame($first, $second);
        $this->assertContains('cached_col', $second);
        $this->assertNotContains('different_col', $second);
    }

    // ---------------------------------------------------------------------------
    // discoverFromDatabase() with table that does not exist
    // ---------------------------------------------------------------------------

    /** @test */
    public function discover_from_database_returns_empty_when_table_does_not_exist(): void
    {
        // Create an anonymous subclass of Model with a non-existent table
        $tempClass = new class extends \Illuminate\Database\Eloquent\Model {
            protected $table = 'non_existent_table_xyz_' . PHP_INT_MAX;
        };

        $columns = $this->discovery->discoverFromDatabase(get_class($tempClass));

        $this->assertIsArray($columns);
        $this->assertEmpty($columns);
    }
}
