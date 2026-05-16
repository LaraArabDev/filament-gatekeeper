<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Concerns;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use LaraArabDev\FilamentGatekeeper\Facades\Gatekeeper;

trait InteractsWithGatekeeperCache
{
    /**
     * Check if permission checks should be bypassed for super admin.
     */
    protected static function shouldBypassPermissions(): bool
    {
        return Gatekeeper::shouldBypassPermissions();
    }

    /**
     * Get the current authenticated user.
     */
    protected static function getAuthUser(): ?Authenticatable
    {
        return Auth::user();
    }

    /**
     * Get the permission matrix for the current user.
     *
     * @return array<string, mixed>
     */
    protected static function getPermissionMatrix(): array
    {
        return Gatekeeper::getPermissionMatrix();
    }

    /**
     * Get the guard to use for permission checks.
     */
    protected static function getGuardName(): string
    {
        return config('gatekeeper.guard', 'web');
    }

    /**
     * Check if a user has a specific permission.
     */
    protected static function userCan(string $permission): bool
    {
        $user = static::getAuthUser();

        if (! $user) {
            return false;
        }

        if (static::shouldBypassPermissions()) {
            return true;
        }

        return $user->can($permission);
    }
}
