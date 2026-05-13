<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Interface for the core Gatekeeper service.
 *
 * This interface defines the contract for permission checking and
 * management operations within the Filament Gatekeeper package.
 *
 * @package LaraArabDev\FilamentGatekeeper\Contracts
 */
interface GatekeeperInterface
{
    /**
     * Set the guard for permission checks.
     *
     * @param string $guardName The guard name to use
     * @return static Returns self for method chaining
     */
    public function guard(string $guardName): static;

    /**
     * Get the current guard name.
     *
     * @return string The guard name being used
     */
    public function getGuard(): string;

    /**
     * Get the current authenticated user.
     *
     * @return Authenticatable|null The authenticated user or null
     */
    public function user(): ?Authenticatable;

    /**
     * Check if the current user has a specific permission.
     *
     * Supports OR logic: 'permission1|permission2' will return true if user
     * has any of the specified permissions.
     *
     * @param string $permission Permission name or pipe-separated permissions
     * @param mixed $record Optional record for context
     * @return bool True if user has the permission(s), false otherwise
     */
    public function can(string $permission, mixed $record = null): bool;

    /**
     * Check if the current user can view a specific field.
     *
     * @param string $modelName The model name
     * @param string $field The field name
     * @return bool True if the user can view the field, false otherwise
     */
    public function canViewField(string $modelName, string $field): bool;

    /**
     * Check if the current user can update a specific field.
     *
     * @param string $modelName The model name
     * @param string $field The field name
     * @return bool True if the user can update the field, false otherwise
     */
    public function canUpdateField(string $modelName, string $field): bool;

    /**
     * Check if the current user can view a specific column.
     *
     * @param string $modelName The model name
     * @param string $column The column name
     * @return bool True if the user can view the column, false otherwise
     */
    public function canViewColumn(string $modelName, string $column): bool;
}
