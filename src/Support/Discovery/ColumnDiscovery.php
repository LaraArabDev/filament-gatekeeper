<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Support\Discovery;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;

/**
 * Class ColumnDiscovery
 *
 * Discovers table columns from various sources for permission management.
 * Supports multiple detection strategies: database schema, model attributes,
 * and Filament resource tables.
 */
class ColumnDiscovery
{
    /**
     * Detection source constants.
     */
    public const SOURCE_DATABASE = 'database';

    public const SOURCE_RESOURCE = 'resource';

    public const SOURCE_CONFIG = 'config';

    /**
     * Get default columns to exclude from detection.
     *
     * Reads from config or falls back to sensible defaults.
     *
     * @return array<string>
     */
    protected function getDefaultExcluded(): array
    {
        return config('gatekeeper.column_discovery.default_excluded', [
            'password',
            'remember_token',
            'two_factor_secret',
            'two_factor_recovery_codes',
        ]);
    }

    /**
     * Discovered columns cache.
     *
     * @var array<string, array<string>>
     */
    protected array $discoveredColumns = [];

    /**
     * Discover columns for a specific model.
     *
     * This method aggregates columns from multiple sources based on configuration.
     * It applies exclusions and returns a unique list of column names.
     *
     * @param  string  $modelClass  The fully qualified model class name
     * @param  array<string>|null  $sources  Detection sources to use (null = use config)
     * @return array<string> List of discovered column names
     *
     * @example
     * ```php
     * $discovery = new ColumnDiscovery();
     * $columns = $discovery->discoverForModel(User::class);
     * // Returns: ['id', 'name', 'email', 'created_at']
     * ```
     */
    public function discoverForModel(string $modelClass, ?array $sources = null): array
    {
        $modelName = class_basename($modelClass);

        if (isset($this->discoveredColumns[$modelName])) {
            return $this->discoveredColumns[$modelName];
        }

        $sources ??= $this->getConfiguredSources();
        $columns = [];

        foreach ($sources as $source) {
            $columns = array_merge($columns, match ($source) {
                self::SOURCE_DATABASE => $this->discoverFromDatabase($modelClass),
                self::SOURCE_RESOURCE => $this->discoverFromResource($modelClass),
                self::SOURCE_CONFIG => $this->discoverFromConfig($modelName),
                default => [],
            });
        }

        $columns = array_unique($columns);
        $columns = $this->applyExclusions($modelName, $columns);
        $this->discoveredColumns[$modelName] = array_values($columns);

        return $this->discoveredColumns[$modelName];
    }

    /**
     * Discover columns from database table schema.
     *
     * This method queries the database schema to get all column names
     * for the model's table.
     *
     * @param  string  $modelClass  The fully qualified model class name
     * @return array<string> List of database column names
     *
     * @example
     * ```php
     * $columns = $discovery->discoverFromDatabase(User::class);
     * // Returns: ['id', 'name', 'email', 'password', 'created_at', ...]
     * ```
     */
    public function discoverFromDatabase(string $modelClass): array
    {
        if (! class_exists($modelClass)) {
            return [];
        }

        try {
            $reflection = new ReflectionClass($modelClass);

            if (! $reflection->isSubclassOf(Model::class)) {
                return [];
            }

            $model = $reflection->newInstanceWithoutConstructor();
            $table = $model->getTable();

            if (! Schema::hasTable($table)) {
                return [];
            }

            return Schema::getColumnListing($table);
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Discover columns from Filament resource table schema.
     *
     * This method analyzes the table() method of a Filament resource
     * to extract column names defined in the schema.
     *
     * @param  string  $modelClass  The fully qualified model class name
     * @return array<string> List of column names from the resource table
     *
     * @example
     * ```php
     * $columns = $discovery->discoverFromResource(User::class);
     * // Returns columns defined in UserResource::table()
     * ```
     */
    public function discoverFromResource(string $modelClass): array
    {
        $resourceClass = $this->findResourceForModel($modelClass);

        if (! $resourceClass || ! class_exists($resourceClass)) {
            return [];
        }

        try {
            if (! method_exists($resourceClass, 'table')) {
                return [];
            }

            $reflection = new ReflectionMethod($resourceClass, 'table');
            $fileName = $reflection->getFileName();

            if (! $fileName || ! file_exists($fileName)) {
                return [];
            }

            $contents = file_get_contents($fileName);

            return $this->parseColumnsFromCode($contents);
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Discover columns from configuration file.
     *
     * This method retrieves manually configured columns from the
     * gatekeeper config file.
     *
     * @param  string  $modelName  The model name (e.g., 'User')
     * @return array<string> List of configured column names
     *
     * @example
     * ```php
     * // With config: 'column_permissions' => ['User' => ['email', 'salary']]
     * $columns = $discovery->discoverFromConfig('User');
     * // Returns: ['email', 'salary']
     * ```
     */
    public function discoverFromConfig(string $modelName): array
    {
        $globalColumns = config('gatekeeper.column_permissions.*', []);
        $modelColumns = config("gatekeeper.column_permissions.{$modelName}", []);

        return array_merge($globalColumns, $modelColumns);
    }

    /**
     * Find the Filament resource class for a given model.
     *
     * Searches through configured resource paths to find the corresponding
     * resource class for the given model.
     *
     * @param  string  $modelClass  The fully qualified model class name
     * @return string|null The resource class name or null if not found
     */
    protected function findResourceForModel(string $modelClass): ?string
    {
        $modelName = class_basename($modelClass);
        $resourcePaths = config('gatekeeper.discovery.resources', ['app/Filament/Resources']);

        foreach ($resourcePaths as $path) {
            $resourceName = "{$modelName}Resource";
            $possibleClasses = [
                "App\\Filament\\Resources\\{$resourceName}",
                "App\\Filament\\Admin\\Resources\\{$resourceName}",
            ];

            foreach ($possibleClasses as $class) {
                if (class_exists($class)) {
                    return $class;
                }
            }
        }

        return null;
    }

    /**
     * Parse column names from PHP code.
     *
     * Extracts column names from TextColumn::make(), BadgeColumn::make(), etc.
     *
     * @param  string  $code  The PHP source code to parse
     * @return array<string> List of parsed column names
     */
    protected function parseColumnsFromCode(string $code): array
    {
        $columns = [];

        preg_match_all(
            '/(?:TextColumn|BadgeColumn|BooleanColumn|IconColumn|ImageColumn|ColorColumn|SelectColumn|ToggleColumn|TextInputColumn|CheckboxColumn|SpatieTagsColumn)::make\([\'"]([a-zA-Z_][a-zA-Z0-9_.]*)[\'"]/',
            $code,
            $matches
        );

        foreach ($matches[1] as $match) {
            $parts = explode('.', $match);
            $columns[] = $parts[0];
        }

        return array_unique($columns);
    }

    /**
     * Apply exclusions to the discovered columns.
     *
     * Removes columns that are in the exclusion list (both default and configured).
     *
     * @param  string  $modelName  The model name
     * @param  array<string>  $columns  List of columns to filter
     * @return array<string> Filtered list of columns
     */
    protected function applyExclusions(string $modelName, array $columns): array
    {
        $excluded = $this->getExcludedColumns($modelName);

        return array_filter($columns, fn (string $column): bool => ! in_array($column, $excluded));
    }

    /**
     * Get the list of excluded columns for a model.
     *
     * Combines default exclusions with model-specific and global exclusions
     * from the configuration.
     *
     * @param  string  $modelName  The model name
     * @return array<string> List of excluded column names
     */
    public function getExcludedColumns(string $modelName): array
    {
        $configExcluded = config('gatekeeper.column_discovery.excluded', []);
        $globalExcluded = $configExcluded['*'] ?? [];
        $modelExcluded = $configExcluded[$modelName] ?? [];

        return array_unique(array_merge(
            $this->getDefaultExcluded(),
            $globalExcluded,
            $modelExcluded
        ));
    }

    /**
     * Get the configured detection sources.
     *
     * Returns the list of sources to use for column detection
     * based on the configuration.
     *
     * @return array<string> List of source identifiers
     */
    protected function getConfiguredSources(): array
    {
        return config('gatekeeper.column_discovery.sources', [
            self::SOURCE_CONFIG,
            self::SOURCE_DATABASE,
        ]);
    }

    /**
     * Clear the discovery cache.
     *
     * Useful when configuration or model structure changes.
     *
     * @param  string|null  $modelName  Specific model to clear, or null for all
     */
    public function clearCache(?string $modelName = null): void
    {
        if ($modelName) {
            unset($this->discoveredColumns[$modelName]);
        } else {
            $this->discoveredColumns = [];
        }
    }

    /**
     * Check if a column is sensitive and should be protected.
     *
     * Sensitive columns are typically those containing passwords,
     * tokens, or personal identifiable information.
     *
     * @param  string  $columnName  The column name to check
     * @return bool True if the column is considered sensitive
     */
    public function isSensitiveColumn(string $columnName): bool
    {
        $sensitivePatterns = config('gatekeeper.column_discovery.sensitive_patterns', [
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

        $columnLower = strtolower($columnName);

        foreach ($sensitivePatterns as $pattern) {
            if (Str::contains($columnLower, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all available detection sources.
     *
     * @return array<string, string> Source identifier => Source label
     */
    public static function getAvailableSources(): array
    {
        return [
            self::SOURCE_CONFIG => 'Configuration File',
            self::SOURCE_DATABASE => 'Database Schema',
            self::SOURCE_RESOURCE => 'Filament Resource',
        ];
    }
}
