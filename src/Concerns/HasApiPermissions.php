<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Concerns;

use Illuminate\Database\Eloquent\Model;
use LaraArabDev\FilamentGatekeeper\Facades\Gatekeeper;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Trait HasApiPermissions
 *
 * Add this trait to your API controllers for automatic permission checking.
 *
 * Usage:
 * ```php
 * class UserController extends Controller
 * {
 *     use HasApiPermissions;
 *
 *     protected string $shieldModel = 'User';
 *
 *     public function index()
 *     {
 *         $this->authorizeResource('view_any');
 *         return User::all();
 *     }
 * }
 * ```
 */
trait HasApiPermissions
{
    /**
     * Get the model name for permission checks.
     * Override this in your controller or set $shieldModel or $permissionModel property.
     */
    protected function getGatekeeperModel(): string
    {
        // Support both $shieldModel and $permissionModel for backward compatibility
        if (property_exists($this, 'shieldModel') && isset($this->shieldModel)) {
            return $this->shieldModel;
        }

        if (property_exists($this, 'permissionModel') && isset($this->permissionModel)) {
            return $this->permissionModel;
        }

        // Try to extract from controller name (UserController -> User)
        $className = class_basename(static::class);

        return str($className)
            ->replace('Controller', '')
            ->replace('Api', '')
            ->toString();
    }

    /**
     * Get the guard for API permission checks.
     */
    protected function getShieldGuard(): string
    {
        if (property_exists($this, 'shieldGuard')) {
            return $this->shieldGuard;
        }

        return 'api';
    }

    /**
     * Authorize a resource action.
     *
     * @throws HttpException
     */
    protected function authorizeResource(string $action, ?Model $model = null): void
    {
        $permission = $this->buildPermissionName($action);

        Gatekeeper::guard($this->getShieldGuard())->authorize($permission, $model);
    }

    /**
     * Check if user can perform an action.
     */
    protected function canPerform(string $action, ?Model $model = null): bool
    {
        $permission = $this->buildPermissionName($action);

        return Gatekeeper::guard($this->getShieldGuard())->can($permission, $model);
    }

    /**
     * Check if user can view a field.
     */
    protected function canViewField(string $field): bool
    {
        return Gatekeeper::guard($this->getShieldGuard())
            ->canViewField($this->getGatekeeperModel(), $field);
    }

    /**
     * Check if user can update a field.
     */
    protected function canUpdateField(string $field): bool
    {
        return Gatekeeper::guard($this->getShieldGuard())
            ->canUpdateField($this->getGatekeeperModel(), $field);
    }

    /**
     * Check if user can view a column.
     */
    protected function canViewColumn(string $column): bool
    {
        return Gatekeeper::guard($this->getShieldGuard())
            ->canViewColumn($this->getGatekeeperModel(), $column);
    }

    /**
     * Check if user can execute an action.
     */
    protected function canExecuteAction(string $action): bool
    {
        return Gatekeeper::guard($this->getShieldGuard())
            ->canExecuteAction($this->getGatekeeperModel(), $action);
    }

    /**
     * Get all visible fields for API response.
     *
     * @return array<string>
     */
    protected function getVisibleFields(): array
    {
        return Gatekeeper::guard($this->getShieldGuard())
            ->getVisibleFields($this->getGatekeeperModel());
    }

    /**
     * Get all visible columns for API response.
     *
     * @return array<string>
     */
    protected function getVisibleColumns(): array
    {
        return Gatekeeper::guard($this->getShieldGuard())
            ->getVisibleColumns($this->getGatekeeperModel());
    }

    /**
     * Filter model data based on field permissions.
     *
     * @return array<string, mixed>
     */
    protected function filterByPermissions(Model $model): array
    {
        $visibleFields = $this->getVisibleFields();

        if (empty($visibleFields)) {
            return $model->toArray();
        }

        return $model->only($visibleFields);
    }

    /**
     * Build the permission name.
     */
    protected function buildPermissionName(string $action): string
    {
        $modelName = str($this->getGatekeeperModel())->snake()->toString();
        $separator = config('gatekeeper.generator.separator', '_');

        return "{$action}{$separator}{$modelName}";
    }

    /**
     * Authorize index action.
     */
    public function authorizeIndex(): void
    {
        $this->authorizeResource('view_any');
    }

    /**
     * Authorize show action.
     */
    public function authorizeShow(?Model $model = null): void
    {
        $this->authorizeResource('view', $model);
    }

    /**
     * Authorize store action.
     */
    public function authorizeStore(): void
    {
        $this->authorizeResource('create');
    }

    /**
     * Authorize update action.
     */
    public function authorizeUpdate(?Model $model = null): void
    {
        $this->authorizeResource('update', $model);
    }

    /**
     * Authorize destroy action.
     */
    public function authorizeDestroy(?Model $model = null): void
    {
        $this->authorizeResource('delete', $model);
    }

    /**
     * Authorize restore action.
     */
    public function authorizeRestore(?Model $model = null): void
    {
        $this->authorizeResource('restore', $model);
    }

    /**
     * Authorize force delete action.
     */
    public function authorizeForceDelete(?Model $model = null): void
    {
        $this->authorizeResource('force_delete', $model);
    }

    /**
     * Authorize a custom permission.
     */
    public function authorizePermission(string $permission, ?Model $model = null): void
    {
        Gatekeeper::guard($this->getShieldGuard())->authorize($permission, $model);
    }

    /**
     * Check if user can perform index action.
     */
    public function canIndex(): bool
    {
        return $this->canPerform('view_any');
    }

    /**
     * Check if user can perform store action.
     */
    public function canStore(): bool
    {
        return $this->canPerform('create');
    }
}
