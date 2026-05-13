<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use LaraArabDev\FilamentGatekeeper\Enums\PermissionType;
use LaraArabDev\FilamentGatekeeper\Models\Permission;
use LaraArabDev\FilamentGatekeeper\Resources\PermissionResource\Pages\ListPermissions;

class PermissionResource extends Resource
{
    protected static ?string $model = Permission::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('gatekeeper::messages.navigation.permissions');
    }

    public static function getModelLabel(): string
    {
        return __('gatekeeper::messages.labels.permission');
    }

    public static function getPluralModelLabel(): string
    {
        return __('gatekeeper::messages.labels.permissions');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('gatekeeper.navigation.group', __('gatekeeper::messages.navigation.group'));
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make(__('gatekeeper::messages.labels.permission_details'))
                ->description(__('gatekeeper::messages.descriptions.permission_info'))
                ->icon('heroicon-o-key')
                ->schema([
                    Forms\Components\Grid::make(2)->schema(
                        static::getFormFields(),
                    ),
                ]),
        ]);
    }

    /**
     * Form fields for view/edit (read-only).
     *
     * @return array<int, Forms\Components\Component>
     */
    protected static function getFormFields(): array
    {
        return [
            Forms\Components\TextInput::make('name')
                ->label(__('gatekeeper::messages.labels.name'))
                ->required()
                ->disabled()
                ->columnSpanFull(),

            Forms\Components\Select::make('guard_name')
                ->label(__('gatekeeper::messages.labels.guard'))
                ->options(static::getGuardOptions())
                ->disabled(),

            Forms\Components\Select::make('type')
                ->label(__('gatekeeper::messages.labels.type'))
                ->options(PermissionType::class)
                ->disabled(),

            Forms\Components\Placeholder::make('entity')
                ->label(__('gatekeeper::messages.labels.entity'))
                ->content(fn(?Permission $record): string => $record?->getEntityDisplayName() ?? '—'),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function getGuardOptions(): array
    {
        $guards = config('gatekeeper.guards', ['web' => ['enabled' => true]]);
        return collect($guards)
            ->filter(fn($guard) => $guard['enabled'] ?? true)
            ->mapWithKeys(fn($guard, $name) => [$name => ucfirst($name)])
            ->toArray();
    }

    /**
     * Badge color for guard name (web, api, etc.).
     */
    protected static function getGuardBadgeColor(string $guard): string
    {
        return match ($guard) {
            'web' => 'success',
            'api' => 'warning',
            default => 'gray',
        };
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(static::getTableColumns())
            ->filters(static::getTableFilters(), layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(4)
            ->actions([
                Tables\Actions\ViewAction::make()->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('name')
            ->striped()
            ->paginated([25, 50, 100, 'all'])
            ->poll('60s');
    }

    /**
     * Table column definitions. Type column uses PermissionType enum (icon, color, label).
     *
     * @return array<int, TextColumn>
     */
    protected static function getTableColumns(): array
    {
        return [
            TextColumn::make('name')
                ->label(__('gatekeeper::messages.labels.permission'))
                ->searchable()
                ->sortable()
                ->icon('heroicon-o-key')
                ->iconColor('primary')
                ->weight('medium')
                ->copyable()
                ->copyMessage(__('gatekeeper::messages.notifications.copied'))
                ->description(fn(Permission $record): string => $record->getTypeEnum()?->getLabel() ?? (string) $record->type),

            TextColumn::make('entity_name')
                ->label(__('gatekeeper::messages.labels.entity'))
                ->state(fn(Permission $record): ?string => $record->getEntityDisplayName())
                ->badge()
                ->color(fn(Permission $record): string => $record->getTypeColor())
                ->searchable(query: fn(Builder $query, string $search): Builder => $query->where('name', 'like', "%{$search}%")),

            TextColumn::make('action')
                ->label(__('gatekeeper::messages.labels.action'))
                ->state(fn(Permission $record): string => $record->getActionLabel())
                ->color('success')
                ->badge(),

            TextColumn::make('type')
                ->label(__('gatekeeper::messages.labels.type'))
                ->badge()
                ->icon(fn(Permission $record): ?string => $record->getTypeEnum()?->getIcon())
                ->color(fn(Permission $record): string => $record->getTypeColor())
                ->formatStateUsing(fn(Permission $record): string => $record->getTypeEnum()?->getLabel() ?? (string) $record->type),

            TextColumn::make('guard_name')
                ->label(__('gatekeeper::messages.labels.guard'))
                ->badge()
                ->icon('heroicon-o-shield-check')
                ->color(fn(string $state): string => static::getGuardBadgeColor($state)),

            TextColumn::make('roles_count')
                ->label(__('gatekeeper::messages.labels.roles'))
                ->counts('roles')
                ->badge()
                ->color('primary')
                ->sortable(),

            TextColumn::make('created_at')
                ->label(__('gatekeeper::messages.labels.created_at'))
                ->dateTime('M j, Y')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    /**
     * Table filter definitions. Type filter uses PermissionType enum for options.
     *
     * @return array<int, Tables\Filters\Filter>
     */
    protected static function getTableFilters(): array
    {
        return [
            Tables\Filters\SelectFilter::make('type')
                ->label(__('gatekeeper::messages.labels.type'))
                ->options(PermissionType::class)
                ->multiple()
                ->preload(),

            Tables\Filters\SelectFilter::make('guard_name')
                ->label(__('gatekeeper::messages.labels.guard'))
                ->options(static::getGuardOptions()),

            Tables\Filters\SelectFilter::make('entity')
                ->label(__('gatekeeper::messages.labels.entity'))
                ->options(fn() => Permission::getDistinctEntityOptionsForFilter())
                ->query(function (Builder $query, array $data): Builder {
                    $value = $data['value'] ?? null;
                    if ($value === null || $value === '') {
                        return $query;
                    }
                    return $query->where(function (Builder $q) use ($value): void {
                        $q->where('entity', $value)->orWhere('name', 'like', '%' . $value . '%');
                    });
                })
                ->searchable(),

            Tables\Filters\Filter::make('unused')
                ->label(__('gatekeeper::messages.filters.unused_permissions'))
                ->query(fn(Builder $query): Builder => $query->doesntHave('roles'))
                ->toggle(),
        ];
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPermissions::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
