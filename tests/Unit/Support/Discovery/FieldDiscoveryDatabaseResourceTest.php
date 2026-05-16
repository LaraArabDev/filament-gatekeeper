<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Unit\Support\Discovery;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LaraArabDev\FilamentGatekeeper\Support\Discovery\FieldDiscovery;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;
use LaraArabDev\FilamentGatekeeper\Tests\TestUser;

/**
 * Tests for FieldDiscovery::discoverFromDatabase(), discoverFromResource(),
 * parseFieldsFromCode(), and findResourceForModel() — previously uncovered.
 */
class FieldDiscoveryDatabaseResourceTest extends TestCase
{
    use RefreshDatabase;

    protected FieldDiscovery $discovery;

    protected function setUp(): void
    {
        parent::setUp();
        $this->discovery = new FieldDiscovery();
    }

    // ── discoverFromDatabase ──────────────────────────────────────────────

    /** @test */
    public function it_discovers_columns_from_database_for_real_model(): void
    {
        // TestUser maps to the `users` table which is created by migrations
        $columns = $this->discovery->discoverFromDatabase(TestUser::class);

        $this->assertIsArray($columns);
        // users table should have at least id, name, email, password columns
        $this->assertNotEmpty($columns);
        $this->assertContains('email', $columns);
        $this->assertContains('name', $columns);
    }

    /** @test */
    public function it_returns_empty_array_for_non_existent_class_in_database_discovery(): void
    {
        $columns = $this->discovery->discoverFromDatabase('App\\Models\\CompletelyNonExistentModel');

        $this->assertIsArray($columns);
        $this->assertEmpty($columns);
    }

    /** @test */
    public function it_returns_empty_array_for_non_model_class_in_database_discovery(): void
    {
        $columns = $this->discovery->discoverFromDatabase(\stdClass::class);

        $this->assertIsArray($columns);
        $this->assertEmpty($columns);
    }

    /** @test */
    public function it_returns_empty_array_for_model_with_nonexistent_table(): void
    {
        // Create a model class pointing to a nonexistent table
        $model = new class extends Model {
            protected $table = 'nonexistent_table_xyz_12345';
        };

        $columns = $this->discovery->discoverFromDatabase(get_class($model));

        $this->assertIsArray($columns);
        $this->assertEmpty($columns);
    }

    // ── discoverFromResource ──────────────────────────────────────────────

    /** @test */
    public function it_returns_empty_for_model_with_no_corresponding_resource(): void
    {
        // TestUser has no UserResource in App\Filament\Resources
        $fields = $this->discovery->discoverFromResource(TestUser::class);

        $this->assertIsArray($fields);
        $this->assertEmpty($fields);
    }

    /** @test */
    public function it_returns_empty_for_non_existent_class_in_resource_discovery(): void
    {
        $fields = $this->discovery->discoverFromResource('App\\Models\\DoesNotExist');

        $this->assertIsArray($fields);
        $this->assertEmpty($fields);
    }

    // ── discoverForModel with database source ─────────────────────────────

    /** @test */
    public function it_discovers_for_model_using_database_source(): void
    {
        // Using TestUser which has a real users table
        $fields = $this->discovery->discoverForModel(TestUser::class, [FieldDiscovery::SOURCE_DATABASE]);

        $this->assertIsArray($fields);
        $this->assertNotEmpty($fields);
        $this->assertContains('email', $fields);
    }

    /** @test */
    public function it_discovers_for_model_using_resource_source(): void
    {
        // No UserResource exists, so should return empty
        $fields = $this->discovery->discoverForModel(TestUser::class, [FieldDiscovery::SOURCE_RESOURCE]);

        $this->assertIsArray($fields);
        // Empty because no resource class found
        $this->assertEmpty($fields);
    }

    /** @test */
    public function it_discovers_for_model_combining_database_and_config_sources(): void
    {
        config()->set('gatekeeper.field_permissions', [
            'TestUser' => ['salary', 'bonus'],
        ]);

        $discovery = new FieldDiscovery();
        $fields = $discovery->discoverForModel(
            TestUser::class,
            [FieldDiscovery::SOURCE_DATABASE, FieldDiscovery::SOURCE_CONFIG]
        );

        $this->assertIsArray($fields);
        $this->assertNotEmpty($fields);
        // Database columns should be included
        $this->assertContains('email', $fields);
    }

    /** @test */
    public function it_deduplicates_fields_when_using_multiple_sources(): void
    {
        config()->set('gatekeeper.field_permissions', [
            'TestUser' => ['email', 'name'],
        ]);

        $discovery = new FieldDiscovery();
        $fields = $discovery->discoverForModel(
            TestUser::class,
            [FieldDiscovery::SOURCE_DATABASE, FieldDiscovery::SOURCE_CONFIG]
        );

        // email and name come from both sources but should be unique
        $uniqueFields = array_unique($fields);
        $this->assertSame(count($uniqueFields), count($fields));
    }

    /** @test */
    public function it_returns_empty_for_default_source_with_unknown_class(): void
    {
        $fields = $this->discovery->discoverFromDatabase('NonExistentClassXyz');

        $this->assertIsArray($fields);
        $this->assertEmpty($fields);
    }

    // ── discoverForModel cache behavior ───────────────────────────────────

    /** @test */
    public function it_returns_cached_result_on_second_call_with_database_source(): void
    {
        $first = $this->discovery->discoverForModel(TestUser::class, [FieldDiscovery::SOURCE_DATABASE]);
        $second = $this->discovery->discoverForModel(TestUser::class, [FieldDiscovery::SOURCE_DATABASE]);

        $this->assertSame($first, $second);
    }

    /** @test */
    public function it_clears_cached_database_result_on_clearCache(): void
    {
        $this->discovery->discoverForModel(TestUser::class, [FieldDiscovery::SOURCE_DATABASE]);
        $this->discovery->clearCache(class_basename(TestUser::class));

        // Should re-discover without error
        $fields = $this->discovery->discoverForModel(TestUser::class, [FieldDiscovery::SOURCE_DATABASE]);

        $this->assertIsArray($fields);
        $this->assertNotEmpty($fields);
    }
}
