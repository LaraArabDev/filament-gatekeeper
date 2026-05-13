<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Support\Discovery;

use Illuminate\Support\Facades\File;

/**
 * Handles discovery of Filament widgets within the application.
 *
 * This class scans configured widget paths to discover all Filament widget
 * classes and supports module-based discovery for HMVC applications.
 *
 * @package LaraArabDev\FilamentGatekeeper\Support\Discovery
 */
class WidgetDiscovery
{
    /**
     * Discover all Filament widgets.
     *
     * Scans configured widget paths and optionally module paths to discover
     * all Filament widget classes. Filters out excluded widgets and returns
     * unique widget names.
     *
     * @return array<string> Array of discovered widget names
     */
    public function discover(): array
    {
        $widgets = [];
        $paths = config('gatekeeper.discovery.widgets', []);
        $excludedWidgets = config('gatekeeper.excluded_widgets', []);

        foreach ($paths as $pathPattern) {
            $foundWidgets = $this->scanPath($pathPattern);
            $widgets = array_merge($widgets, $foundWidgets);
        }

        if (config('gatekeeper.modules.enabled', false)) {
            $moduleWidgets = $this->discoverModuleWidgets();
            $widgets = array_merge($widgets, $moduleWidgets);
        }

        $widgets = array_filter($widgets, function ($widget) use ($excludedWidgets) {
            foreach ($excludedWidgets as $excluded) {
                if (str_contains($widget, class_basename($excluded))) {
                    return false;
                }
            }

            return true;
        });

        return array_unique($widgets);
    }

    /**
     * Scan a path pattern for widget classes.
     *
     * Handles both glob patterns and direct directory paths.
     *
     * @param string $pathPattern The path pattern to scan (supports glob patterns)
     * @return array<string> Array of discovered widget names
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
     * Scan a directory for widget files.
     *
     * Scans a directory for PHP files and extracts widget names from
     * file names (without extension).
     *
     * @param string $directory The directory path to scan
     * @return array<string> Array of discovered widget names
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
            $results[] = $filename;
        }

        return $results;
    }

    /**
     * Discover widgets from modules (HMVC support).
     *
     * Scans all modules in the configured modules path for widget classes.
     * Only runs if module discovery is enabled in configuration.
     *
     * @return array<string> Array of discovered widget names from modules
     */
    protected function discoverModuleWidgets(): array
    {
        $results = [];
        $modulesPath = config('gatekeeper.modules.path', base_path('Modules'));
        $widgetPath = config('gatekeeper.modules.discovery_paths.widgets', '{module}/Filament/Widgets');

        if (! is_dir($modulesPath)) {
            return $results;
        }

        $modules = File::directories($modulesPath);

        foreach ($modules as $module) {
            $moduleWidgetPath = str_replace('{module}', $module, $widgetPath);

            if (is_dir($moduleWidgetPath)) {
                $widgets = $this->scanDirectory($moduleWidgetPath);
                $results = array_merge($results, $widgets);
            }
        }

        return $results;
    }
}
