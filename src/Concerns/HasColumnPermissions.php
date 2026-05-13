<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Concerns;

use LaraArabDev\FilamentGatekeeper\Facades\Gatekeeper;

/**
 * Provides column-level permission checking for Filament table resources.
 *
 * This trait enables granular control over table column visibility based
 * on user permissions. It integrates with Gatekeeper to check column-level
 * permissions defined in the configuration.
 *
 * @package LaraArabDev\FilamentGatekeeper\Concerns
 */
trait HasColumnPermissions
{
    use InteractsWithGatekeeperCache;

    /**
     * Check if the current user can view a specific column.
     *
     * @param string $column The column name to check
     * @return bool True if the user can view the column, false otherwise
     */
    public static function canViewColumn(string $column): bool
    {
        if (static::shouldBypassPermissions()) {
            return true;
        }

        $modelName = static::getColumnPermissionModelName();

        return Gatekeeper::canViewColumn($modelName, $column);
    }

    /**
     * Check if a column should be hidden.
     *
     * A column is hidden if the user cannot view it.
     *
     * @param string $column The column name to check
     * @return bool True if the column should be hidden, false otherwise
     */
    public static function isColumnHidden(string $column): bool
    {
        return ! static::canViewColumn($column);
    }

    /**
     * Get the model name for column permissions.
     *
     * Attempts to determine the model name by checking for a getModelName()
     * method, then the static $model property, and finally falls back to
     * the class basename.
     *
     * @return string The model name to use for permission checks
     */
    protected static function getColumnPermissionModelName(): string
    {
        if (method_exists(static::class, 'getModelName')) {
            return static::getModelName();
        }

        if (property_exists(static::class, 'model') && static::$model) {
            return class_basename(static::$model);
        }

        return class_basename(static::class);
    }

    /**
     * Get the permission name for a column.
     *
     * Generates a permission name in the format: {action}_{model}_{column}_column
     * (Action + Entity + Type) which matches the format used by PermissionRegistrar.
     *
     * @param string $column The column name
     * @return string The generated permission name
     */
    public static function getColumnPermissionName(string $column): string
    {
        $modelName = static::getColumnPermissionModelName();
        $prefixes = config('gatekeeper.permission_prefixes.column', ['view']);
        $prefix = $prefixes[0] ?? 'view';

        $modelSnake = str($modelName)->snake()->toString();

        return "{$prefix}_{$modelSnake}_{$column}_column";
    }

    /**
     * Get all column permissions for the model.
     *
     * @return array<string>
     */
    public static function getAllColumnPermissions(): array
    {
        $modelName = static::getColumnPermissionModelName();
        $configuredColumns = array_merge(
            config('gatekeeper.column_permissions.*', []),
            config("gatekeeper.column_permissions.{$modelName}", [])
        );

        $permissions = [];
        foreach ($configuredColumns as $column) {
            $permissions[] = static::getColumnPermissionName($column);
        }

        return $permissions;
    }

    /**
     * Get all visible columns for the current user.
     *
     * @return array<string>
     */
    public static function getVisibleColumns(): array
    {
        if (static::shouldBypassPermissions()) {
            $modelName = static::getColumnPermissionModelName();

            return array_merge(
                config("gatekeeper.column_permissions.*", []),
                config("gatekeeper.column_permissions.{$modelName}", [])
            );
        }

        $modelName = static::getColumnPermissionModelName();

        return Gatekeeper::getVisibleColumns($modelName);
    }

    /**
     * Apply column permissions to a table columns array.
     *
     * @param  array<mixed>  $columns
     * @return array<mixed>
     */
    public static function applyColumnPermissions(array $columns): array
    {
        return array_map(function ($column) {
            if (method_exists($column, 'getName')) {
                $columnName = $column->getName();

                if (method_exists($column, 'visible')) {
                    $column->visible(fn() => static::canViewColumn($columnName));
                }
            }

            return $column;
        }, $columns);
    }

    /**
     * Filter columns based on permissions.
     *
     * @param  array<mixed>  $columns
     * @return array<mixed>
     */
    public static function filterColumns(array $columns): array
    {
        return array_filter($columns, function ($column) {
            if (method_exists($column, 'getName')) {
                return static::canViewColumn($column->getName());
            }

            return true;
        });
    }
}
