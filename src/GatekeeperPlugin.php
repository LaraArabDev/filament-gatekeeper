<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper;

use Closure;
use Filament\Contracts\Plugin;
use Filament\Context;
use Filament\Panel;
use LaraArabDev\FilamentGatekeeper\Resources\PermissionResource;
use LaraArabDev\FilamentGatekeeper\Resources\RoleResource;

/**
 * Gatekeeper Plugin
 * This class is responsible for registering the Gatekeeper plugin and its resources.
 */
class GatekeeperPlugin implements Plugin
{
    /** @var string*/
    protected string $superAdminRole = 'super-admin';

    /** @var bool*/
    protected bool $bypassForSuperAdmin = true;

    /** @var bool*/
    protected bool $fieldPermissionsEnabled = true;

    /** @var bool*/
    protected bool $columnPermissionsEnabled = true;

    /** @var bool*/
    protected bool $actionPermissionsEnabled = true;

    /** @var bool*/
    protected bool $relationPermissionsEnabled = true;

    /** @var string*/
    protected string $navigationGroup = 'Access Control';

    /** @var string*/
    protected string $navigationIcon = 'heroicon-o-shield-check';

    /** @var int*/
    protected int $navigationSort = 1;

    /** @var bool*/
    protected bool $roleResourceEnabled = true;

    /** @var bool*/
    protected bool $permissionResourceEnabled = true;

    /** @var array<string>*/
    protected array $panels = [];

    /** @var array<string> */
    protected array $guards = ['web'];

    /** @var ?Closure*/
    protected ?Closure $modifyRoleResourceUsing = null;

    /** @var ?Closure*/
    protected ?Closure $modifyPermissionResourceUsing = null;

    /**
     * Get the plugin ID.
     * @return string
     */
    public function getId(): string
    {
        return 'gatekeeper';
    }

    /**
     * Make a new instance of the plugin.
     * @return static
     */
    public static function make(): static
    {
        return app(static::class);
    }

    /**
     * Get the plugin instance.
     * @return static
     */
    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    /**
     * Register the plugin resources.
     * @param Context|Panel $context
     * @return void
     */
    public function register(Context|Panel $context): void
    {
        $resources = [];

        if ($this->roleResourceEnabled) {
            $resources[] = RoleResource::class;
        }

        if ($this->permissionResourceEnabled) {
            $resources[] = PermissionResource::class;
        }

        if (method_exists($context, 'resources')) {
            $context->resources($resources);
        }
    }

    /**
     * Boot the plugin.
     * @param Context|Panel $context
     * @return void
     */
    public function boot(Context|Panel $context): void {}

    /**
     * Set the super admin role name.
     * @param string $role
     * @return static
     */
    public function superAdminRole(string $role): static
    {
        $this->superAdminRole = $role;

        return $this;
    }

    /**
     * Get the super admin role name.
     * @return string
     */
    public function getSuperAdminRole(): string
    {
        return $this->superAdminRole;
    }

    /**
     * Enable or disable super admin bypass.
     * @param bool $bypass
     * @return static
     */
    public function bypassForSuperAdmin(bool $bypass = true): static
    {
        $this->bypassForSuperAdmin = $bypass;

        return $this;
    }

    /**
     * Check if super admin bypass is enabled.
     * @return bool
     */
    public function isBypassForSuperAdminEnabled(): bool
    {
        return $this->bypassForSuperAdmin;
    }

    /**
     * Alias for isBypassForSuperAdminEnabled().
     * @return bool
     */
    public function shouldBypassForSuperAdmin(): bool
    {
        return $this->isBypassForSuperAdminEnabled();
    }

    /**
     * Enable field permissions.
     * @param bool $enable
     * @return static
     */
    public function enableFieldPermissions(bool $enable = true): static
    {
        $this->fieldPermissionsEnabled = $enable;

        return $this;
    }

    /**
     * Check if field permissions are enabled.
     * @return bool
     */
    public function isFieldPermissionsEnabled(): bool
    {
        return $this->fieldPermissionsEnabled;
    }

    /**
     * Alias for isFieldPermissionsEnabled().
     * @return bool
     */
    public function hasFieldPermissions(): bool
    {
        return $this->isFieldPermissionsEnabled();
    }

    /**
     * Enable column permissions.
     * @param bool $enable
     * @return static
     */
    public function enableColumnPermissions(bool $enable = true): static
    {
        $this->columnPermissionsEnabled = $enable;

        return $this;
    }

    /**
     * Check if column permissions are enabled.
     * @return bool
     */
    public function isColumnPermissionsEnabled(): bool
    {
        return $this->columnPermissionsEnabled;
    }

    /**
     * Alias for isColumnPermissionsEnabled().
     * @return bool
     */
    public function hasColumnPermissions(): bool
    {
        return $this->isColumnPermissionsEnabled();
    }

    /**
     * Enable action permissions.
     * @param bool $enable
     * @return static
     */
    public function enableActionPermissions(bool $enable = true): static
    {
        $this->actionPermissionsEnabled = $enable;

        return $this;
    }

    /**
     * Check if action permissions are enabled.
     * @return bool
     */
    public function isActionPermissionsEnabled(): bool
    {
        return $this->actionPermissionsEnabled;
    }

    /**
     * Alias for isActionPermissionsEnabled().
     * @return bool
     */
    public function hasActionPermissions(): bool
    {
        return $this->isActionPermissionsEnabled();
    }

    /**
     * Enable relation permissions.
     * @param bool $enable
     * @return static
     */
    public function enableRelationPermissions(bool $enable = true): static
    {
        $this->relationPermissionsEnabled = $enable;

        return $this;
    }

    /**
     * Check if relation permissions are enabled.
     * @return bool
     */
    public function isRelationPermissionsEnabled(): bool
    {
        return $this->relationPermissionsEnabled;
    }

    /**
     * Alias for isRelationPermissionsEnabled().
     * @return bool
     */
    public function hasRelationPermissions(): bool
    {
        return $this->isRelationPermissionsEnabled();
    }

    /**
     * Set the navigation group.
     * @param string $group
     * @return static
     */
    public function navigationGroup(string $group): static
    {
        $this->navigationGroup = $group;

        return $this;
    }

    /**
     * Get the navigation group.
     * @return string
     */
    public function getNavigationGroup(): string
    {
        return $this->navigationGroup;
    }

    /**
     * Set the navigation icon.
     * @param string $icon
     * @return static
     */
    public function navigationIcon(string $icon): static
    {
        $this->navigationIcon = $icon;

        return $this;
    }

    /**
     * Get the navigation icon.
     * @return string
     */
    public function getNavigationIcon(): string
    {
        return $this->navigationIcon;
    }

    /**
     * Set the navigation sort order.
     * @param int $sort
     * @return static
     */
    public function navigationSort(int $sort): static
    {
        $this->navigationSort = $sort;

        return $this;
    }

    /**
     * Get the navigation sort order.
     * @return int
     */
    public function getNavigationSort(): int
    {
        return $this->navigationSort;
    }

    /**
     * Enable or disable the Role resource.
     * @param bool $enabled
     * @return static
     */
    public function roleResource(bool $enabled = true): static
    {
        $this->roleResourceEnabled = $enabled;

        return $this;
    }

    /**
     * Check if Role resource is enabled.
     * @return bool
     */
    public function isRoleResourceEnabled(): bool
    {
        return $this->roleResourceEnabled;
    }

    /**
     * Alias for isRoleResourceEnabled().
     * @return bool
     */
    public function hasRoleResource(): bool
    {
        return $this->isRoleResourceEnabled();
    }

    /**
     * Enable or disable the Permission resource.
     * @param bool $enabled
     * @return static
     */
    public function permissionResource(bool $enabled = true): static
    {
        $this->permissionResourceEnabled = $enabled;

        return $this;
    }

    /**
     * Check if Permission resource is enabled.
     * @return bool
     */
    public function isPermissionResourceEnabled(): bool
    {
        return $this->permissionResourceEnabled;
    }

    /**
     * Alias for isPermissionResourceEnabled().
     * @return bool
     */
    public function hasPermissionResource(): bool
    {
        return $this->isPermissionResourceEnabled();
    }

    /**
     * Set the panels to enable Gatekeeper on.
     * @param array<string> $panels
     */
    public function panels(array $panels): static
    {
        $this->panels = $panels;

        return $this;
    }

    /**
     * Get the panels.
     * @return array<string>
     */
    public function getPanels(): array
    {
        return $this->panels;
    }

    /**
     * Set the guards to use.
     * @param array<string> $guards
     * @return static
     */
    public function guards(array $guards): static
    {
        $this->guards = $guards;

        return $this;
    }

    /**
     * Get the guards.
     * @return array<string>
     */
    public function getGuards(): array
    {
        return $this->guards;
    }

    /**
     * Modify the Role resource using a callback.
     * @param ?Closure $callback
     * @return static
     */
    public function modifyRoleResourceUsing(?Closure $callback): static
    {
        $this->modifyRoleResourceUsing = $callback;

        return $this;
    }

    /**
     * Get the Role resource modifier callback.
     * @return ?Closure
     */
    public function getModifyRoleResourceUsing(): ?Closure
    {
        return $this->modifyRoleResourceUsing;
    }

    /**
     * Modify the Permission resource using a callback.
     * @param ?Closure $callback
     * @return static
     */
    public function modifyPermissionResourceUsing(?Closure $callback): static
    {
        $this->modifyPermissionResourceUsing = $callback;

        return $this;
    }

    /**
     * Get the Permission resource modifier callback.
     * @return ?Closure
     */
    public function getModifyPermissionResourceUsing(): ?Closure
    {
        return $this->modifyPermissionResourceUsing;
    }
}
