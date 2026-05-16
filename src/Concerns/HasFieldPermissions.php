<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Concerns;

use LaraArabDev\FilamentGatekeeper\Facades\Gatekeeper;

/**
 * Provides field-level permission checking for Filament resources.
 *
 * This trait enables granular control over form field visibility and
 * editability based on user permissions. It integrates with Gatekeeper
 * to check field-level permissions defined in the configuration.
 */
trait HasFieldPermissions
{
    use InteractsWithGatekeeperCache;

    /**
     * Check if the current user can view a specific field.
     *
     * @param  string  $field  The field name to check
     * @return bool True if the user can view the field, false otherwise
     */
    public static function canViewField(string $field): bool
    {
        if (static::shouldBypassPermissions()) {
            return true;
        }

        $modelName = static::getFieldPermissionModelName();

        return Gatekeeper::canViewField($modelName, $field);
    }

    /**
     * Check if the current user can update a specific field.
     *
     * @param  string  $field  The field name to check
     * @return bool True if the user can update the field, false otherwise
     */
    public static function canUpdateField(string $field): bool
    {
        if (static::shouldBypassPermissions()) {
            return true;
        }

        $modelName = static::getFieldPermissionModelName();

        return Gatekeeper::canUpdateField($modelName, $field);
    }

    /**
     * Check if a field should be disabled (read-only).
     *
     * A field is disabled if the user cannot update it.
     *
     * @param  string  $field  The field name to check
     * @return bool True if the field should be disabled, false otherwise
     */
    public static function isFieldDisabled(string $field): bool
    {
        return ! static::canUpdateField($field);
    }

    /**
     * Check if a field should be hidden.
     *
     * A field is hidden if the user cannot view it.
     *
     * @param  string  $field  The field name to check
     * @return bool True if the field should be hidden, false otherwise
     */
    public static function isFieldHidden(string $field): bool
    {
        return ! static::canViewField($field);
    }

    /**
     * Get the model name for field permissions.
     *
     * Attempts to determine the model name by checking for a getModelName()
     * method, then the static $model property, and finally falls back to
     * the class basename.
     *
     * @return string The model name to use for permission checks
     */
    protected static function getFieldPermissionModelName(): string
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
     * Get the permission name for a field action.
     *
     * Generates a permission name in the format: {action}_{model}_{field}_field
     * (Action + Entity + Type) which matches the format used by PermissionRegistrar.
     *
     * @param  string  $action  The action type ('view' or 'update')
     * @param  string  $field  The field name
     * @return string The generated permission name
     */
    public static function getFieldPermissionName(string $action, string $field): string
    {
        $modelName = static::getFieldPermissionModelName();
        $prefixes = config('gatekeeper.permission_prefixes.field', ['view', 'update']);
        $prefix = $action === 'view' ? 'view' : ($action === 'update' ? 'update' : $prefixes[0] ?? 'view');

        $modelSnake = str($modelName)->snake()->toString();

        return "{$prefix}_{$modelSnake}_{$field}_field";
    }

    /**
     * Get all field permissions for the model.
     *
     * @return array<string>
     */
    public static function getAllFieldPermissions(): array
    {
        $modelName = static::getFieldPermissionModelName();
        $configuredFields = array_merge(
            config('gatekeeper.field_permissions.*', []),
            config("gatekeeper.field_permissions.{$modelName}", [])
        );

        $permissions = [];
        foreach ($configuredFields as $field) {
            $permissions[] = static::getFieldPermissionName('view', $field);
            $permissions[] = static::getFieldPermissionName('update', $field);
        }

        return $permissions;
    }

    /**
     * Get all visible fields for the current user.
     *
     * @return array<string>
     */
    public static function getVisibleFields(): array
    {
        if (static::shouldBypassPermissions()) {
            $modelName = static::getFieldPermissionModelName();

            return array_merge(
                config('gatekeeper.field_permissions.*', []),
                config("gatekeeper.field_permissions.{$modelName}", [])
            );
        }

        $modelName = static::getFieldPermissionModelName();

        return Gatekeeper::getVisibleFields($modelName);
    }

    /**
     * Get all editable fields for the current user.
     *
     * @return array<string>
     */
    public static function getEditableFields(): array
    {
        $modelName = static::getFieldPermissionModelName();

        if (static::shouldBypassPermissions()) {
            $configuredFields = array_merge(
                config('gatekeeper.field_permissions.*', []),
                config("gatekeeper.field_permissions.{$modelName}", [])
            );

            return $configuredFields;
        }

        $configuredFields = array_merge(
            config('gatekeeper.field_permissions.*', []),
            config("gatekeeper.field_permissions.{$modelName}", [])
        );

        if (empty($configuredFields)) {
            return [];
        }

        return array_filter($configuredFields, fn ($field) => static::canUpdateField($field));
    }

    /**
     * Apply field permissions to a form schema array.
     *
     * @param  array<mixed>  $schema
     * @return array<mixed>
     */
    public static function applyFieldPermissions(array $schema): array
    {
        return array_map(function ($component) {
            if (method_exists($component, 'getName')) {
                $fieldName = $component->getName();

                if (method_exists($component, 'visible')) {
                    $component->visible(fn () => static::canViewField($fieldName));
                }

                if (method_exists($component, 'disabled')) {
                    $component->disabled(fn () => ! static::canUpdateField($fieldName));
                }
            }

            return $component;
        }, $schema);
    }
}
