<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * @property int $id
 * @property string $name
 * @property string $guard_name
 * @property array|null $field_permissions
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Permission> $permissions
 */
class Role extends SpatieRole
{
    use HasFactory;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): \LaraArabDev\FilamentGatekeeper\Database\Factories\RoleFactory
    {
        return \LaraArabDev\FilamentGatekeeper\Database\Factories\RoleFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'guard_name',
        'description',
        'field_permissions',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'field_permissions' => 'array',
    ];

    /**
     * Get the permissions relationship.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            config('permission.table_names.role_has_permissions'),
            'role_id',
            'permission_id'
        );
    }

    /**
     * Scope a query to only include roles for a specific guard.
     */
    public function scopeForGuard(Builder $query, string $guard): Builder
    {
        return $query->where('guard_name', $guard);
    }

    /**
     * Scope a query to exclude super admin role.
     */
    public function scopeWithoutSuperAdmin(Builder $query): Builder
    {
        $superAdminRole = config('gatekeeper.super_admin.role', 'super-admin');

        return $query->where('name', '!=', $superAdminRole);
    }

    /**
     * Check if this is the super admin role.
     */
    public function isSuperAdmin(): bool
    {
        $superAdminRole = config('gatekeeper.super_admin.role', 'super-admin');

        return $this->name === $superAdminRole;
    }

    /**
     * Get field permissions for a specific model.
     *
     * @return array<string, array<string, bool>>
     */
    public function getFieldPermissionsForModel(string $modelName): array
    {
        $permissions = $this->field_permissions ?? [];

        return $permissions[$modelName]['fields'] ?? [];
    }

    /**
     * Get column permissions for a specific model.
     *
     * @return array<string, bool>
     */
    public function getColumnPermissionsForModel(string $modelName): array
    {
        $permissions = $this->field_permissions ?? [];

        return $permissions[$modelName]['columns'] ?? [];
    }

    /**
     * Get action permissions for a specific model.
     *
     * @return array<string, bool>
     */
    public function getActionPermissionsForModel(string $modelName): array
    {
        $permissions = $this->field_permissions ?? [];

        return $permissions[$modelName]['actions'] ?? [];
    }

    /**
     * Get relation permissions for a specific model.
     *
     * @return array<string, array<string>>
     */
    public function getRelationPermissionsForModel(string $modelName): array
    {
        $permissions = $this->field_permissions ?? [];

        return $permissions[$modelName]['relations'] ?? [];
    }

    /**
     * Set field permissions for a specific model.
     *
     * @param  array<string, array<string, bool>>  $fieldPermissions
     */
    public function setFieldPermissionsForModel(string $modelName, array $fieldPermissions): static
    {
        $permissions = $this->field_permissions ?? [];
        $permissions[$modelName]['fields'] = $fieldPermissions;
        $this->field_permissions = $permissions;

        return $this;
    }

    /**
     * Set column permissions for a specific model.
     *
     * @param  array<string, bool>  $columnPermissions
     */
    public function setColumnPermissionsForModel(string $modelName, array $columnPermissions): static
    {
        $permissions = $this->field_permissions ?? [];
        $permissions[$modelName]['columns'] = $columnPermissions;
        $this->field_permissions = $permissions;

        return $this;
    }

    /**
     * Set action permissions for a specific model.
     *
     * @param  array<string, bool>  $actionPermissions
     */
    public function setActionPermissionsForModel(string $modelName, array $actionPermissions): static
    {
        $permissions = $this->field_permissions ?? [];
        $permissions[$modelName]['actions'] = $actionPermissions;
        $this->field_permissions = $permissions;

        return $this;
    }

    /**
     * Set relation permissions for a specific model.
     *
     * @param  array<string, array<string>>  $relationPermissions
     */
    public function setRelationPermissionsForModel(string $modelName, array $relationPermissions): static
    {
        $permissions = $this->field_permissions ?? [];
        $permissions[$modelName]['relations'] = $relationPermissions;
        $this->field_permissions = $permissions;

        return $this;
    }

    /**
     * Check if role has field permission.
     */
    public function hasFieldPermission(string $modelName, string $field, string $action = 'view'): bool
    {
        $fieldPermissions = $this->getFieldPermissionsForModel($modelName);

        return $fieldPermissions[$field][$action] ?? true;
    }

    /**
     * Check if role has column permission.
     */
    public function hasColumnPermission(string $modelName, string $column): bool
    {
        $columnPermissions = $this->getColumnPermissionsForModel($modelName);

        return $columnPermissions[$column] ?? true;
    }

    /**
     * Check if role has action permission.
     */
    public function hasActionPermission(string $modelName, string $action): bool
    {
        $actionPermissions = $this->getActionPermissionsForModel($modelName);

        return $actionPermissions[$action] ?? false;
    }

    /**
     * Check if role has relation permission.
     */
    public function hasRelationPermission(string $modelName, string $relation, string $action = 'view'): bool
    {
        $relationPermissions = $this->getRelationPermissionsForModel($modelName);

        return in_array($action, $relationPermissions[$relation] ?? []);
    }

    /**
     * Get all model names with configured permissions.
     *
     * @return array<string>
     */
    public function getConfiguredModels(): array
    {
        return array_keys($this->field_permissions ?? []);
    }

    /**
     * Sync all permissions to this role.
     */
    public function syncAllPermissions(): static
    {
        $permissions = Permission::forGuard($this->guard_name)->get();
        $this->syncPermissions($permissions);

        return $this;
    }

    /**
     * Sync resource permissions only.
     */
    public function syncResourcePermissions(): static
    {
        $permissions = Permission::forGuard($this->guard_name)
            ->resources()
            ->get();
        $this->syncPermissions($permissions);

        return $this;
    }

    /**
     * Get permissions by type.
     *
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Collection<int, Permission>
     */
    public function getPermissionsByType(string $type): \Illuminate\Database\Eloquent\Collection
    {
        return $this->permissions()
            ->where('type', $type)
            ->get();
    }
}
