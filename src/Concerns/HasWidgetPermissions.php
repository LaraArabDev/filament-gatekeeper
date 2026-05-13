<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Concerns;

/**
 * Trait HasWidgetPermissions
 *
 * Provides widget-level permission checking for Filament widgets.
 * Controls visibility of dashboard widgets.
 */
trait HasWidgetPermissions
{
    use InteractsWithGatekeeperCache;

    /**
     * Determine if the user can view the widget.
     */
    public static function canView(): bool
    {
        if (static::shouldBypassPermissions()) {
            return true;
        }

        return static::userCan(static::getWidgetPermissionName());
    }

    /**
     * Get the permission name for this widget.
     */
    protected static function getWidgetPermissionName(): string
    {
        $widgetName = static::getWidgetName();
        $separator = config('gatekeeper.generator.separator', '_');

        if (config('gatekeeper.generator.snake_case', true)) {
            $widgetName = str($widgetName)->snake()->toString();
        }

        return "view{$separator}widget{$separator}{$widgetName}";
    }

    /**
     * Get the widget name for permission generation.
     */
    protected static function getWidgetName(): string
    {
        $className = class_basename(static::class);

        // Remove 'Widget' suffix if present
        return str($className)
            ->replace('Widget', '')
            ->toString();
    }

    /**
     * Determine if the widget should be visible.
     *
     * @return bool
     */
    public static function shouldBeVisible(): bool
    {
        return static::canView();
    }
}

