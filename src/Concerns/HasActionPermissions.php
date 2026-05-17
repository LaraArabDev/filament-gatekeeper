<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Concerns;

use LaraArabDev\FilamentGatekeeper\Facades\Gatekeeper;

/**
 * Provides action-level permission checking for Filament resources.
 *
 * This trait enables granular control over custom action visibility and
 * executability based on user permissions. It integrates with Gatekeeper
 * to check action-level permissions defined in the configuration.
 */
trait HasActionPermissions
{
    use InteractsWithGatekeeperCache;

    /**
     * Check if the current user can execute a specific action.
     *
     * @param  string  $action  The action name to check
     * @return bool True if the user can execute the action, false otherwise
     */
    public static function canExecuteAction(string $action): bool
    {
        if (static::shouldBypassPermissions()) {
            return true;
        }

        $modelName = static::getActionPermissionModelName();

        return Gatekeeper::canExecuteAction($modelName, $action);
    }

    /**
     * Check if an action should be hidden.
     *
     * An action is hidden if the user cannot execute it.
     *
     * @param  string  $action  The action name to check
     * @return bool True if the action should be hidden, false otherwise
     */
    public static function isActionHidden(string $action): bool
    {
        return ! static::canExecuteAction($action);
    }

    /**
     * Get the model name for action permissions.
     *
     * Attempts to determine the model name by checking for a getModelName()
     * method, then the static $model property, and finally falls back to
     * the class basename.
     *
     * @return string The model name to use for permission checks
     */
    protected static function getActionPermissionModelName(): string
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
     * Get the permission name for an action.
     *
     * Generates a permission name in the format: {action}_{model}_{action_name}_action
     * (Action + Entity + Type) which matches the format used by PermissionRegistrar.
     *
     * @param  string  $action  The action name
     * @return string The generated permission name
     */
    public static function getActionPermissionName(string $action): string
    {
        $modelName = static::getActionPermissionModelName();
        $prefixes = config('gatekeeper.permission_prefixes.action', ['execute']);
        $prefix = $prefixes[0] ?? 'execute';

        $modelSnake = str($modelName)->snake()->toString();

        return "{$prefix}_{$modelSnake}_{$action}_action";
    }

    /**
     * Get all available actions for the current user.
     *
     * @return array<string>
     */
    public static function getAvailableActions(): array
    {
        $modelName = static::getActionPermissionModelName();

        $configuredActions = array_merge(
            config('gatekeeper.custom_actions.*', []),
            config("gatekeeper.custom_actions.{$modelName}", [])
        );

        if (static::shouldBypassPermissions()) {
            return $configuredActions;
        }

        return array_filter($configuredActions, static::canExecuteAction(...));
    }

    /**
     * Get all action permissions for the model.
     *
     * @return array<string>
     */
    public static function getAllActionPermissions(): array
    {
        $modelName = static::getActionPermissionModelName();
        $configuredActions = array_merge(
            config('gatekeeper.custom_actions.*', []),
            config("gatekeeper.custom_actions.{$modelName}", [])
        );

        $permissions = [];
        foreach ($configuredActions as $action) {
            $permissions[] = static::getActionPermissionName($action);
        }

        return $permissions;
    }

    /**
     * Get permitted actions for the current user.
     *
     * @return array<string>
     */
    public static function getPermittedActions(): array
    {
        $modelName = static::getActionPermissionModelName();
        $configuredActions = array_merge(
            config('gatekeeper.custom_actions.*', []),
            config("gatekeeper.custom_actions.{$modelName}", [])
        );

        if (static::shouldBypassPermissions()) {
            return $configuredActions;
        }

        return array_filter($configuredActions, static::canExecuteAction(...));
    }

    /**
     * Apply action permissions to an actions array.
     *
     * @param  array<mixed>  $actions
     * @return array<mixed>
     */
    public static function applyActionPermissions(array $actions): array
    {
        return array_map(function ($action) {
            if (method_exists($action, 'getName')) {
                $actionName = $action->getName();

                if (method_exists($action, 'visible')) {
                    $action->visible(fn (): bool => static::canExecuteAction($actionName));
                }
            }

            return $action;
        }, $actions);
    }

    /**
     * Filter actions based on permissions.
     *
     * @param  array<mixed>  $actions
     * @return array<mixed>
     */
    public static function filterActions(array $actions): array
    {
        return array_filter($actions, function ($action): bool {
            if (method_exists($action, 'getName')) {
                return static::canExecuteAction($action->getName());
            }

            return true;
        });
    }

    /**
     * Check if user can perform bulk action.
     */
    public static function canExecuteBulkAction(string $action): bool
    {
        return static::canExecuteAction("bulk_{$action}");
    }
}
