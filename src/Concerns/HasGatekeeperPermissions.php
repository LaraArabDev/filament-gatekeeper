<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Trait HasGatekeeperPermissions
 *
 * Provides comprehensive resource authorization methods for Filament 3.x.
 * Includes all 9 standard authorization methods plus super admin bypass.
 */
trait HasGatekeeperPermissions
{
    use InteractsWithGatekeeperCache;

    /**
     * Determine whether the user can view any models.
     */
    public static function canViewAny(): bool
    {
        if (static::shouldBypassPermissions()) {
            return true;
        }

        return static::userCan(static::getPermissionName('view_any'));
    }

    /**
     * Determine whether the user can view the model.
     */
    public static function canView(Model $record): bool
    {
        if (static::shouldBypassPermissions()) {
            return true;
        }

        return static::userCan(static::getPermissionName('view'));
    }

    /**
     * Determine whether the user can create models.
     */
    public static function canCreate(): bool
    {
        if (static::shouldBypassPermissions()) {
            return true;
        }

        return static::userCan(static::getPermissionName('create'));
    }

    /**
     * Determine whether the user can update the model.
     */
    public static function canEdit(Model $record): bool
    {
        if (static::shouldBypassPermissions()) {
            return true;
        }

        return static::userCan(static::getPermissionName('update'));
    }

    /**
     * Alias for canEdit (Filament uses canUpdate in some contexts).
     */
    public static function canUpdate(mixed $record = null): bool
    {
        if ($record instanceof Model) {
            return static::canEdit($record);
        }

        // If no record provided, check if user can update any record
        if (static::shouldBypassPermissions()) {
            return true;
        }

        return static::userCan(static::getPermissionName('update'));
    }

    /**
     * Determine whether the user can delete the model.
     */
    public static function canDelete(?Model $record = null): bool
    {
        if (static::shouldBypassPermissions()) {
            return true;
        }

        return static::userCan(static::getPermissionName('delete'));
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public static function canDeleteAny(): bool
    {
        if (static::shouldBypassPermissions()) {
            return true;
        }

        return static::userCan(static::getPermissionName('delete_any'));
    }

    /**
     * Determine whether the user can restore the model.
     */
    public static function canRestore(?Model $record = null): bool
    {
        if (static::shouldBypassPermissions()) {
            return true;
        }

        return static::userCan(static::getPermissionName('restore'));
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public static function canRestoreAny(): bool
    {
        if (static::shouldBypassPermissions()) {
            return true;
        }

        return static::userCan(static::getPermissionName('restore_any'));
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public static function canForceDelete(?Model $record = null): bool
    {
        if (static::shouldBypassPermissions()) {
            return true;
        }

        return static::userCan(static::getPermissionName('force_delete'));
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public static function canForceDeleteAny(): bool
    {
        if (static::shouldBypassPermissions()) {
            return true;
        }

        return static::userCan(static::getPermissionName('force_delete_any'));
    }

    /**
     * Determine whether the user can replicate the model.
     */
    public static function canReplicate(?Model $record = null): bool
    {
        if (static::shouldBypassPermissions()) {
            return true;
        }

        return static::userCan(static::getPermissionName('replicate'));
    }

    /**
     * Determine whether the user can reorder models.
     */
    public static function canReorder(): bool
    {
        if (static::shouldBypassPermissions()) {
            return true;
        }

        return static::userCan(static::getPermissionName('reorder'));
    }

    /**
     * Determine if the resource should be registered in navigation.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    /**
     * Get the permission name for an action.
     */
    protected static function getPermissionName(string $action): string
    {
        $modelName = static::getGatekeeperModelName();
        $separator = config('gatekeeper.generator.separator', '_');

        if (config('gatekeeper.generator.snake_case', true)) {
            $modelName = str($modelName)->snake()->toString();
        } else {
            $modelName = str($modelName)->camel()->toString();
        }

        return "{$action}{$separator}{$modelName}";
    }

    /**
     * Get the model name for permission generation.
     */
    protected static function getGatekeeperModelName(): string
    {
        if (property_exists(static::class, 'model') && static::$model) {
            return class_basename(static::$model);
        }

        // Extract from resource name (UserResource -> User)
        $resourceName = class_basename(static::class);

        return str($resourceName)
            ->replace('Resource', '')
            ->toString();
    }

    /**
     * Check if user can perform a custom action.
     */
    public static function canPerformAction(string $action): bool
    {
        if (static::shouldBypassPermissions()) {
            return true;
        }

        return static::userCan(static::getPermissionName($action));
    }

    /**
     * Check if user can access a custom route.
     */
    public static function canAccessRoute(string $route): bool
    {
        if (static::shouldBypassPermissions()) {
            return true;
        }

        return static::userCan("access_{$route}");
    }
}
