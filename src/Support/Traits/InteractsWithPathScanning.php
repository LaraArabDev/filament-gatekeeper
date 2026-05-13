<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Support\Traits;

use Illuminate\Support\Facades\File;

/**
 * Provides path scanning functionality for discovery classes.
 *
 * This trait handles common operations for scanning directories
 * and paths to discover classes within the application.
 *
 * @package LaraArabDev\FilamentGatekeeper\Support\Traits
 */
trait InteractsWithPathScanning
{
    /**
     * Scan a path pattern for class files.
     *
     * Handles both glob patterns and direct directory paths.
     *
     * @param string $pathPattern The path pattern to scan (supports glob patterns)
     * @return array<string> Array of discovered class names or identifiers
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
     * Scan a directory for class files.
     *
     * Must be implemented by the consuming class to handle
     * specific file type detection and class extraction.
     *
     * @param string $directory The directory path to scan
     * @param string $pathPattern The original path pattern (for namespace resolution)
     * @return array<string> Array of discovered class names or identifiers
     */
    abstract protected function scanDirectory(string $directory, string $pathPattern): array;

    /**
     * Get the fully qualified class name from a file.
     *
     * Extracts namespace and class name from PHP source code.
     *
     * @param string $filePath The full path to the PHP file
     * @param string $pathPattern The path pattern for namespace resolution
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
}
