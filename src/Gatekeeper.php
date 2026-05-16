<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use LaraArabDev\FilamentGatekeeper\Services\PermissionCache;
use Spatie\Permission\Exceptions\GuardDoesNotMatch;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

/**
 * Core permission management service for Filament Gatekeeper.
 *
 * This class provides the main interface for checking permissions, managing
 * guards, and handling permission-related operations throughout the application.
 * It integrates with Spatie Laravel Permission and provides additional
 * functionality for field, column, action, and relation permissions.
 */
class Gatekeeper
{
    /**
     * The current guard name for permission checks.
     */
    protected ?string $guardName = null;

    /**
     * Create a new Gatekeeper instance.
     *
     * @param  PermissionCache  $cache  The permission cache service instance
     */
    public function __construct(
        protected PermissionCache $cache
    ) {}

    /**
     * Set the guard for permission checks.
     *
     * @param  string  $guardName  The guard name to use for permission checks
     * @return static Returns self for method chaining
     */
    public function guard(string $guardName): static
    {
        $this->guardName = $guardName;

        return $this;
    }

    /**
     * Get the current guard name.
     *
     * Returns the explicitly set guard name, or auto-detects from the
     * current request if no guard has been set.
     *
     * @return string The guard name to use for permission checks
     */
    public function getGuard(): string
    {
        if ($this->guardName) {
            return $this->guardName;
        }

        return $this->detectGuardFromRequest();
    }

    /**
     * Auto-detect the guard based on the current request.
     *
     * Detects the appropriate guard by checking for bearer tokens,
     * API route prefixes, or JSON expectations. Falls back to the
     * configured default guard.
     *
     * @return string The detected guard name
     */
    protected function detectGuardFromRequest(): string
    {
        $request = request();

        if ($request->bearerToken() || $request->is('api/*') || $request->expectsJson()) {
            return 'api';
        }

        return config('gatekeeper.guard', 'web');
    }

    /**
     * Use API guard explicitly.
     *
     * Convenience method to set the guard to 'api'.
     *
     * @return static Returns self for method chaining
     */
    public function api(): static
    {
        return $this->guard('api');
    }

    /**
     * Use web guard explicitly.
     */
    public function web(): static
    {
        return $this->guard('web');
    }

    /**
     * Get the current authenticated user.
     */
    public function user(): ?Authenticatable
    {
        return Auth::guard($this->getGuard())->user();
    }

    /**
     * Check if the current user has a specific permission.
     *
     * Supports OR logic: 'permission1|permission2' will return true if user
     * has any of the specified permissions.
     *
     * @param  string  $permission  Permission name or pipe-separated permissions (e.g., 'view_any_user|create_user')
     * @param  mixed  $record  Optional record for context
     * @return bool True if user has the permission(s), false otherwise
     */
    public function can(string $permission, mixed $record = null): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        if ($this->shouldBypassPermissions($user)) {
            return true;
        }

        if (str_contains($permission, '|')) {
            $permissions = array_map('trim', explode('|', $permission));

            foreach ($permissions as $perm) {
                if ($this->checkSinglePermission($user, $perm)) {
                    return true;
                }
            }

            return false;
        }

        return $this->checkSinglePermission($user, $permission);
    }

    /**
     * Check if user has a single permission.
     *
     * @param  Authenticatable  $user  The user to check
     * @param  string  $permission  The permission name to check
     * @return bool True if user has the permission, false otherwise
     */
    protected function checkSinglePermission(Authenticatable $user, string $permission): bool
    {
        $guard = $this->getGuard();

        if (method_exists($user, 'hasPermissionTo')) {
            try {
                return $user->hasPermissionTo($permission, $guard);
            } catch (PermissionDoesNotExist $e) {
                return false;
            } catch (\Exception) {
                return false;
            }
        }

        return $user->can($permission);
    }

    /**
     * Authorize a permission (throws exception if unauthorized).
     */
    public function authorize(string $permission, mixed $record = null): void
    {
        if (! $this->can($permission, $record)) {
            abort(403, __('gatekeeper::messages.unauthorized'));
        }
    }

    /**
     * Check if the current user is a super admin.
     *
     * @return bool True if user is super admin, false otherwise
     */
    public function isSuperAdmin(): bool
    {
        return $this->shouldBypassPermissions();
    }

    /**
     * Clear the permission cache.
     */
    public function clearCache(): void
    {
        $this->cache->flushAll();
    }

    /**
     * Check if the user should bypass permission checks.
     *
     * This method checks if the user has the super admin role configured
     * in the gatekeeper config. It works with any user model by:
     * 1. First trying to use hasRole() method if available (Spatie Permission trait)
     * 2. Falling back to direct database check if hasRole is not available
     *
     * @param  Authenticatable|null  $user  The user to check (defaults to current authenticated user)
     * @return bool True if user should bypass permissions, false otherwise
     */
    public function shouldBypassPermissions(?Authenticatable $user = null): bool
    {
        $user = $user ?? $this->user();

        if (! $user || ! config('gatekeeper.super_admin.enabled', true)) {
            return false;
        }

        $superAdminRole = config('gatekeeper.super_admin.role', 'super-admin');
        $guardsToCheck = $this->getGuardsToCheckForSuperAdmin($user);

        if (method_exists($user, 'hasRole')) {
            foreach ($guardsToCheck as $guard) {
                if ($this->userHasRole($user, $superAdminRole, $guard)) {
                    return true;
                }
            }
        }

        return $this->checkSuperAdminViaDatabase($user, $superAdminRole, $guardsToCheck);
    }

    /**
     * Get the list of guards to check for super admin role.
     *
     * Returns guards in priority order:
     * 1. Current Gatekeeper guard
     * 2. User's default guard (if different)
     * 3. Default 'web' guard as fallback
     *
     * @param  Authenticatable  $user  The user to check guards for
     * @return array<string> List of guard names to check
     */
    protected function getGuardsToCheckForSuperAdmin(Authenticatable $user): array
    {
        $guards = [$this->getGuard()];

        if (method_exists($user, 'getGuardName')) {
            $userGuard = $user->getGuardName();
            if ($userGuard !== $guards[0] && ! in_array($userGuard, $guards, true)) {
                $guards[] = $userGuard;
            }
        }

        if (! in_array('web', $guards, true)) {
            $guards[] = 'web';
        }

        return array_unique($guards);
    }

    /**
     * Check if user has a specific role for a given guard.
     *
     * Safely checks if the user has the role using hasRole() method,
     * catching any exceptions that might occur during the check.
     *
     * @param  Authenticatable  $user  The user to check (must have hasRole method)
     * @param  string  $roleName  The role name to check
     * @param  string|null  $guard  The guard to check (null uses user's default)
     * @return bool True if user has the role, false otherwise
     */
    protected function userHasRole(Authenticatable $user, string $roleName, ?string $guard = null): bool
    {
        if (! method_exists($user, 'hasRole')) {
            return false;
        }

        try {
            return $guard !== null
                ? $user->hasRole($roleName, $guard)
                : $user->hasRole($roleName);
        } catch (GuardDoesNotMatch|\Exception) {
            return false;
        }
    }

    /**
     * Check if user has super admin role via direct database query.
     *
     * This method works with any user model, even if it doesn't use
     * Spatie's HasRoles trait. It directly queries the database to
     * check if the user has the super admin role.
     *
     * @param  Authenticatable  $user  The user to check
     * @param  string  $superAdminRole  The super admin role name
     * @param  array<string>  $guards  List of guards to check
     * @return bool True if user has super admin role, false otherwise
     */
    protected function checkSuperAdminViaDatabase(
        Authenticatable $user,
        string $superAdminRole,
        array $guards
    ): bool {
        try {
            $userId = $user->getAuthIdentifier();
            $userClass = get_class($user);
            $modelHasRolesTable = config('permission.table_names.model_has_roles', 'model_has_roles');
            $rolesTable = config('permission.table_names.roles', 'roles');
            $schema = DB::getSchemaBuilder();

            if (! $schema->hasTable($rolesTable) || ! $schema->hasTable($modelHasRolesTable)) {
                return false;
            }

            return DB::table($modelHasRolesTable)
                ->join($rolesTable, "{$modelHasRolesTable}.role_id", '=', "{$rolesTable}.id")
                ->where("{$modelHasRolesTable}.model_type", $userClass)
                ->where("{$modelHasRolesTable}.model_id", $userId)
                ->where("{$rolesTable}.name", $superAdminRole)
                ->whereIn("{$rolesTable}.guard_name", $guards)
                ->exists();
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Get the permission cache instance.
     */
    public function cache(): PermissionCache
    {
        return $this->cache;
    }

    /**
     * Get the permission matrix for a user.
     *
     * @return array<string, mixed>
     */
    public function getPermissionMatrix(?Authenticatable $user = null): array
    {
        $user = $user ?? $this->user();

        if (! $user) {
            return [];
        }

        return $this->cache->getPermissionMatrix($user);
    }

    /**
     * Check if user can view a specific field.
     */
    public function canViewField(string $modelName, string $field): bool
    {
        if ($this->shouldBypassPermissions()) {
            return true;
        }

        $user = $this->user();

        if (! $user) {
            return false;
        }

        $prefixes = config('gatekeeper.permission_prefixes.field', ['view', 'update']);
        $prefix = $prefixes[0] ?? 'view';
        $modelSnake = str($modelName)->snake()->toString();
        $permissionName = "{$prefix}_{$modelSnake}_{$field}_field";

        if ($user->can($permissionName)) {
            return true;
        }

        $matrix = $this->getPermissionMatrix();

        return $matrix[$modelName]['fields'][$field]['view'] ?? false;
    }

    /**
     * Check if user can update a specific field.
     */
    public function canUpdateField(string $modelName, string $field): bool
    {
        if ($this->shouldBypassPermissions()) {
            return true;
        }

        $user = $this->user();

        if (! $user) {
            return false;
        }

        $prefixes = config('gatekeeper.permission_prefixes.field', ['view', 'update']);
        $prefix = $prefixes[1] ?? 'update';
        $modelSnake = str($modelName)->snake()->toString();
        $permissionName = "{$prefix}_{$modelSnake}_{$field}_field";

        if ($user->can($permissionName)) {
            return true;
        }

        $matrix = $this->getPermissionMatrix();

        return $matrix[$modelName]['fields'][$field]['update'] ?? false;
    }

    /**
     * Check if user can view a specific column.
     */
    public function canViewColumn(string $modelName, string $column): bool
    {
        if ($this->shouldBypassPermissions()) {
            return true;
        }

        $user = $this->user();

        if (! $user) {
            return false;
        }

        $prefixes = config('gatekeeper.permission_prefixes.column', ['view']);
        $prefix = $prefixes[0] ?? 'view';
        $modelSnake = str($modelName)->snake()->toString();
        $permissionName = "{$prefix}_{$modelSnake}_{$column}_column";
        $guard = $this->getGuard();

        if (method_exists($user, 'hasPermissionTo')) {
            try {
                if ($user->hasPermissionTo($permissionName, $guard)) {
                    return true;
                }
            } catch (PermissionDoesNotExist) {
            }
        }

        if ($user->can($permissionName)) {
            return true;
        }

        $matrix = $this->getPermissionMatrix();

        return $matrix[$modelName]['columns'][$column] ?? false;
    }

    /**
     * Check if user can execute a specific action.
     */
    public function canExecuteAction(string $modelName, string $action): bool
    {
        if ($this->shouldBypassPermissions()) {
            return true;
        }

        $user = $this->user();

        if (! $user) {
            return false;
        }

        $prefixes = config('gatekeeper.permission_prefixes.action', ['execute']);
        $prefix = $prefixes[0] ?? 'execute';
        $modelSnake = str($modelName)->snake()->toString();
        $permissionName = "{$prefix}_{$modelSnake}_{$action}_action";

        if ($user->can($permissionName)) {
            return true;
        }

        $matrix = $this->getPermissionMatrix();

        return $matrix[$modelName]['actions'][$action] ?? false;
    }

    /**
     * Check if user can view a specific relation.
     */
    public function canViewRelation(string $modelName, string $relation): bool
    {
        if ($this->shouldBypassPermissions()) {
            return true;
        }

        $user = $this->user();

        if (! $user) {
            return false;
        }

        $prefixes = config('gatekeeper.permission_prefixes.relation', ['view']);
        $prefix = $prefixes[0] ?? 'view';
        $modelSnake = str($modelName)->snake()->toString();
        $permissionName = "{$prefix}_{$modelSnake}_{$relation}_relation";
        $guard = $this->getGuard();

        if (method_exists($user, 'hasPermissionTo')) {
            try {
                if ($user->hasPermissionTo($permissionName, $guard)) {
                    return true;
                }
            } catch (PermissionDoesNotExist) {
            }
        }

        if ($user->can($permissionName)) {
            return true;
        }

        $matrix = $this->getPermissionMatrix();

        return in_array('view', $matrix[$modelName]['relations'][$relation] ?? []);
    }

    /**
     * Get all visible fields for a model.
     *
     * @return array<int, string>
     */
    public function getVisibleFields(string $modelName): array
    {
        if ($this->shouldBypassPermissions()) {
            $configuredFields = array_merge(
                config('gatekeeper.field_permissions.*', []),
                config("gatekeeper.field_permissions.{$modelName}", [])
            );

            return $configuredFields;
        }

        $user = $this->user();

        if (! $user) {
            return [];
        }

        $configuredFields = array_merge(
            config('gatekeeper.field_permissions.*', []),
            config("gatekeeper.field_permissions.{$modelName}", [])
        );

        if (empty($configuredFields)) {
            return [];
        }

        $visibleFields = [];
        $prefixes = config('gatekeeper.permission_prefixes.field', ['view', 'update']);
        $prefix = $prefixes[0] ?? 'view';
        $modelSnake = str($modelName)->snake()->toString();

        foreach ($configuredFields as $field) {
            $permissionName = "{$prefix}_{$modelSnake}_{$field}_field";

            if ($user->can($permissionName)) {
                $visibleFields[] = $field;
            }
        }

        if (empty($visibleFields)) {
            $matrix = $this->getPermissionMatrix();
            $fields = $matrix[$modelName]['fields'] ?? [];
            $visibleFields = array_keys(array_filter($fields, fn ($perms) => $perms['view'] ?? false));
        }

        return $visibleFields;
    }

    /**
     * Get all visible columns for a model.
     *
     * @return array<int, string>
     */
    public function getVisibleColumns(string $modelName): array
    {
        if ($this->shouldBypassPermissions()) {
            $configuredColumns = array_merge(
                config('gatekeeper.column_permissions.*', []),
                config("gatekeeper.column_permissions.{$modelName}", [])
            );

            return $configuredColumns;
        }

        $user = $this->user();

        if (! $user) {
            return [];
        }

        $configuredColumns = array_merge(
            config('gatekeeper.column_permissions.*', []),
            config("gatekeeper.column_permissions.{$modelName}", [])
        );

        if (empty($configuredColumns)) {
            return [];
        }

        $visibleColumns = [];
        $prefixes = config('gatekeeper.permission_prefixes.column', ['view']);
        $prefix = $prefixes[0] ?? 'view';
        $modelSnake = str($modelName)->snake()->toString();
        $guard = $this->getGuard();

        foreach ($configuredColumns as $column) {
            $permissionName = "{$prefix}_{$modelSnake}_{$column}_column";

            if (method_exists($user, 'hasPermissionTo')) {
                try {
                    if ($user->hasPermissionTo($permissionName, $guard)) {
                        $visibleColumns[] = $column;

                        continue;
                    }
                } catch (PermissionDoesNotExist) {
                }
            }

            if ($user->can($permissionName)) {
                $visibleColumns[] = $column;
            }
        }

        if (empty($visibleColumns)) {
            $matrix = $this->getPermissionMatrix();
            $columns = $matrix[$modelName]['columns'] ?? [];
            $visibleColumns = array_keys(array_filter($columns, fn ($value) => $value === true));
        }

        return $visibleColumns;
    }
}
