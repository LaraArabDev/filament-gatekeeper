<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Services;

use Illuminate\Support\Facades\DB;
use LaraArabDev\FilamentGatekeeper\Models\Permission;
use LaraArabDev\FilamentGatekeeper\Models\Role;
use LaraArabDev\FilamentGatekeeper\Support\Discovery\ColumnDiscovery;
use LaraArabDev\FilamentGatekeeper\Support\Discovery\FieldDiscovery;
use LaraArabDev\FilamentGatekeeper\Support\Discovery\ModelDiscovery;
use LaraArabDev\FilamentGatekeeper\Support\Discovery\ModuleDiscovery;
use LaraArabDev\FilamentGatekeeper\Support\Discovery\PageDiscovery;
use LaraArabDev\FilamentGatekeeper\Support\Discovery\ResourceDiscovery;
use LaraArabDev\FilamentGatekeeper\Support\Discovery\WidgetDiscovery;

/**
 * Class PermissionRegistrar
 *
 * Handles the registration and synchronization of permissions
 * for all discovered entities including resources, pages, widgets,
 * fields, columns, actions, and relations.
 */
class PermissionRegistrar
{
    /**
     * Resource discovery service.
     */
    protected ResourceDiscovery $resourceDiscovery;

    /**
     * Page discovery service.
     */
    protected PageDiscovery $pageDiscovery;

    /**
     * Widget discovery service.
     */
    protected WidgetDiscovery $widgetDiscovery;

    /**
     * Module discovery service.
     */
    protected ModuleDiscovery $moduleDiscovery;

    /**
     * Model discovery service.
     */
    protected ModelDiscovery $modelDiscovery;

    /**
     * Field discovery service.
     */
    protected FieldDiscovery $fieldDiscovery;

    /**
     * Column discovery service.
     */
    protected ColumnDiscovery $columnDiscovery;

    /**
     * List of enabled guards.
     *
     * @var array<string>
     */
    protected array $guards;

    /**
     * Whether to run in dry-run mode.
     */
    protected bool $dryRun = false;

    /**
     * Sync operation log.
     *
     * @var array<string, array<string>>
     */
    protected array $syncLog = [];

    /**
     * List of discovered resource models.
     *
     * @var array<string>
     */
    protected array $resourceModels = [];

    /**
     * Create a new PermissionRegistrar instance.
     *
     * Initializes all discovery services and loads enabled guards
     * from configuration.
     */
    public function __construct()
    {
        $this->resourceDiscovery = new ResourceDiscovery;
        $this->pageDiscovery = new PageDiscovery;
        $this->widgetDiscovery = new WidgetDiscovery;
        $this->moduleDiscovery = new ModuleDiscovery;
        $this->modelDiscovery = new ModelDiscovery;
        $this->fieldDiscovery = new FieldDiscovery;
        $this->columnDiscovery = new ColumnDiscovery;
        $this->guards = $this->resolveGuardsFromConfig();
    }

    /**
     * Resolve guards from configuration.
     *
     * Handles both simple array format ['web', 'api'] and
     * associative array format ['web' => ['enabled' => true]].
     *
     * @return array<string> List of enabled guard names
     */
    protected function resolveGuardsFromConfig(): array
    {
        $guardsConfig = config('gatekeeper.guards', ['web' => ['enabled' => true]]);

        if (isset($guardsConfig[0]) && is_string($guardsConfig[0])) {
            return $guardsConfig;
        }

        $guards = array_keys(array_filter(
            $guardsConfig,
            fn ($guard): mixed => is_array($guard) ? ($guard['enabled'] ?? true) : true
        ));

        return $guards === [] ? ['web'] : $guards;
    }

    /**
     * Set dry run mode.
     *
     * When enabled, no actual database changes are made.
     * Useful for previewing what would be synced.
     *
     * @param  bool  $dryRun  Whether to enable dry run mode
     */
    public function dryRun(bool $dryRun = true): static
    {
        $this->dryRun = $dryRun;

        return $this;
    }

    /**
     * Sync all permissions.
     *
     * Synchronizes permissions for all entity types including
     * models, resources, pages, widgets, fields, columns, actions,
     * and relations. Also syncs the super admin role.
     *
     * @return array<string, array<string>> Sync operation log
     */
    public function syncAll(): array
    {
        $this->syncLog = [];
        $this->resourceModels = [];

        DB::transaction(function (): void {
            $this->syncModelPermissions();
            $this->syncResourcePermissions();
            $this->syncPagePermissions();
            $this->syncWidgetPermissions();
            $this->syncFieldPermissions();
            $this->syncColumnPermissions();
            $this->syncActionPermissions();
            $this->syncRelationPermissions();
            $this->syncSuperAdminRole();
        });

        return $this->syncLog;
    }

    /**
     * Sync only specific type of permissions.
     *
     * @param  string  $type  The permission type to sync (resources, models, pages, widgets, fields, columns, actions, relations)
     * @return array<string, array<string>> Sync operation log
     */
    public function syncOnly(string $type): array
    {
        $this->syncLog = [];
        $this->resourceModels = [];

        DB::transaction(function () use ($type): void {
            match ($type) {
                'resources' => $this->syncResourcePermissions(),
                'models' => $this->syncModelPermissions(),
                'pages' => $this->syncPagePermissions(),
                'widgets' => $this->syncWidgetPermissions(),
                'fields' => $this->syncFieldPermissions(),
                'columns' => $this->syncColumnPermissions(),
                'actions' => $this->syncActionPermissions(),
                'relations' => $this->syncRelationPermissions(),
                default => null,
            };
        });

        return $this->syncLog;
    }

    /**
     * Sync resource permissions.
     *
     * Creates permissions for all discovered Filament resources
     * using configured permission prefixes.
     */
    public function syncResourcePermissions(): void
    {
        $resources = $this->resourceDiscovery->discover();
        $prefixes = config('gatekeeper.permission_prefixes.resource', []);

        $this->resourceModels = $resources;

        foreach ($resources as $resource) {
            foreach ($prefixes as $prefix) {
                foreach ($this->guards as $guard) {
                    $permissionName = $this->generatePermissionName($prefix, $resource);
                    $this->createOrUpdatePermission($permissionName, $guard, Permission::TYPE_RESOURCE, $this->toSnakeCase($resource));
                }
            }
        }
    }

    /**
     * Sync model permissions for all discovered models.
     *
     * Creates permissions with TYPE_MODEL for API/standalone usage.
     * Only runs if model discovery is enabled in configuration.
     */
    public function syncModelPermissions(): void
    {
        if (! config('gatekeeper.discovery.discover_models', false)) {
            return;
        }

        $allModels = $this->modelDiscovery->discover();
        $excludedModels = config('gatekeeper.excluded_models', []);
        $prefixes = config('gatekeeper.permission_prefixes.model', [
            'view_any',
            'view',
            'create',
            'update',
            'delete',
        ]);

        $excludedLower = array_map(strtolower(...), $excludedModels);
        $modelsToSync = array_filter($allModels, fn (string $model): bool => ! in_array(strtolower($model), $excludedLower, true));

        foreach ($modelsToSync as $model) {
            foreach ($prefixes as $prefix) {
                foreach ($this->guards as $guard) {
                    $permissionName = $this->generatePermissionName($prefix, $model);
                    $this->createOrUpdatePermission($permissionName, $guard, Permission::TYPE_MODEL, $this->toSnakeCase($model));
                }
            }
        }
    }

    /**
     * Sync page permissions.
     *
     * Creates view permissions for all discovered Filament pages.
     */
    public function syncPagePermissions(): void
    {
        $pages = $this->pageDiscovery->discover();
        $prefixes = config('gatekeeper.permission_prefixes.page', ['view']);

        foreach ($pages as $page) {
            foreach ($prefixes as $prefix) {
                foreach ($this->guards as $guard) {
                    $permissionName = "{$prefix}_page_{$this->toSnakeCase($page)}";
                    $this->createOrUpdatePermission($permissionName, $guard, Permission::TYPE_PAGE, $this->toSnakeCase($page));
                }
            }
        }
    }

    /**
     * Sync widget permissions.
     *
     * Creates view permissions for all discovered Filament widgets.
     */
    public function syncWidgetPermissions(): void
    {
        $widgets = $this->widgetDiscovery->discover();
        $prefixes = config('gatekeeper.permission_prefixes.widget', ['view']);

        foreach ($widgets as $widget) {
            foreach ($prefixes as $prefix) {
                foreach ($this->guards as $guard) {
                    $permissionName = "{$prefix}_widget_{$this->toSnakeCase($widget)}";
                    $this->createOrUpdatePermission($permissionName, $guard, Permission::TYPE_WIDGET, $this->toSnakeCase($widget));
                }
            }
        }
    }

    /**
     * Sync field permissions.
     *
     * Creates view and update permissions for fields based on
     * configuration or auto-discovery settings.
     */
    public function syncFieldPermissions(): void
    {
        $prefixes = config('gatekeeper.permission_prefixes.field', ['view', 'update']);

        $fieldPermissions = $this->getFieldsForSync();

        foreach ($fieldPermissions as $model => $fields) {
            if ($model === '*') {
                continue;
            }

            foreach ($fields as $field) {
                foreach ($prefixes as $prefix) {
                    foreach ($this->guards as $guard) {
                        $permissionName = "{$prefix}_{$this->toSnakeCase($model)}_{$field}_field";
                        $this->createOrUpdatePermission($permissionName, $guard, Permission::TYPE_FIELD, $this->toSnakeCase($model));
                    }
                }
            }
        }
    }

    /**
     * Sync column permissions.
     *
     * Creates view permissions for table columns based on
     * configuration or auto-discovery settings.
     */
    public function syncColumnPermissions(): void
    {
        $prefixes = config('gatekeeper.permission_prefixes.column', ['view']);

        $columnPermissions = $this->getColumnsForSync();

        foreach ($columnPermissions as $model => $columns) {
            if ($model === '*') {
                continue;
            }

            foreach ($columns as $column) {
                foreach ($prefixes as $prefix) {
                    foreach ($this->guards as $guard) {
                        $permissionName = "{$prefix}_{$this->toSnakeCase($model)}_{$column}_column";
                        $this->createOrUpdatePermission($permissionName, $guard, Permission::TYPE_COLUMN, $this->toSnakeCase($model));
                    }
                }
            }
        }
    }

    /**
     * Sync action permissions.
     *
     * Creates execute permissions for custom actions defined in configuration.
     */
    public function syncActionPermissions(): void
    {
        $customActions = config('gatekeeper.custom_actions', []);
        $prefixes = config('gatekeeper.permission_prefixes.action', ['execute']);

        foreach ($customActions as $model => $actions) {
            if ($model === '*') {
                continue;
            }

            foreach ($actions as $action) {
                foreach ($prefixes as $prefix) {
                    foreach ($this->guards as $guard) {
                        $permissionName = "{$prefix}_{$this->toSnakeCase($model)}_{$action}_action";
                        $this->createOrUpdatePermission($permissionName, $guard, Permission::TYPE_ACTION, $this->toSnakeCase($model));
                    }
                }
            }
        }
    }

    /**
     * Sync relation permissions.
     *
     * Creates view permissions for relation managers defined in configuration.
     */
    public function syncRelationPermissions(): void
    {
        $relationPermissions = config('gatekeeper.relation_permissions', []);
        $prefixes = config('gatekeeper.permission_prefixes.relation', ['view']);

        foreach ($relationPermissions as $model => $relations) {
            if ($model === '*') {
                continue;
            }

            foreach ($relations as $relation) {
                foreach ($prefixes as $prefix) {
                    foreach ($this->guards as $guard) {
                        $permissionName = "{$prefix}_{$this->toSnakeCase($model)}_{$relation}_relation";
                        $this->createOrUpdatePermission($permissionName, $guard, Permission::TYPE_RELATION, $this->toSnakeCase($model));
                    }
                }
            }
        }
    }

    /**
     * Sync super admin role with all permissions.
     *
     * Creates the super admin role if it doesn't exist and
     * syncs all permissions to it.
     */
    public function syncSuperAdminRole(): void
    {
        if (! config('gatekeeper.super_admin.enabled', true)) {
            return;
        }

        $superAdminRole = config('gatekeeper.super_admin.role', 'super-admin');

        foreach ($this->guards as $guard) {
            if ($this->dryRun) {
                $this->log('super_admin', "Would create/sync role: {$superAdminRole} ({$guard})");

                continue;
            }

            $role = Role::firstOrCreate([
                'name' => $superAdminRole,
                'guard_name' => $guard,
            ]);

            $permissions = Permission::where('guard_name', $guard)->get();
            $role->syncPermissions($permissions);

            $this->log('super_admin', "Synced {$permissions->count()} permissions to {$superAdminRole} ({$guard})");
        }
    }

    /**
     * Get fields for synchronization.
     *
     * Retrieves fields from configuration and optionally from
     * auto-discovery based on settings.
     *
     * @return array<string, array<string>> Model => fields array
     */
    protected function getFieldsForSync(): array
    {
        $configFields = config('gatekeeper.field_permissions', []);

        if (! config('gatekeeper.field_discovery.enabled', false)) {
            return $configFields;
        }

        $models = $this->getModelsForFieldDiscovery();

        foreach ($models as $modelClass) {
            $modelName = class_basename($modelClass);

            if (isset($configFields[$modelName])) {
                continue;
            }

            $discoveredFields = $this->fieldDiscovery->discoverForModel($modelClass);

            if ($discoveredFields !== []) {
                $configFields[$modelName] = $discoveredFields;
            }
        }

        return $configFields;
    }

    /**
     * Get columns for synchronization.
     *
     * Retrieves columns from configuration and optionally from
     * auto-discovery based on settings.
     *
     * @return array<string, array<string>> Model => columns array
     */
    protected function getColumnsForSync(): array
    {
        $configColumns = config('gatekeeper.column_permissions', []);

        if (! config('gatekeeper.column_discovery.enabled', false)) {
            return $configColumns;
        }

        $models = $this->getModelsForColumnDiscovery();

        foreach ($models as $modelClass) {
            $modelName = class_basename($modelClass);

            if (isset($configColumns[$modelName])) {
                continue;
            }

            $discoveredColumns = $this->columnDiscovery->discoverForModel($modelClass);

            if ($discoveredColumns !== []) {
                $configColumns[$modelName] = $discoveredColumns;
            }
        }

        return $configColumns;
    }

    /**
     * Get models for field discovery.
     *
     * Returns the list of model classes to scan for field discovery.
     *
     * @return array<string> List of fully qualified model class names
     */
    protected function getModelsForFieldDiscovery(): array
    {
        $models = [];
        $modelPaths = config('gatekeeper.discovery.models', ['app/Models']);

        foreach ($modelPaths as $path) {
            $fullPath = base_path($path);

            if (! is_dir($fullPath)) {
                continue;
            }

            $files = glob($fullPath.'/*.php');

            foreach ($files as $file) {
                $className = 'App\\Models\\'.pathinfo($file, PATHINFO_FILENAME);

                if (class_exists($className)) {
                    $models[] = $className;
                }
            }
        }

        return $models;
    }

    /**
     * Get models for column discovery.
     *
     * Returns the list of model classes to scan for column discovery.
     *
     * @return array<string> List of fully qualified model class names
     */
    protected function getModelsForColumnDiscovery(): array
    {
        return $this->getModelsForFieldDiscovery();
    }

    /**
     * Delete field permissions for a specific model.
     *
     * Removes all field-related permissions for the given model.
     *
     * @param  string  $modelName  The model name (e.g., 'User')
     * @param  array<string>|null  $fields  Specific fields to delete, or null for all
     * @param  string|null  $guard  Specific guard, or null for all guards
     * @return int Number of deleted permissions
     *
     * @example
     * ```php
     * // Delete all field permissions for User model
     * $registrar->deleteFieldPermissions('User');
     *
     * // Delete specific field permissions
     * $registrar->deleteFieldPermissions('User', ['email', 'salary']);
     *
     * // Delete for specific guard
     * $registrar->deleteFieldPermissions('User', null, 'web');
     * ```
     */
    public function deleteFieldPermissions(string $modelName, ?array $fields = null, ?string $guard = null): int
    {
        $modelSnake = $this->toSnakeCase($modelName);

        $query = Permission::where('type', Permission::TYPE_FIELD)
            ->where(function ($q) use ($modelSnake): void {
                $q->where('entity', $modelSnake)
                    ->orWhere('name', 'like', "%_{$modelSnake}_%_field");
            });

        if ($guard) {
            $query->where('guard_name', $guard);
        }

        if ($fields) {
            $query->where(function ($q) use ($fields, $modelSnake): void {
                foreach ($fields as $field) {
                    $q->orWhere('name', 'like', "%_{$modelSnake}_{$field}_field");
                }
            });
        }

        $count = $query->count();

        if (! $this->dryRun) {
            $query->delete();
            $this->log('delete_field', "Deleted {$count} field permissions for {$modelName}");
        } else {
            $this->log('delete_field', "Would delete {$count} field permissions for {$modelName}");
        }

        return $count;
    }

    /**
     * Delete column permissions for a specific model.
     *
     * Removes all column-related permissions for the given model.
     *
     * @param  string  $modelName  The model name (e.g., 'User')
     * @param  array<string>|null  $columns  Specific columns to delete, or null for all
     * @param  string|null  $guard  Specific guard, or null for all guards
     * @return int Number of deleted permissions
     *
     * @example
     * ```php
     * // Delete all column permissions for User model
     * $registrar->deleteColumnPermissions('User');
     *
     * // Delete specific column permissions
     * $registrar->deleteColumnPermissions('User', ['salary', 'ssn']);
     *
     * // Delete for specific guard
     * $registrar->deleteColumnPermissions('User', null, 'api');
     * ```
     */
    public function deleteColumnPermissions(string $modelName, ?array $columns = null, ?string $guard = null): int
    {
        $modelSnake = $this->toSnakeCase($modelName);

        $query = Permission::where('type', Permission::TYPE_COLUMN)
            ->where(function ($q) use ($modelSnake): void {
                $q->where('entity', $modelSnake)
                    ->orWhere('name', 'like', "%_{$modelSnake}_%_column");
            });

        if ($guard) {
            $query->where('guard_name', $guard);
        }

        if ($columns) {
            $query->where(function ($q) use ($columns, $modelSnake): void {
                foreach ($columns as $column) {
                    $q->orWhere('name', 'like', "%_{$modelSnake}_{$column}_column");
                }
            });
        }

        $count = $query->count();

        if (! $this->dryRun) {
            $query->delete();
            $this->log('delete_column', "Deleted {$count} column permissions for {$modelName}");
        } else {
            $this->log('delete_column', "Would delete {$count} column permissions for {$modelName}");
        }

        return $count;
    }

    /**
     * Delete all permissions for a specific model.
     *
     * Removes all permission types (resource, field, column, action, relation)
     * for the given model.
     *
     * @param  string  $modelName  The model name (e.g., 'User')
     * @param  string|null  $guard  Specific guard, or null for all guards
     * @return int Total number of deleted permissions
     */
    public function deleteModelPermissions(string $modelName, ?string $guard = null): int
    {
        $modelSnake = $this->toSnakeCase($modelName);
        $count = 0;

        $query = Permission::where(function ($q) use ($modelSnake): void {
            $q->where('entity', $modelSnake)
                ->orWhere('name', 'like', "%_{$modelSnake}")
                ->orWhere('name', 'like', "%_{$modelSnake}_%");
        });

        if ($guard) {
            $query->where('guard_name', $guard);
        }

        $count = $query->count();

        if (! $this->dryRun) {
            $query->delete();
            $this->log('delete_model', "Deleted {$count} permissions for {$modelName}");
        } else {
            $this->log('delete_model', "Would delete {$count} permissions for {$modelName}");
        }

        return $count;
    }

    /**
     * Delete orphaned permissions.
     *
     * Removes permissions that are no longer associated with any
     * discovered entity (resource, page, widget, model).
     *
     * @return int Number of deleted orphaned permissions
     */
    public function deleteOrphanedPermissions(): int
    {
        $validPermissions = [];

        $resources = $this->resourceDiscovery->discover();
        $pages = $this->pageDiscovery->discover();
        $widgets = $this->widgetDiscovery->discover();

        foreach ($resources as $resource) {
            foreach (config('gatekeeper.permission_prefixes.resource', []) as $prefix) {
                $validPermissions[] = $this->generatePermissionName($prefix, $resource);
            }
        }

        foreach ($pages as $page) {
            foreach (config('gatekeeper.permission_prefixes.page', ['view']) as $prefix) {
                $validPermissions[] = "{$prefix}_page_{$this->toSnakeCase($page)}";
            }
        }

        foreach ($widgets as $widget) {
            foreach (config('gatekeeper.permission_prefixes.widget', ['view']) as $prefix) {
                $validPermissions[] = "{$prefix}_widget_{$this->toSnakeCase($widget)}";
            }
        }

        $orphaned = Permission::whereIn('type', [
            Permission::TYPE_RESOURCE,
            Permission::TYPE_PAGE,
            Permission::TYPE_WIDGET,
        ])->whereNotIn('name', $validPermissions)->get();

        $count = $orphaned->count();

        if (! $this->dryRun && $count > 0) {
            Permission::whereIn('id', $orphaned->pluck('id'))->delete();
            $this->log('delete_orphaned', "Deleted {$count} orphaned permissions");
        } else {
            $this->log('delete_orphaned', "Would delete {$count} orphaned permissions");
        }

        return $count;
    }

    /**
     * Create or update a permission.
     *
     * @param  string  $name  Permission name
     * @param  string  $guard  Guard name
     * @param  string  $type  Permission type
     * @param  string|null  $entity  Entity identifier (e.g. user, product) for grouping
     */
    public function createOrUpdatePermission(string $name, string $guard, string $type, ?string $entity = null): void
    {
        if ($this->dryRun) {
            $this->log($type, "Would create: {$name} ({$guard})");

            return;
        }

        Permission::updateOrCreate(
            ['name' => $name, 'guard_name' => $guard],
            ['type' => $type, 'entity' => $entity]
        );

        $this->log($type, "Created/Updated: {$name} ({$guard})");
    }

    /**
     * Generate permission name.
     *
     * @param  string  $prefix  Permission prefix
     * @param  string  $model  Model name
     * @return string Generated permission name
     */
    protected function generatePermissionName(string $prefix, string $model): string
    {
        $separator = config('gatekeeper.generator.separator', '_');
        $modelName = $this->toSnakeCase($model);

        return "{$prefix}{$separator}{$modelName}";
    }

    /**
     * Convert string to snake_case.
     *
     * @param  string  $value  Value to convert
     * @return string Snake case value
     */
    protected function toSnakeCase(string $value): string
    {
        if (config('gatekeeper.generator.snake_case', true)) {
            return str($value)->snake()->toString();
        }

        return str($value)->camel()->toString();
    }

    /**
     * Log a sync action.
     *
     * @param  string  $type  Log category
     * @param  string  $message  Log message
     */
    protected function log(string $type, string $message): void
    {
        if (! isset($this->syncLog[$type])) {
            $this->syncLog[$type] = [];
        }

        $this->syncLog[$type][] = $message;
    }

    /**
     * Get the sync log.
     *
     * @return array<string, array<string>> Sync operation log
     */
    public function getSyncLog(): array
    {
        return $this->syncLog;
    }

    /**
     * Get permission prefixes for a specific type.
     *
     * @param  string  $type  Permission type
     * @return array<string> List of prefixes
     */
    public function getPermissionPrefixes(string $type): array
    {
        return config("gatekeeper.permission_prefixes.{$type}", []);
    }

    /**
     * Get the field discovery service.
     */
    public function getFieldDiscovery(): FieldDiscovery
    {
        return $this->fieldDiscovery;
    }

    /**
     * Get the column discovery service.
     */
    public function getColumnDiscovery(): ColumnDiscovery
    {
        return $this->columnDiscovery;
    }
}
