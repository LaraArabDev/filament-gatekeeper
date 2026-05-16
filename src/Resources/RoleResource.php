<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Resources;

use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use LaraArabDev\FilamentGatekeeper\Models\Role;
use LaraArabDev\FilamentGatekeeper\Resources\RoleResource\Forms\RoleForm;
use LaraArabDev\FilamentGatekeeper\Resources\RoleResource\Pages\CreateRole;
use LaraArabDev\FilamentGatekeeper\Resources\RoleResource\Pages\EditRole;
use LaraArabDev\FilamentGatekeeper\Resources\RoleResource\Pages\ListRoles;
use LaraArabDev\FilamentGatekeeper\Resources\RoleResource\Tables\RoleTable;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?int $navigationSort = 1;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-shield-check';
    }

    public static function getNavigationLabel(): string
    {
        return __('gatekeeper::messages.navigation.roles');
    }

    public static function getModelLabel(): string
    {
        return __('gatekeeper::messages.labels.role');
    }

    public static function getPluralModelLabel(): string
    {
        return __('gatekeeper::messages.labels.roles');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('gatekeeper.navigation.group', __('gatekeeper::messages.navigation.group'));
    }

    public static function form(Form $form): Form
    {
        return RoleForm::make($form);
    }

    public static function table(Table $table): Table
    {
        return RoleTable::make($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }
}
