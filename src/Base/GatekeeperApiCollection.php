<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Base;

use Illuminate\Http\Resources\Json\ResourceCollection;
use LaraArabDev\FilamentGatekeeper\Concerns\HasResourcePermissions;

/**
 * Base Shield API Resource Collection
 *
 * Extend this class for Laravel API Resource Collections with built-in permission support.
 *
 * Usage:
 * ```php
 * use LaraArabDev\FilamentGatekeeper\Base\GatekeeperApiCollection;
 *
 * class UserCollection extends GatekeeperApiCollection
 * {
 *     protected string $shieldModel = 'User';
 *
 *     public $collects = UserResource::class;
 * }
 * ```
 */
class GatekeeperApiCollection extends ResourceCollection
{
    use HasResourcePermissions;
}
