<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Resources;

use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use LaraArabDev\FilamentGatekeeper\Models\Permission;
use LaraArabDev\FilamentGatekeeper\Models\Role;
use LaraArabDev\FilamentGatekeeper\Resources\RoleResource\Pages\CreateRole;
use LaraArabDev\FilamentGatekeeper\Resources\RoleResource\Pages\EditRole;
use LaraArabDev\FilamentGatekeeper\Resources\RoleResource\Pages\ListRoles;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?int $navigationSort = 1;

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

    /**
     * Permission types and their tab config (label, icon, columns).
     * Single source of truth for grouping by type; uses Permission model type constants.
     *
     * @return array<string, array{label: string, icon: string, columns: int}>
     */
    protected static function getPermissionTypeConfig(): array
    {
        return [
            Permission::TYPE_RESOURCE => [
                'label' => __('gatekeeper::messages.tabs.resources'),
                'icon' => 'heroicon-o-rectangle-stack',
                'columns' => 4,
            ],
            Permission::TYPE_MODEL => [
                'label' => __('gatekeeper::messages.tabs.models'),
                'icon' => 'heroicon-o-cube',
                'columns' => 4,
            ],
            Permission::TYPE_PAGE => [
                'label' => __('gatekeeper::messages.tabs.pages'),
                'icon' => 'heroicon-o-document-text',
                'columns' => 3,
            ],
            Permission::TYPE_WIDGET => [
                'label' => __('gatekeeper::messages.tabs.widgets'),
                'icon' => 'heroicon-o-chart-bar',
                'columns' => 3,
            ],
            Permission::TYPE_FIELD => [
                'label' => __('gatekeeper::messages.tabs.fields'),
                'icon' => 'heroicon-o-adjustments-horizontal',
                'columns' => 2,
            ],
            Permission::TYPE_COLUMN => [
                'label' => __('gatekeeper::messages.tabs.columns'),
                'icon' => 'heroicon-o-table-cells',
                'columns' => 2,
            ],
            Permission::TYPE_ACTION => [
                'label' => __('gatekeeper::messages.tabs.actions'),
                'icon' => 'heroicon-o-bolt',
                'columns' => 3,
            ],
            Permission::TYPE_RELATION => [
                'label' => __('gatekeeper::messages.tabs.relations'),
                'icon' => 'heroicon-o-link',
                'columns' => 3,
            ],
        ];
    }

    /** @return array<string> Types that show "Entity — Type" in section title (e.g. "Product — Fields") */
    protected static function getTypesWithEntityInTitle(): array
    {
        return [Permission::TYPE_FIELD, Permission::TYPE_COLUMN, Permission::TYPE_RELATION];
    }

    public static function form(Form $form): Form
    {
        $guard = config('gatekeeper.guard', 'web');
        return $form->schema([
            Section::make(__('gatekeeper::messages.sections.role_details'))
                ->description(__('gatekeeper::messages.sections.role_details_description'))
                ->schema([
                    Forms\Components\Grid::make(2)->schema([
                        TextInput::make('name')
                            ->label(__('gatekeeper::messages.labels.name'))
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\Select::make('guard_name')
                            ->label(__('gatekeeper::messages.labels.guard'))
                            ->options(self::getGuardOptions())
                            ->default($guard)
                            ->required(),
                    ]),

                    Toggle::make('select_all')
                        ->label(__('gatekeeper::messages.labels.select_all'))
                        ->helperText(__('gatekeeper::messages.helpers.select_all'))
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($state) {
                                $set('permissions', Permission::pluck('id')->toArray());
                            } else {
                                $set('permissions', []);
                            }
                        }),
                ]),

            Tabs::make('Permissions')
                ->columnSpanFull()
                ->tabs(
                    collect(static::getPermissionTypeConfig())->map(function (array $config, string $type) use ($guard) {
                        return Tab::make($config['label'])
                            ->icon($config['icon'])
                            ->badge(Permission::forGuard($guard)->ofType($type)->count())
                            ->schema(static::getGroupedPermissionsSection($type));
                    })->values()->all()
                ),
        ]);
    }

    /**
     * Get grouped permissions section for a given type.
     * Uses Permission model's getEntityGroupKey() to group by entity (resource, field, etc.).
     *
     * @return array<int, mixed>
     */
    protected static function getGroupedPermissionsSection(string $type): array
    {
        $guard = config('gatekeeper.guard', 'web');
        $permissions = Permission::forGuard($guard)->ofType($type)->get();

        if ($permissions->isEmpty()) {
            return [static::makeEmptyPermissionsPlaceholder()];
        }

        $typeConfig = static::getPermissionTypeConfig()[$type] ?? [];
        $columns = $typeConfig['columns'] ?? 3;
        $typeLabel = $typeConfig['label'] ?? $type;
        $showEntityInTitle = in_array($type, static::getTypesWithEntityInTitle(), true);

        /** @var Collection<string, Collection> $grouped */
        $grouped = $permissions->groupBy(fn(Permission $p) => $p->getEntityGroupKey());
        $grouped = $grouped->sortKeys(SORT_NATURAL);

        $sections = [];
        foreach ($grouped as $entityKey => $perms) {
            $sections[] = static::makeSectionForEntity($entityKey, $perms, $type, $typeLabel, $showEntityInTitle, $columns);
        }

        return $sections;
    }

    /**
     * Build a single section for one entity group (e.g. "User" with its permissions).
     *
     * @param Collection<int, Permission> $perms
     */
    protected static function makeSectionForEntity(
        string $entityKey,
        Collection $perms,
        string $type,
        string $typeLabel,
        bool $showEntityInTitle,
        int $columns
    ): Section {
        $title = str($entityKey)->headline()->toString();
        $sectionTitle = $showEntityInTitle
            ? $title . ' — ' . str($typeLabel)->lower()->toString()
            : $title;
        $modelPath = static::getModelPath($entityKey, $type);

        return Section::make($sectionTitle)
            ->description($modelPath)
            ->icon(static::getEntityIcon($type))
            ->collapsible()
            ->collapsed(false)
            ->compact()
            ->schema([
                Forms\Components\CheckboxList::make('permissions')
                    ->hiddenLabel()
                    ->relationship('permissions', 'id')
                    ->options(
                        $perms->mapWithKeys(fn(Permission $p) => [
                            $p->id => static::formatPermissionLabel($p->name, $entityKey),
                        ])->toArray()
                    )
                    ->columns($columns)
                    ->gridDirection('row')
                    ->bulkToggleable(),
            ]);
    }

    protected static function makeEmptyPermissionsPlaceholder(): Section
    {
        return Section::make()
            ->schema([
                Forms\Components\Placeholder::make('no_permissions')
                    ->hiddenLabel()
                    ->content(fn() => new \Illuminate\Support\HtmlString(
                        '<div class="flex flex-col items-center justify-center py-8 text-center">
                            <div class="flex items-center justify-center w-16 h-16 mb-4 rounded-full bg-warning-100 dark:bg-warning-500/20">
                                <svg class="w-8 h-8 text-warning-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                                </svg>
                            </div>
                            <h3 class="mb-2 text-base font-semibold text-gray-900 dark:text-white">' . __('gatekeeper::messages.placeholders.no_permissions') . '</h3>
                            <p class="mb-4 max-w-md text-sm text-gray-500 dark:text-gray-400">' . __('gatekeeper::messages.placeholders.no_permissions_hint') . '</p>
                            <div class="flex items-center gap-2 px-4 py-2 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                                <svg class="w-4 h-4 text-gray-400 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m6.75 7.5 3 2.25-3 2.25m4.5 0h3m-9 8.25h13.5A2.25 2.25 0 0 0 21 18V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v12a2.25 2.25 0 0 0 2.25 2.25Z" />
                                </svg>
                                <code class="text-sm font-mono text-primary-600 dark:text-primary-400">' . __('gatekeeper::messages.placeholders.no_permissions_command') . '</code>
                            </div>
                        </div>'
                    )),
            ]);
    }

    /**
     * Format permission label for display (Action + Entity + Type, e.g. "Update Product Stock Field").
     */
    protected static function formatPermissionLabel(string $permissionName, string $entityKey): string
    {
        return str($permissionName)->replace('_', ' ')->headline()->toString();
    }

    /**
     * Get model/entity path for display.
     */
    protected static function getModelPath(string $entityName, string $type): string
    {
        // Convert snake_case to PascalCase for class names
        $modelName = str($entityName)->studly()->toString();

        return match ($type) {
            Permission::TYPE_RESOURCE => "App\\Filament\\Resources\\{$modelName}Resource",
            Permission::TYPE_MODEL => "App\\Models\\{$modelName}",
            Permission::TYPE_PAGE => "App\\Filament\\Pages\\{$modelName}",
            Permission::TYPE_WIDGET => "App\\Filament\\Widgets\\{$modelName}",
            Permission::TYPE_FIELD => "Fields for {$modelName}",
            Permission::TYPE_COLUMN => "Columns for {$modelName}",
            Permission::TYPE_ACTION => "Actions for {$modelName}",
            Permission::TYPE_RELATION => "Relations for {$modelName}",
            default => '',
        };
    }

    /**
     * Get icon for entity type.
     */
    protected static function getEntityIcon(string $type): string
    {
        return match ($type) {
            Permission::TYPE_RESOURCE => 'heroicon-o-rectangle-stack',
            Permission::TYPE_MODEL => 'heroicon-o-cube',
            Permission::TYPE_PAGE => 'heroicon-o-document-text',
            Permission::TYPE_WIDGET => 'heroicon-o-chart-bar',
            Permission::TYPE_FIELD => 'heroicon-o-adjustments-horizontal',
            Permission::TYPE_COLUMN => 'heroicon-o-table-cells',
            Permission::TYPE_ACTION => 'heroicon-o-bolt',
            Permission::TYPE_RELATION => 'heroicon-o-link',
            default => 'heroicon-o-shield-check',
        };
    }

    /**
     * Get permission descriptions for tooltips.
     *
     * @param  \Illuminate\Support\Collection  $permissions
     * @return array<int, string>
     */
    protected static function getPermissionDescriptions($permissions): array
    {
        $descriptions = [];

        foreach ($permissions as $permission) {
            $action = $permission->getAction();
            $descriptions[$permission->id] = match ($action) {
                'view_any' => __('gatekeeper::messages.permission_descriptions.view_any'),
                'view' => __('gatekeeper::messages.permission_descriptions.view'),
                'create' => __('gatekeeper::messages.permission_descriptions.create'),
                'update' => __('gatekeeper::messages.permission_descriptions.update'),
                'delete' => __('gatekeeper::messages.permission_descriptions.delete'),
                'restore' => __('gatekeeper::messages.permission_descriptions.restore'),
                'force_delete' => __('gatekeeper::messages.permission_descriptions.force_delete'),
                'replicate' => __('gatekeeper::messages.permission_descriptions.replicate'),
                'reorder' => __('gatekeeper::messages.permission_descriptions.reorder'),
                default => '',
            };
        }

        return $descriptions;
    }

    /**
     * Get guard options.
     *
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
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
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('guard_name')
                    ->label(__('gatekeeper::messages.labels.guard'))
                    ->options(static::getGuardOptions()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->hidden(fn(Role $record) => $record->isSuperAdmin()),
            ])
            ->headerActions([
                Action::make('sync')
                    ->label(__('gatekeeper::messages.actions.sync_permissions'))
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->action(function () {
                        Artisan::call('gatekeeper:sync');

                        Notification::make()
                            ->title(__('gatekeeper::messages.notifications.permissions_synced'))
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
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
