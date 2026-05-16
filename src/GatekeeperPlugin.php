<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper;

use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;
use LaraArabDev\FilamentGatekeeper\Resources\PermissionResource;
use LaraArabDev\FilamentGatekeeper\Resources\RoleResource;

/**
 * Gatekeeper Plugin
 * This class is responsible for registering the Gatekeeper plugin and its resources.
 */
class GatekeeperPlugin implements Plugin
{
    protected string $superAdminRole = 'super-admin';

    protected bool $bypassForSuperAdmin = true;

    protected bool $fieldPermissionsEnabled = true;

    protected bool $columnPermissionsEnabled = true;

    protected bool $actionPermissionsEnabled = true;

    protected bool $relationPermissionsEnabled = true;

    protected string $navigationGroup = 'Access Control';

    protected string $navigationIcon = 'heroicon-o-shield-check';

    protected int $navigationSort = 1;

    protected bool $roleResourceEnabled = true;

    protected bool $permissionResourceEnabled = true;

    /** @var array<string> */
    protected array $panels = [];

    /** @var array<string> */
    protected array $guards = ['web'];

    protected ?Closure $modifyRoleResourceUsing = null;

    protected ?Closure $modifyPermissionResourceUsing = null;

    /**
     * Get the plugin ID.
     */
    public function getId(): string
    {
        return 'gatekeeper';
    }

    /**
     * Make a new instance of the plugin.
     */
    public static function make(): static
    {
        return app(static::class);
    }

    /**
     * Get the plugin instance.
     */
    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    /**
     * Register the plugin resources.
     */
    public function register(Panel $context): void
    {
        $resources = [];

        if ($this->roleResourceEnabled) {
            $resources[] = RoleResource::class;
        }

        if ($this->permissionResourceEnabled) {
            $resources[] = PermissionResource::class;
        }

        $context->resources($resources);
    }

    /**
     * Boot the plugin.
     */
    public function boot(Panel $context): void {}

    /**
     * Set the super admin role name.
     */
    public function superAdminRole(string $role): static
    {
        $this->superAdminRole = $role;

        return $this;
    }

    /**
     * Get the super admin role name.
     */
    public function getSuperAdminRole(): string
    {
        return $this->superAdminRole;
    }

    /**
     * Enable or disable super admin bypass.
     */
    public function bypassForSuperAdmin(bool $bypass = true): static
    {
        $this->bypassForSuperAdmin = $bypass;

        return $this;
    }

    /**
     * Check if super admin bypass is enabled.
     */
    public function isBypassForSuperAdminEnabled(): bool
    {
        return $this->bypassForSuperAdmin;
    }

    /**
     * Alias for isBypassForSuperAdminEnabled().
     */
    public function shouldBypassForSuperAdmin(): bool
    {
        return $this->isBypassForSuperAdminEnabled();
    }

    /**
     * Enable field permissions.
     */
    public function enableFieldPermissions(bool $enable = true): static
    {
        $this->fieldPermissionsEnabled = $enable;

        return $this;
    }

    /**
     * Check if field permissions are enabled.
     */
    public function isFieldPermissionsEnabled(): bool
    {
        return $this->fieldPermissionsEnabled;
    }

    /**
     * Alias for isFieldPermissionsEnabled().
     */
    public function hasFieldPermissions(): bool
    {
        return $this->isFieldPermissionsEnabled();
    }

    /**
     * Enable column permissions.
     */
    public function enableColumnPermissions(bool $enable = true): static
    {
        $this->columnPermissionsEnabled = $enable;

        return $this;
    }

    /**
     * Check if column permissions are enabled.
     */
    public function isColumnPermissionsEnabled(): bool
    {
        return $this->columnPermissionsEnabled;
    }

    /**
     * Alias for isColumnPermissionsEnabled().
     */
    public function hasColumnPermissions(): bool
    {
        return $this->isColumnPermissionsEnabled();
    }

    /**
     * Enable action permissions.
     */
    public function enableActionPermissions(bool $enable = true): static
    {
        $this->actionPermissionsEnabled = $enable;

        return $this;
    }

    /**
     * Check if action permissions are enabled.
     */
    public function isActionPermissionsEnabled(): bool
    {
        return $this->actionPermissionsEnabled;
    }

    /**
     * Alias for isActionPermissionsEnabled().
     */
    public function hasActionPermissions(): bool
    {
        return $this->isActionPermissionsEnabled();
    }

    /**
     * Enable relation permissions.
     */
    public function enableRelationPermissions(bool $enable = true): static
    {
        $this->relationPermissionsEnabled = $enable;

        return $this;
    }

    /**
     * Check if relation permissions are enabled.
     */
    public function isRelationPermissionsEnabled(): bool
    {
        return $this->relationPermissionsEnabled;
    }

    /**
     * Alias for isRelationPermissionsEnabled().
     */
    public function hasRelationPermissions(): bool
    {
        return $this->isRelationPermissionsEnabled();
    }

    /**
     * Set the navigation group.
     */
    public function navigationGroup(string $group): static
    {
        $this->navigationGroup = $group;

        return $this;
    }

    /**
     * Get the navigation group.
     */
    public function getNavigationGroup(): string
    {
        return $this->navigationGroup;
    }

    /**
     * Set the navigation icon.
     */
    public function navigationIcon(string $icon): static
    {
        $this->navigationIcon = $icon;

        return $this;
    }

    /**
     * Get the navigation icon.
     */
    public function getNavigationIcon(): string
    {
        return $this->navigationIcon;
    }

    /**
     * Set the navigation sort order.
     */
    public function navigationSort(int $sort): static
    {
        $this->navigationSort = $sort;

        return $this;
    }

    /**
     * Get the navigation sort order.
     */
    public function getNavigationSort(): int
    {
        return $this->navigationSort;
    }

    /**
     * Enable or disable the Role resource.
     */
    public function roleResource(bool $enabled = true): static
    {
        $this->roleResourceEnabled = $enabled;

        return $this;
    }

    /**
     * Check if Role resource is enabled.
     */
    public function isRoleResourceEnabled(): bool
    {
        return $this->roleResourceEnabled;
    }

    /**
     * Alias for isRoleResourceEnabled().
     */
    public function hasRoleResource(): bool
    {
        return $this->isRoleResourceEnabled();
    }

    /**
     * Enable or disable the Permission resource.
     */
    public function permissionResource(bool $enabled = true): static
    {
        $this->permissionResourceEnabled = $enabled;

        return $this;
    }

    /**
     * Check if Permission resource is enabled.
     */
    public function isPermissionResourceEnabled(): bool
    {
        return $this->permissionResourceEnabled;
    }

    /**
     * Alias for isPermissionResourceEnabled().
     */
    public function hasPermissionResource(): bool
    {
        return $this->isPermissionResourceEnabled();
    }

    /**
     * Set the panels to enable Gatekeeper on.
     *
     * @param  array<string>  $panels
     */
    public function panels(array $panels): static
    {
        $this->panels = $panels;

        return $this;
    }

    /**
     * Get the panels.
     *
     * @return array<string>
     */
    public function getPanels(): array
    {
        return $this->panels;
    }

    /**
     * Set the guards to use.
     *
     * @param  array<string>  $guards
     */
    public function guards(array $guards): static
    {
        $this->guards = $guards;

        return $this;
    }

    /**
     * Get the guards.
     *
     * @return array<string>
     */
    public function getGuards(): array
    {
        return $this->guards;
    }

    /**
     * Modify the Role resource using a callback.
     */
    public function modifyRoleResourceUsing(?Closure $callback): static
    {
        $this->modifyRoleResourceUsing = $callback;

        return $this;
    }

    /**
     * Get the Role resource modifier callback.
     */
    public function getModifyRoleResourceUsing(): ?Closure
    {
        return $this->modifyRoleResourceUsing;
    }

    /**
     * Modify the Permission resource using a callback.
     */
    public function modifyPermissionResourceUsing(?Closure $callback): static
    {
        $this->modifyPermissionResourceUsing = $callback;

        return $this;
    }

    /**
     * Get the Permission resource modifier callback.
     */
    public function getModifyPermissionResourceUsing(): ?Closure
    {
        return $this->modifyPermissionResourceUsing;
    }
}
