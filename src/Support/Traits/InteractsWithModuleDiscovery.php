<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Support\Traits;

use Illuminate\Support\Facades\File;

/**
 * Provides module discovery functionality for HMVC support.
 *
 * This trait handles discovery of classes within Laravel modules
 * (nwidart/laravel-modules) for multi-module applications.
 *
 * @package LaraArabDev\FilamentGatekeeper\Support\Traits
 */
trait InteractsWithModuleDiscovery
{
    /**
     * Discover classes from modules based on path pattern.
     *
     * Scans all modules in the configured modules path and discovers
     * classes matching the provided path pattern.
     *
     * @param string $modulePathPattern The path pattern within modules (e.g., '{module}/Models')
     * @return array<string> Array of discovered class names or identifiers
     */
    protected function discoverFromModules(string $modulePathPattern): array
    {
        $results = [];
        $modulesPath = config('gatekeeper.modules.path', base_path('Modules'));

        if (! is_dir($modulesPath)) {
            return $results;
        }

        $modules = File::directories($modulesPath);

        foreach ($modules as $module) {
            $moduleClassPath = str_replace('{module}', $module, $modulePathPattern);

            if (is_dir($moduleClassPath)) {
                $classes = $this->scanDirectory($moduleClassPath, $modulePathPattern);
                $results = array_merge($results, $classes);
            }
        }

        return $results;
    }

    /**
     * Check if module discovery is enabled.
     *
     * @return bool True if modules are enabled, false otherwise
     */
    protected function isModuleDiscoveryEnabled(): bool
    {
        return config('gatekeeper.modules.enabled', false);
    }

    /**
     * Scan a directory for class files.
     *
     * Must be implemented by the consuming class.
     *
     * @param string $directory The directory path to scan
     * @param string $pathPattern The original path pattern
     * @return array<string> Array of discovered class names or identifiers
     */
    abstract protected function scanDirectory(string $directory, string $pathPattern): array;
}
