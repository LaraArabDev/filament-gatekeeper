<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Concerns;

/**
 * Trait HasPagePermissions
 *
 * Provides page-level permission checking for Filament pages.
 * Controls access to custom Filament pages.
 */
trait HasPagePermissions
{
    use InteractsWithGatekeeperCache;

    /**
     * Determine if the user can access the page.
     */
    public static function canAccess(): bool
    {
        if (static::shouldBypassPermissions()) {
            return true;
        }

        return static::userCan(static::getPagePermissionName());
    }

    /**
     * Get the permission name for this page.
     */
    protected static function getPagePermissionName(): string
    {
        $pageName = static::getPageName();
        $separator = config('gatekeeper.generator.separator', '_');

        if (config('gatekeeper.generator.snake_case', true)) {
            $pageName = str($pageName)->snake()->toString();
        }

        return "view{$separator}page{$separator}{$pageName}";
    }

    /**
     * Get the page name for permission generation.
     */
    protected static function getPageName(): string
    {
        $className = class_basename(static::class);

        // Remove 'Page' suffix if present
        return str($className)
            ->replace('Page', '')
            ->toString();
    }

    /**
     * Determine if the page should be registered in navigation.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    /**
     * Get the navigation badge (can be overridden).
     */
    public static function getNavigationBadge(): ?string
    {
        return null;
    }
}
