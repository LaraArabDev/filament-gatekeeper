<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Base;

use Filament\Pages\Page;
use LaraArabDev\FilamentGatekeeper\Concerns\HasPagePermissions;

/**
 * Base Shield Page
 *
 * Extend this class instead of Filament's Page to automatically
 * include Gatekeeper page permission functionality.
 *
 * Example:
 * ```php
 * use LaraArabDev\FilamentGatekeeper\Base\GatekeeperPage;
 *
 * class SettingsPage extends GatekeeperPage
 * {
 *     protected static ?string $navigationIcon = 'heroicon-o-cog';
 *     // canAccess() and shouldRegisterNavigation() automatically work!
 * }
 * ```
 */
abstract class GatekeeperPage extends Page
{
    use HasPagePermissions;
}

