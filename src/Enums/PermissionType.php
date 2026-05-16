<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/**
 * Permission type enum. Implements Filament's HasLabel, HasColor, HasIcon
 * so it works with Select/SelectFilter options and badge columns.
 */
enum PermissionType: string implements HasColor, HasIcon, HasLabel
{
    case Resource = 'resource';
    case Model = 'model';
    case Page = 'page';
    case Widget = 'widget';
    case Field = 'field';
    case Column = 'column';
    case Action = 'action';
    case Relation = 'relation';

    /**
     * {@inheritdoc}
     */
    public function getLabel(): ?string
    {
        return match ($this) {
            self::Resource => 'Resource',
            self::Model => 'Model',
            self::Page => 'Page',
            self::Widget => 'Widget',
            self::Field => 'Field',
            self::Column => 'Column',
            self::Action => 'Action',
            self::Relation => 'Relation',
        };
    }

    /**
     * {@inheritdoc}
     */
    public function getColor(): ?string
    {
        return match ($this) {
            self::Resource => 'primary',
            self::Model => 'cyan',
            self::Page => 'success',
            self::Widget => 'warning',
            self::Field => 'info',
            self::Column => 'gray',
            self::Action => 'danger',
            self::Relation => 'purple',
        };
    }

    /**
     * {@inheritdoc}
     */
    public function getIcon(): ?string
    {
        return match ($this) {
            self::Resource => 'heroicon-o-rectangle-stack',
            self::Model => 'heroicon-o-cube',
            self::Page => 'heroicon-o-document',
            self::Widget => 'heroicon-o-chart-bar',
            self::Field => 'heroicon-o-pencil-square',
            self::Column => 'heroicon-o-view-columns',
            self::Action => 'heroicon-o-bolt',
            self::Relation => 'heroicon-o-link',
        };
    }

    /**
     * Options array for select/filter (value => label). Used as default for Permission::getTypes().
     * Filament can also use PermissionType::class with ->options(PermissionType::class) when enum implements HasLabel.
     *
     * @return array<string, string>
     */
    public static function optionsForSelect(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->value] = $case->getLabel() ?? $case->value;
        }

        return $out;
    }
}
