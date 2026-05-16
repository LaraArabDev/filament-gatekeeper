<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * Interface for resources that implement Shield permissions.
 */
interface Gatekeeperable
{
    /**
     * Determine whether the user can view any models.
     */
    public static function canViewAny(): bool;

    /**
     * Determine whether the user can view the model.
     */
    public static function canView(Model $record): bool;

    /**
     * Determine whether the user can create models.
     */
    public static function canCreate(): bool;

    /**
     * Determine whether the user can update the model.
     */
    public static function canEdit(Model $record): bool;

    /**
     * Determine whether the user can delete the model.
     */
    public static function canDelete(Model $record): bool;

    /**
     * Determine whether the user can restore the model.
     */
    public static function canRestore(Model $record): bool;

    /**
     * Determine whether the user can permanently delete the model.
     */
    public static function canForceDelete(Model $record): bool;

    /**
     * Determine if the resource should be registered in navigation.
     */
    public static function shouldRegisterNavigation(): bool;
}
