<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Base;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * Base Shield Authenticatable
 *
 * Extend this class instead of Laravel's Authenticatable to automatically
 * include Spatie's HasRoles trait and Notifiable for user models.
 *
 * Example:
 * ```php
 * use LaraArabDev\FilamentGatekeeper\Base\GatekeeperAuthenticatable;
 *
 * class User extends GatekeeperAuthenticatable
 * {
 *     // HasRoles and Notifiable traits are automatically included!
 *     // $user->assignRole('admin');
 *     // $user->hasPermissionTo('manage users');
 * }
 * ```
 */
abstract class GatekeeperAuthenticatable extends Authenticatable
{
    use HasRoles;
    use Notifiable;

    /**
     * The guard name for permissions.
     */
    protected string $guard_name = 'web';

    /**
     * Set the guard name for this user.
     */
    public function setGuardName(string $guard): static
    {
        $this->guard_name = $guard;

        return $this;
    }

    /**
     * Get the guard name for this user.
     */
    public function getGuardName(): string
    {
        return $this->guard_name;
    }

    /**
     * Check if this user is a super admin.
     */
    public function isSuperAdmin(): bool
    {
        $superAdminRole = config('gatekeeper.super_admin.role', 'super-admin');

        return $this->hasRole($superAdminRole);
    }
}
