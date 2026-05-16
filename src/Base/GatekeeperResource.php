<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Base;

use Filament\Resources\Resource;
use LaraArabDev\FilamentGatekeeper\Concerns\HasActionPermissions;
use LaraArabDev\FilamentGatekeeper\Concerns\HasColumnPermissions;
use LaraArabDev\FilamentGatekeeper\Concerns\HasFieldPermissions;
use LaraArabDev\FilamentGatekeeper\Concerns\HasGatekeeperPermissions;
use LaraArabDev\FilamentGatekeeper\Concerns\HasRelationPermissions;

/**
 * Base Shield Filament Resource
 *
 * Extend this class for Filament Resources with built-in Shield permission support.
 *
 * Usage:
 * ```php
 * use LaraArabDev\FilamentGatekeeper\Base\GatekeeperResource;
 *
 * class UserResource extends GatekeeperResource
 * {
 *     protected static ?string $model = User::class;
 *
 *     public static function form(Form $form): Form
 *     {
 *         return $form->schema([
 *             // Fields automatically respect permissions
 *         ]);
 *     }
 * }
 * ```
 */
abstract class GatekeeperResource extends Resource
{
    use HasActionPermissions;
    use HasColumnPermissions;
    use HasFieldPermissions;
    use HasGatekeeperPermissions;
    use HasRelationPermissions;
}
