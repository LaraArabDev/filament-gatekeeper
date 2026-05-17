<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Support\Discovery;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use ReflectionClass;

/**
 * Handles discovery of Eloquent models within the application.
 *
 * This class scans configured model paths to discover all Eloquent models,
 * supports module-based discovery for HMVC applications, and provides
 * utilities for model name extraction and validation.
 */
class ModelDiscovery
{
    /**
     * Discover all Eloquent models.
     *
     * Scans configured model paths and optionally module paths to discover
     * all Eloquent model classes. Filters out excluded models and returns
     * unique model names.
     *
     * @return array<string> Array of discovered model names (class basenames)
     */
    public function discover(): array
    {
        $models = [];
        $paths = config('gatekeeper.discovery.models', ['app/Models']);
        $excludedModels = config('gatekeeper.excluded_models', []);

        foreach ($paths as $pathPattern) {
            $foundModels = $this->scanPath($pathPattern);
            $models = array_merge($models, $foundModels);
        }

        if (config('gatekeeper.modules.enabled', false)) {
            $moduleModels = $this->discoverModuleModels();
            $models = array_merge($models, $moduleModels);
        }

        $models = array_filter($models, function (string $model) use ($excludedModels): bool {
            foreach ($excludedModels as $excluded) {
                if (str_contains($model, class_basename($excluded))) {
                    return false;
                }
            }

            return true;
        });

        return array_unique($models);
    }

    /**
     * Scan a path pattern for model classes.
     *
     * Handles both glob patterns and direct directory paths.
     *
     * @param  string  $pathPattern  The path pattern to scan (supports glob patterns)
     * @return array<string> Array of discovered model names
     */
    protected function scanPath(string $pathPattern): array
    {
        $results = [];
        $basePath = base_path($pathPattern);

        if (str_contains($pathPattern, '*')) {
            $directories = glob($basePath, GLOB_ONLYDIR) ?: [];
            foreach ($directories as $directory) {
                $results = array_merge($results, $this->scanDirectory($directory, $pathPattern));
            }
        } elseif (is_dir($basePath)) {
            $results = $this->scanDirectory($basePath, $pathPattern);
        }

        return $results;
    }

    /**
     * Scan a directory for model files.
     *
     * Recursively scans a directory for PHP files, extracts class names,
     * validates they are Eloquent models, and returns their basenames.
     *
     * @param  string  $directory  The directory path to scan
     * @param  string  $pathPattern  The original path pattern (for namespace resolution)
     * @return array<string> Array of discovered model names (class basenames)
     */
    protected function scanDirectory(string $directory, string $pathPattern): array
    {
        $results = [];

        if (! is_dir($directory)) {
            return $results;
        }

        $files = File::allFiles($directory);

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $className = $this->getClassFromFile($file->getPathname(), $pathPattern);

            if ($className && $this->isEloquentModel($className)) {
                $modelName = class_basename($className);
                $results[] = $modelName;
            }
        }

        return $results;
    }

    /**
     * Get the fully qualified class name from a file.
     *
     * Extracts namespace and class name from PHP source code using regex.
     *
     * @param  string  $filePath  The full path to the PHP file
     * @param  string  $pathPattern  The path pattern (unused, kept for compatibility)
     * @return string|null The fully qualified class name or null if not found
     */
    protected function getClassFromFile(string $filePath, string $pathPattern): ?string
    {
        $contents = file_get_contents($filePath);

        if (! $contents) {
            return null;
        }

        $namespace = null;
        if (preg_match('/namespace\s+([^;]+);/', $contents, $matches)) {
            $namespace = $matches[1];
        }

        $className = null;
        if (preg_match('/class\s+(\w+)/', $contents, $matches)) {
            $className = $matches[1];
        }

        if (! $className) {
            return null;
        }

        return $namespace ? "{$namespace}\\{$className}" : $className;
    }

    /**
     * Check if a class is an Eloquent model.
     *
     * Validates that the class exists, is concrete (not abstract/interface/trait),
     * and extends Illuminate\Database\Eloquent\Model.
     *
     * @param  string  $className  The fully qualified class name to check
     * @return bool True if the class is a valid Eloquent model, false otherwise
     */
    protected function isEloquentModel(string $className): bool
    {
        if (! class_exists($className)) {
            return false;
        }

        try {
            $reflection = new ReflectionClass($className);

            if ($reflection->isAbstract() || $reflection->isInterface() || $reflection->isTrait()) {
                return false;
            }

            return $reflection->isSubclassOf(Model::class);
        } catch (\ReflectionException) {
            return false;
        }
    }

    /**
     * Discover models from modules (HMVC support).
     *
     * Scans all modules in the configured modules path for model classes.
     * Only runs if module discovery is enabled in configuration.
     *
     * @return array<string> Array of discovered model names from modules
     */
    protected function discoverModuleModels(): array
    {
        $results = [];
        $modulesPath = config('gatekeeper.modules.path', base_path('Modules'));
        $modelPath = config('gatekeeper.modules.discovery_paths.models', '{module}/Models');

        if (! is_dir($modulesPath)) {
            return $results;
        }

        $modules = File::directories($modulesPath);

        foreach ($modules as $module) {
            $moduleModelPath = str_replace('{module}', $module, $modelPath);

            if (is_dir($moduleModelPath)) {
                $models = $this->scanDirectory($moduleModelPath, $modelPath);
                $results = array_merge($results, $models);
            }
        }

        return $results;
    }

    /**
     * Get models that don't have Filament Resources.
     *
     * Compares all discovered models against models that have Filament Resources
     * and returns the difference.
     *
     * @param  array<string>  $resourceModels  Models that have Filament Resources
     * @return array<string> Array of model names without corresponding resources
     */
    public function getModelsWithoutResources(array $resourceModels): array
    {
        $allModels = $this->discover();

        return array_diff($allModels, $resourceModels);
    }

    /**
     * Get the model name from a fully qualified class name.
     *
     * @param  string  $modelClass  The fully qualified model class name
     * @return string The model name (e.g., 'User' from 'App\Models\User')
     */
    public function getModelName(string $modelClass): string
    {
        return class_basename($modelClass);
    }

    /**
     * Get the permission model name (snake_case) from a fully qualified class name.
     *
     * @param  string  $modelClass  The fully qualified model class name
     * @return string The permission model name in snake_case (e.g., 'user' from 'App\Models\User')
     */
    public function getPermissionModelName(string $modelClass): string
    {
        $modelName = $this->getModelName($modelClass);

        return str($modelName)->snake()->toString();
    }
}
