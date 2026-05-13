<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Support\Discovery;

use Illuminate\Support\Facades\File;

/**
 * Handles discovery of Filament resources within the application.
 *
 * This class scans configured resource paths to discover all Filament resource
 * classes, supports module-based discovery for HMVC applications, and provides
 * utilities for extracting model names from resource class names.
 *
 * @package LaraArabDev\FilamentGatekeeper\Support\Discovery
 */
class ResourceDiscovery
{
    /**
     * Discover all Filament resources.
     *
     * Scans configured resource paths and optionally module paths to discover
     * all Filament resource classes. Filters out excluded resources and returns
     * unique model names extracted from resource class names.
     *
     * @return array<string> Array of discovered model names (extracted from resource class names)
     */
    public function discover(): array
    {
        $resources = [];
        $paths = config('gatekeeper.discovery.resources', []);
        $excludedResources = config('gatekeeper.excluded_resources', []);

        foreach ($paths as $pathPattern) {
            $foundResources = $this->scanPath($pathPattern);
            $resources = array_merge($resources, $foundResources);
        }

        if (config('gatekeeper.modules.enabled', false)) {
            $moduleResources = $this->discoverModuleResources();
            $resources = array_merge($resources, $moduleResources);
        }

        $resources = array_filter($resources, function ($resource) use ($excludedResources) {
            foreach ($excludedResources as $excluded) {
                if (str_contains($resource, class_basename($excluded))) {
                    return false;
                }
            }

            return true;
        });

        return array_unique($resources);
    }

    /**
     * Scan a path pattern for resource classes.
     *
     * Handles both glob patterns and direct directory paths.
     *
     * @param string $pathPattern The path pattern to scan (supports glob patterns)
     * @return array<string> Array of discovered model names (extracted from resource files)
     */
    protected function scanPath(string $pathPattern): array
    {
        $results = [];
        $basePath = base_path($pathPattern);

        if (str_contains($pathPattern, '*')) {
            $directories = glob($basePath, GLOB_ONLYDIR) ?: [];
            foreach ($directories as $directory) {
                $results = array_merge($results, $this->scanDirectory($directory));
            }
        } elseif (is_dir($basePath)) {
            $results = $this->scanDirectory($basePath);
        }

        return $results;
    }

    /**
     * Scan a directory for resource files.
     *
     * Scans a directory for PHP files ending with 'Resource' suffix,
     * extracts the model name by removing the 'Resource' suffix.
     *
     * @param string $directory The directory path to scan
     * @return array<string> Array of model names extracted from resource file names
     */
    protected function scanDirectory(string $directory): array
    {
        $results = [];

        if (! is_dir($directory)) {
            return $results;
        }

        $files = File::files($directory);

        foreach ($files as $file) {
            $filename = $file->getFilenameWithoutExtension();

            if (str_ends_with($filename, 'Resource')) {
                $modelName = str_replace('Resource', '', $filename);
                $results[] = $modelName;
            }
        }

        return $results;
    }

    /**
     * Discover resources from modules (HMVC support).
     *
     * Scans all modules in the configured modules path for resource classes.
     * Only runs if module discovery is enabled in configuration.
     *
     * @return array<string> Array of discovered model names from module resources
     */
    protected function discoverModuleResources(): array
    {
        $results = [];
        $modulesPath = config('gatekeeper.modules.path', base_path('Modules'));
        $resourcePath = config('gatekeeper.modules.discovery_paths.resources', '{module}/Filament/Resources');

        if (! is_dir($modulesPath)) {
            return $results;
        }

        $modules = File::directories($modulesPath);

        foreach ($modules as $module) {
            $moduleName = basename($module);
            $moduleResourcePath = str_replace('{module}', $module, $resourcePath);

            if (is_dir($moduleResourcePath)) {
                $resources = $this->scanDirectory($moduleResourcePath);
                $results = array_merge($results, $resources);
            }
        }

        return $results;
    }

    /**
     * Get the model name from a resource class name.
     *
     * Extracts the model name by removing the 'Resource' suffix from
     * the resource class basename.
     *
     * @param string $resourceClass The fully qualified resource class name
     * @return string The model name (e.g., 'User' from 'App\Filament\Resources\UserResource')
     */
    public function getModelFromResource(string $resourceClass): string
    {
        $basename = class_basename($resourceClass);

        return str_replace('Resource', '', $basename);
    }

    /**
     * Get the permission name (snake_case model name) from a resource class name.
     *
     * Extracts the model name from the resource class and converts it
     * to snake_case format suitable for permission names.
     *
     * @param string $resourceClass The fully qualified resource class name
     * @return string The permission model name in snake_case (e.g., 'user' from 'UserResource')
     */
    public function getPermissionName(string $resourceClass): string
    {
        $modelName = $this->getModelFromResource($resourceClass);

        return str($modelName)->snake()->toString();
    }

    /**
     * Discover resources from modules (public alias for discoverModuleResources).
     *
     * Public method to discover resources from modules. This is an alias
     * for the protected discoverModuleResources method.
     *
     * @return array<string> Array of discovered model names from module resources
     */
    public function discoverFromModules(): array
    {
        return $this->discoverModuleResources();
    }
}
