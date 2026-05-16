<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Base;

use Filament\Widgets\Widget;
use LaraArabDev\FilamentGatekeeper\Concerns\HasWidgetPermissions;

/**
 * Base Shield Widget
 *
 * Extend this class instead of Filament's Widget to automatically
 * include Gatekeeper widget permission functionality.
 *
 * Example:
 * ```php
 * use LaraArabDev\FilamentGatekeeper\Base\GatekeeperWidget;
 *
 * class StatsOverview extends GatekeeperWidget
 * {
 *     // canView() and shouldBeVisible() automatically work!
 * }
 * ```
 */
abstract class GatekeeperWidget extends Widget
{
    use HasWidgetPermissions;
}
