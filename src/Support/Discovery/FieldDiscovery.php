<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Support\Discovery;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;

/**
 * Class FieldDiscovery
 *
 * Discovers fields from various sources for permission management.
 * Supports multiple detection strategies: model fillable, database schema,
 * and Filament resource forms.
 *
 * @package LaraArabDev\FilamentGatekeeper\Support\Discovery
 */
class FieldDiscovery
{
    /**
     * Detection source constants.
     */
    public const SOURCE_FILLABLE = 'fillable';
    public const SOURCE_DATABASE = 'database';
    public const SOURCE_RESOURCE = 'resource';
    public const SOURCE_CONFIG = 'config';

    /**
     * Get default fields to exclude from detection.
     *
     * Reads from config or falls back to sensible defaults.
     *
     * @return array<string>
     */
    protected function getDefaultExcluded(): array
    {
        return config('gatekeeper.field_discovery.default_excluded', [
            'id',
            'uuid',
            'created_at',
            'updated_at',
            'deleted_at',
            'remember_token',
            'email_verified_at',
            'two_factor_secret',
            'two_factor_recovery_codes',
            'two_factor_confirmed_at',
        ]);
    }

    /**
     * Discovered fields cache.
     *
     * @var array<string, array<string>>
     */
    protected array $discoveredFields = [];

    /**
     * Discover fields for a specific model.
     *
     * This method aggregates fields from multiple sources based on configuration.
     * It applies exclusions and returns a unique list of field names.
     *
     * @param string $modelClass The fully qualified model class name
     * @param array<string>|null $sources Detection sources to use (null = use config)
     * @return array<string> List of discovered field names
     *
     * @example
     * ```php
     * $discovery = new FieldDiscovery();
     * $fields = $discovery->discoverForModel(User::class);
     * // Returns: ['name', 'email', 'phone', 'salary']
     * ```
     */
    public function discoverForModel(string $modelClass, ?array $sources = null): array
    {
        $modelName = class_basename($modelClass);

        if (isset($this->discoveredFields[$modelName])) {
            return $this->discoveredFields[$modelName];
        }

        $sources = $sources ?? $this->getConfiguredSources();
        $fields = [];

        foreach ($sources as $source) {
            $fields = array_merge($fields, match ($source) {
                self::SOURCE_FILLABLE => $this->discoverFromFillable($modelClass),
                self::SOURCE_DATABASE => $this->discoverFromDatabase($modelClass),
                self::SOURCE_RESOURCE => $this->discoverFromResource($modelClass),
                self::SOURCE_CONFIG => $this->discoverFromConfig($modelName),
                default => [],
            });
        }

        $fields = array_unique($fields);
        $fields = $this->applyExclusions($modelName, $fields);
        $this->discoveredFields[$modelName] = array_values($fields);

        return $this->discoveredFields[$modelName];
    }

    /**
     * Discover fields from model's $fillable property.
     *
     * This method uses reflection to access the model's fillable attributes
     * without instantiating the model.
     *
     * @param string $modelClass The fully qualified model class name
     * @return array<string> List of fillable field names
     *
     * @example
     * ```php
     * // For a model with: protected $fillable = ['name', 'email'];
     * $fields = $discovery->discoverFromFillable(User::class);
     * // Returns: ['name', 'email']
     * ```
     */
    public function discoverFromFillable(string $modelClass): array
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

            return $model->getFillable();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Discover fields from database table schema.
     *
     * This method queries the database schema to get all column names
     * for the model's table.
     *
     * @param string $modelClass The fully qualified model class name
     * @return array<string> List of database column names
     *
     * @example
     * ```php
     * $fields = $discovery->discoverFromDatabase(User::class);
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
     * Discover fields from Filament resource form schema.
     *
     * This method analyzes the form() method of a Filament resource
     * to extract field names defined in the schema.
     *
     * @param string $modelClass The fully qualified model class name
     * @return array<string> List of field names from the resource form
     *
     * @example
     * ```php
     * $fields = $discovery->discoverFromResource(User::class);
     * // Returns fields defined in UserResource::form()
     * ```
     */
    public function discoverFromResource(string $modelClass): array
    {
        $resourceClass = $this->findResourceForModel($modelClass);

        if (! $resourceClass || ! class_exists($resourceClass)) {
            return [];
        }

        try {
            if (! method_exists($resourceClass, 'form')) {
                return [];
            }

            $reflection = new ReflectionMethod($resourceClass, 'form');
            $fileName = $reflection->getFileName();

            if (! $fileName || ! file_exists($fileName)) {
                return [];
            }

            $contents = file_get_contents($fileName);

            return $this->parseFieldsFromCode($contents);
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Discover fields from configuration file.
     *
     * This method retrieves manually configured fields from the
     * gatekeeper config file.
     *
     * @param string $modelName The model name (e.g., 'User')
     * @return array<string> List of configured field names
     *
     * @example
     * ```php
     * // With config: 'field_permissions' => ['User' => ['email', 'salary']]
     * $fields = $discovery->discoverFromConfig('User');
     * // Returns: ['email', 'salary']
     * ```
     */
    public function discoverFromConfig(string $modelName): array
    {
        $globalFields = config('gatekeeper.field_permissions.*', []);
        $modelFields = config("gatekeeper.field_permissions.{$modelName}", []);

        return array_merge($globalFields, $modelFields);
    }

    /**
     * Find the Filament resource class for a given model.
     *
     * Searches through configured resource paths to find the corresponding
     * resource class for the given model.
     *
     * @param string $modelClass The fully qualified model class name
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
     * Parse field names from PHP code.
     *
     * Extracts field names from TextInput::make(), Select::make(), etc.
     *
     * @param string $code The PHP source code to parse
     * @return array<string> List of parsed field names
     */
    protected function parseFieldsFromCode(string $code): array
    {
        $fields = [];

        preg_match_all(
            '/(?:TextInput|TextArea|Select|DatePicker|DateTimePicker|TimePicker|Toggle|Checkbox|Radio|FileUpload|RichEditor|MarkdownEditor|ColorPicker|KeyValue|Hidden|Repeater|Builder|TagsInput)::make\([\'"]([a-zA-Z_][a-zA-Z0-9_]*)[\'"]/',
            $code,
            $matches
        );

        if (! empty($matches[1])) {
            $fields = array_merge($fields, $matches[1]);
        }

        return array_unique($fields);
    }

    /**
     * Apply exclusions to the discovered fields.
     *
     * Removes fields that are in the exclusion list (both default and configured).
     *
     * @param string $modelName The model name
     * @param array<string> $fields List of fields to filter
     * @return array<string> Filtered list of fields
     */
    protected function applyExclusions(string $modelName, array $fields): array
    {
        $excluded = $this->getExcludedFields($modelName);

        return array_filter($fields, fn($field) => ! in_array($field, $excluded));
    }

    /**
     * Get the list of excluded fields for a model.
     *
     * Combines default exclusions with model-specific and global exclusions
     * from the configuration.
     *
     * @param string $modelName The model name
     * @return array<string> List of excluded field names
     */
    public function getExcludedFields(string $modelName): array
    {
        $configExcluded = config('gatekeeper.field_discovery.excluded', []);
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
     * Returns the list of sources to use for field detection
     * based on the configuration.
     *
     * @return array<string> List of source identifiers
     */
    protected function getConfiguredSources(): array
    {
        return config('gatekeeper.field_discovery.sources', [
            self::SOURCE_CONFIG,
            self::SOURCE_FILLABLE,
        ]);
    }

    /**
     * Clear the discovery cache.
     *
     * Useful when configuration or model structure changes.
     *
     * @param string|null $modelName Specific model to clear, or null for all
     * @return void
     */
    public function clearCache(?string $modelName = null): void
    {
        if ($modelName) {
            unset($this->discoveredFields[$modelName]);
        } else {
            $this->discoveredFields = [];
        }
    }

    /**
     * Check if a field is sensitive and should be protected.
     *
     * Sensitive fields are typically those containing passwords,
     * tokens, or personal identifiable information.
     *
     * @param string $fieldName The field name to check
     * @return bool True if the field is considered sensitive
     */
    public function isSensitiveField(string $fieldName): bool
    {
        $sensitivePatterns = config('gatekeeper.field_discovery.sensitive_patterns', [
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
        ]);

        $fieldLower = strtolower($fieldName);

        foreach ($sensitivePatterns as $pattern) {
            if (Str::contains($fieldLower, $pattern)) {
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
            self::SOURCE_FILLABLE => 'Model Fillable',
            self::SOURCE_DATABASE => 'Database Schema',
            self::SOURCE_RESOURCE => 'Filament Resource',
        ];
    }
}
