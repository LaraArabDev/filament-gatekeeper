<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Concerns;

use LaraArabDev\FilamentGatekeeper\Facades\Gatekeeper;

/**
 * Trait HasRelationPermissions
 *
 * Provides relation-level permission checking for Filament resources.
 * Controls access to relation managers.
 */
trait HasRelationPermissions
{
    use InteractsWithGatekeeperCache;

    /**
     * Check if the user can view a specific relation.
     */
    public static function canViewRelation(string $relation): bool
    {
        if (static::shouldBypassPermissions()) {
            return true;
        }

        $modelName = static::getRelationPermissionModelName();

        return Gatekeeper::canViewRelation($modelName, $relation);
    }

    /**
     * Get the model name for relation permissions.
     */
    protected static function getRelationPermissionModelName(): string
    {
        // Check if there's a getModelName() method (used by test classes)
        if (method_exists(static::class, 'getModelName')) {
            return static::getModelName();
        }

        if (property_exists(static::class, 'model') && static::$model) {
            return class_basename(static::$model);
        }

        return class_basename(static::class);
    }

    /**
     * Get the permission name for a relation.
     * Format: {action}_{model}_{relation}_relation (Action + Entity + Type).
     */
    public static function getRelationPermissionName(string $relation): string
    {
        $modelName = static::getRelationPermissionModelName();
        $prefixes = config('gatekeeper.permission_prefixes.relation', ['view']);
        $prefix = $prefixes[0] ?? 'view';

        // Convert model name to snake_case
        $modelSnake = str($modelName)->snake()->toString();

        return "{$prefix}_{$modelSnake}_{$relation}_relation";
    }

    /**
     * Get all relation permissions for the model.
     *
     * @return array<string>
     */
    public static function getAllRelationPermissions(): array
    {
        $modelName = static::getRelationPermissionModelName();
        $configuredRelations = array_merge(
            config('gatekeeper.relation_permissions.*', []),
            config("gatekeeper.relation_permissions.{$modelName}", [])
        );

        $permissions = [];
        foreach ($configuredRelations as $relation) {
            $permissions[] = static::getRelationPermissionName($relation);
        }

        return $permissions;
    }

    /**
     * Filter relation managers based on permissions.
     *
     * @param  array<class-string>  $relationManagers
     * @return array<class-string>
     */
    public static function getPermittedRelations(array $relationManagers): array
    {
        if (static::shouldBypassPermissions()) {
            return $relationManagers;
        }

        return array_filter($relationManagers, function (string $relationManagerClass): bool {
            $relationName = static::getRelationNameFromClass($relationManagerClass);

            return static::canViewRelation($relationName);
        });
    }

    /**
     * Get the relation name from a relation manager class.
     */
    protected static function getRelationNameFromClass(string $relationManagerClass): string
    {
        $shortName = class_basename($relationManagerClass);

        return str($shortName)
            ->replace('RelationManager', '')
            ->snake()
            ->replace('_', '')
            ->lower()
            ->toString();
    }

    /**
     * Override getRelations to filter by permissions.
     *
     * This method should be called in the resource's getRelations method:
     *
     * public static function getRelations(): array
     * {
     *     return static::getPermittedRelations([
     *         RolesRelationManager::class,
     *         PostsRelationManager::class,
     *     ]);
     * }
     *
     * @param  array<class-string>  $relationManagers
     * @return array<class-string>
     */
    public static function filterRelationManagers(array $relationManagers): array
    {
        return static::getPermittedRelations($relationManagers);
    }

    /**
     * Get all available relations for the current user.
     *
     * @return array<string>
     */
    public static function getAvailableRelations(): array
    {
        $modelName = static::getRelationPermissionModelName();

        // Get configured relations
        $configuredRelations = array_merge(
            config('gatekeeper.relation_permissions.*', []),
            config("gatekeeper.relation_permissions.{$modelName}", [])
        );

        if (static::shouldBypassPermissions()) {
            return $configuredRelations;
        }

        // Filter by permission
        return array_filter($configuredRelations, static::canViewRelation(...));
    }
}
