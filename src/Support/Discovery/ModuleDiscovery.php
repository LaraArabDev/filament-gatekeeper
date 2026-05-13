<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Support\Discovery;

use Illuminate\Support\Facades\File;

class ModuleDiscovery
{
    /**
     * Check if modules are enabled.
     */
    public function isEnabled(): bool
    {
        return config('gatekeeper.modules.enabled', false);
    }

    /**
     * Get all discovered modules.
     *
     * @return array<string>
     */
    public function getModules(): array
    {
        if (! $this->isEnabled()) {
            return [];
        }

        $modulesPath = config('gatekeeper.modules.path', base_path('Modules'));

        if (! is_dir($modulesPath)) {
            return [];
        }

        $directories = File::directories($modulesPath);

        return array_map(fn ($dir) => basename($dir), $directories);
    }

    /**
     * Get models from all modules.
     *
     * @return array<string>
     */
    public function getModels(): array
    {
        $models = [];
        $modulesPath = config('gatekeeper.modules.path', base_path('Modules'));
        $modelPath = config('gatekeeper.modules.discovery_paths.models', '{module}/Models');

        foreach ($this->getModules() as $module) {
            $fullPath = str_replace('{module}', "{$modulesPath}/{$module}", $modelPath);

            if (is_dir($fullPath)) {
                $files = File::files($fullPath);
                foreach ($files as $file) {
                    $models[] = $file->getFilenameWithoutExtension();
                }
            }
        }

        return $models;
    }

    /**
     * Get the namespace for a module.
     */
    public function getModuleNamespace(string $module): string
    {
        $namespace = config('gatekeeper.modules.namespace', 'Modules');

        return "{$namespace}\\{$module}";
    }

    /**
     * Get the full class name for a model in a module.
     */
    public function getModelClass(string $module, string $model): string
    {
        $namespace = $this->getModuleNamespace($module);

        return "{$namespace}\\Models\\{$model}";
    }

    /**
     * Get the full class name for a resource in a module.
     */
    public function getResourceClass(string $module, string $resource): string
    {
        $namespace = $this->getModuleNamespace($module);

        return "{$namespace}\\Filament\\Resources\\{$resource}";
    }

    /**
     * Get all resources from all modules.
     *
     * @return array<string, string>
     */
    public function getResources(): array
    {
        $resources = [];
        $modulesPath = config('gatekeeper.modules.path', base_path('Modules'));
        $resourcePath = config('gatekeeper.modules.discovery_paths.resources', '{module}/Filament/Resources');

        foreach ($this->getModules() as $module) {
            $fullPath = str_replace('{module}', "{$modulesPath}/{$module}", $resourcePath);

            if (is_dir($fullPath)) {
                $files = File::files($fullPath);
                foreach ($files as $file) {
                    $filename = $file->getFilenameWithoutExtension();
                    if (str_ends_with($filename, 'Resource')) {
                        $resources[$filename] = $this->getResourceClass($module, $filename);
                    }
                }
            }
        }

        return $resources;
    }

    /**
     * Get all pages from all modules.
     *
     * @return array<string, string>
     */
    public function getPages(): array
    {
        $pages = [];
        $modulesPath = config('gatekeeper.modules.path', base_path('Modules'));
        $pagePath = config('gatekeeper.modules.discovery_paths.pages', '{module}/Filament/Pages');

        foreach ($this->getModules() as $module) {
            $fullPath = str_replace('{module}', "{$modulesPath}/{$module}", $pagePath);

            if (is_dir($fullPath)) {
                $files = File::files($fullPath);
                foreach ($files as $file) {
                    $filename = $file->getFilenameWithoutExtension();
                    $namespace = $this->getModuleNamespace($module);
                    $pages[$filename] = "{$namespace}\\Filament\\Pages\\{$filename}";
                }
            }
        }

        return $pages;
    }

    /**
     * Get all widgets from all modules.
     *
     * @return array<string, string>
     */
    public function getWidgets(): array
    {
        $widgets = [];
        $modulesPath = config('gatekeeper.modules.path', base_path('Modules'));
        $widgetPath = config('gatekeeper.modules.discovery_paths.widgets', '{module}/Filament/Widgets');

        foreach ($this->getModules() as $module) {
            $fullPath = str_replace('{module}', "{$modulesPath}/{$module}", $widgetPath);

            if (is_dir($fullPath)) {
                $files = File::files($fullPath);
                foreach ($files as $file) {
                    $filename = $file->getFilenameWithoutExtension();
                    $namespace = $this->getModuleNamespace($module);
                    $widgets[$filename] = "{$namespace}\\Filament\\Widgets\\{$filename}";
                }
            }
        }

        return $widgets;
    }
}

