<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Resources\RoleResource\Tables;

use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Artisan;
use LaraArabDev\FilamentGatekeeper\Models\Role;
use LaraArabDev\FilamentGatekeeper\Resources\RoleResource\Forms\RoleForm;

class RoleTable
{
    public static function make(Table $table): Table
    {
        return $table
            ->columns(static::columns())
            ->filters(static::filters())
            ->headerActions(static::headerActions())
            ->actions(static::actions())
            ->bulkActions(static::bulkActions());
    }

    /**
     * @return array<int, TextColumn>
     */
    public static function columns(): array
    {
        return [
            TextColumn::make('id')
                ->label('ID')
                ->sortable(),

            TextColumn::make('name')
                ->label(__('gatekeeper::messages.labels.name'))
                ->searchable()
                ->sortable(),

            TextColumn::make('guard_name')
                ->label(__('gatekeeper::messages.labels.guard'))
                ->badge()
                ->color('gray'),

            TextColumn::make('permissions_count')
                ->counts('permissions')
                ->label(__('gatekeeper::messages.labels.permissions_count'))
                ->sortable(),

            TextColumn::make('created_at')
                ->label(__('gatekeeper::messages.labels.created_at'))
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('updated_at')
                ->label(__('gatekeeper::messages.labels.updated_at'))
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    /**
     * @return array<int, SelectFilter>
     */
    public static function filters(): array
    {
        return [
            SelectFilter::make('guard_name')
                ->label(__('gatekeeper::messages.labels.guard'))
                ->options(RoleForm::getGuardOptions()),
        ];
    }

    /**
     * @return array<int, Action>
     */
    public static function headerActions(): array
    {
        return [
            Action::make('sync')
                ->label(__('gatekeeper::messages.actions.sync_permissions'))
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->action(function (): void {
                    Artisan::call('gatekeeper:sync');

                    Notification::make()
                        ->title(__('gatekeeper::messages.notifications.permissions_synced'))
                        ->success()
                        ->send();
                }),
        ];
    }

    /**
     * @return array<int, Action|Tables\Actions\EditAction|Tables\Actions\DeleteAction>
     */
    public static function actions(): array
    {
        return [
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make()
                ->hidden(fn (Role $record): bool => $record->isSuperAdmin()),
        ];
    }

    /**
     * @return array<int, Tables\Actions\BulkActionGroup>
     */
    public static function bulkActions(): array
    {
        return [
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
            ]),
        ];
    }
}
