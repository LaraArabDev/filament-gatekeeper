<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Facades;

use Illuminate\Support\Facades\Facade;
use LaraArabDev\FilamentGatekeeper\Services\PermissionCache;
use LaraArabDev\FilamentGatekeeper\Gatekeeper as GatekeeperService;

/**
 * Shield Facade
 *
 * Guard Management:
 * @method static \LaraArabDev\FilamentGatekeeper\Gatekeeper guard(string $guardName)
 * @method static \LaraArabDev\FilamentGatekeeper\Gatekeeper api()
 * @method static \LaraArabDev\FilamentGatekeeper\Gatekeeper web()
 * @method static string getGuard()
 *
 * User & Authentication:
 * @method static \Illuminate\Contracts\Auth\Authenticatable|null user()
 * @method static bool shouldBypassPermissions(\Illuminate\Contracts\Auth\Authenticatable|null $user = null)
 *
 * Permission Checking:
 * @method static bool can(string $permission, mixed $record = null)
 * @method static void authorize(string $permission, mixed $record = null)
 *
 * Field Permissions:
 * @method static bool canViewField(string $modelName, string $field)
 * @method static bool canUpdateField(string $modelName, string $field)
 * @method static array getVisibleFields(string $modelName)
 *
 * Column Permissions:
 * @method static bool canViewColumn(string $modelName, string $column)
 * @method static array getVisibleColumns(string $modelName)
 *
 * Action Permissions:
 * @method static bool canExecuteAction(string $modelName, string $action)
 *
 * Relation Permissions:
 * @method static bool canViewRelation(string $modelName, string $relation)
 *
 * Cache:
 * @method static PermissionCache cache()
 * @method static array getPermissionMatrix(\Illuminate\Contracts\Auth\Authenticatable|null $user = null)
 *
 * @see \LaraArabDev\FilamentGatekeeper\GatekeeperService
 */
class Gatekeeper extends Facade
{
    /**
     * Get the facade accessor.
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return GatekeeperService::class;
    }
}
