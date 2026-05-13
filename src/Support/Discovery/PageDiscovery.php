<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Support\Discovery;

use Illuminate\Support\Facades\File;

/**
 * Handles discovery of Filament pages within the application.
 *
 * This class scans configured page paths to discover all Filament page
 * classes, supports module-based discovery for HMVC applications, and
 * filters out resource page files (CreateUser, EditUser, ListUsers).
 *
 * @package LaraArabDev\FilamentGatekeeper\Support\Discovery
 */
class PageDiscovery
{
    /**
     * Discover all Filament pages.
     *
     * Scans configured page paths and optionally module paths to discover
     * all Filament page classes. Filters out excluded pages and resource
     * page files, returning unique page names.
     *
     * @return array<string> Array of discovered page names
     */
    public function discover(): array
    {
        $pages = [];
        $paths = config('gatekeeper.discovery.pages', []);
        $excludedPages = config('gatekeeper.excluded_pages', []);

        foreach ($paths as $pathPattern) {
            $foundPages = $this->scanPath($pathPattern);
            $pages = array_merge($pages, $foundPages);
        }

        if (config('gatekeeper.modules.enabled', false)) {
            $modulePages = $this->discoverModulePages();
            $pages = array_merge($pages, $modulePages);
        }

        $pages = array_filter($pages, function ($page) use ($excludedPages) {
            foreach ($excludedPages as $excluded) {
                if (str_contains($page, class_basename($excluded))) {
                    return false;
                }
            }

            return true;
        });

        return array_unique($pages);
    }

    /**
     * Scan a path pattern for page classes.
     *
     * Handles both glob patterns and direct directory paths.
     *
     * @param string $pathPattern The path pattern to scan (supports glob patterns)
     * @return array<string> Array of discovered page names
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
     * Scan a directory for page files.
     *
     * Scans a directory for PHP files and filters out resource page files
     * (CreateUser, EditUser, ListUsers, ViewUser, ManageUser) which are
     * handled separately by ResourceDiscovery.
     *
     * @param string $directory The directory path to scan
     * @return array<string> Array of discovered page names
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

            if (preg_match('/^(Create|Edit|List|View|Manage)/', $filename)) {
                continue;
            }

            $results[] = $filename;
        }

        return $results;
    }

    /**
     * Discover pages from modules (HMVC support).
     *
     * Scans all modules in the configured modules path for page classes.
     * Only runs if module discovery is enabled in configuration.
     *
     * @return array<string> Array of discovered page names from modules
     */
    protected function discoverModulePages(): array
    {
        $results = [];
        $modulesPath = config('gatekeeper.modules.path', base_path('Modules'));
        $pagePath = config('gatekeeper.modules.discovery_paths.pages', '{module}/Filament/Pages');

        if (! is_dir($modulesPath)) {
            return $results;
        }

        $modules = File::directories($modulesPath);

        foreach ($modules as $module) {
            $modulePagePath = str_replace('{module}', $module, $pagePath);

            if (is_dir($modulePagePath)) {
                $pages = $this->scanDirectory($modulePagePath);
                $results = array_merge($results, $pages);
            }
        }

        return $results;
    }
}
