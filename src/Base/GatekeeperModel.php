<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Base;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Traits\HasRoles;

/**
 * Base Shield Model
 *
 * Extend this class instead of Eloquent's Model to automatically
 * include Spatie's HasRoles trait for permission management.
 *
 * Example:
 * ```php
 * use LaraArabDev\FilamentGatekeeper\Base\GatekeeperModel;
 *
 * class Product extends GatekeeperModel
 * {
 *     // HasRoles trait is automatically included!
 *     // $product->assignRole('manager');
 *     // $product->hasPermissionTo('edit products');
 * }
 * ```
 */
abstract class GatekeeperModel extends Model
{
    use HasRoles;

    /**
     * The guard name for permissions.
     */
    protected string $guard_name = 'web';

    /**
     * Set the guard name for this model.
     */
    public function setGuardName(string $guard): static
    {
        $this->guard_name = $guard;

        return $this;
    }

    /**
     * Get the guard name for this model.
     */
    public function getGuardName(): string
    {
        return $this->guard_name;
    }
}
