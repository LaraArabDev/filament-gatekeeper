<?php

use LaraArabDev\FilamentGatekeeper\Base\GatekeeperAuthenticatable;
use LaraArabDev\FilamentGatekeeper\Base\GatekeeperModel;
use LaraArabDev\FilamentGatekeeper\Base\GatekeeperPage;
use LaraArabDev\FilamentGatekeeper\Base\GatekeeperResource;
use LaraArabDev\FilamentGatekeeper\Base\GatekeeperWidget;

return [
    /*
    |--------------------------------------------------------------------------
    | Super Admin Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the super admin role that bypasses all permission checks.
    |
    */
    'super_admin' => [
        'enabled' => true,
        'role' => 'super-admin',
    ],

    /*
    |--------------------------------------------------------------------------
    | Guard Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the authentication guards for permission management.
    |
    */
    'guard' => 'web',

    'guards' => [
        'web' => [
            'enabled' => true,
            'provider' => 'users',
        ],
        'api' => [
            'enabled' => true,
            'provider' => 'users',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Permission Prefixes
    |--------------------------------------------------------------------------
    |
    | Define the permission action prefixes for different types of entities.
    |
    */
    'permission_prefixes' => [
        'resource' => [
            'view_any',
            'view',
            'create',
            'update',
            'delete',
            'restore',
            'force_delete',
            'replicate',
            'reorder',
        ],
        // Model prefixes for API-only models (without Filament Resources)
        'model' => [
            'view_any',
            'view',
            'create',
            'update',
            'delete',
            'restore',
            'force_delete',
        ],
        'page' => ['view'],
        'widget' => ['view'],
        'field' => ['view', 'update'],
        'column' => ['view'],
        'action' => ['execute'],
        'relation' => ['view', 'create', 'update', 'delete'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Permission Types
    |--------------------------------------------------------------------------
    |
    | Define the available permission types.
    |
    */
    'types' => [
        'resource' => 'Resource',
        'model' => 'Model',
        'page' => 'Page',
        'widget' => 'Widget',
        'field' => 'Field',
        'column' => 'Column',
        'action' => 'Action',
        'relation' => 'Relation',
    ],

    /*
    |--------------------------------------------------------------------------
    | Discovery Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the paths for discovering models, resources, pages, and widgets.
    | Supports glob patterns for multi-panel Filament applications.
    |
    */
    'discovery' => [
        // Enable model discovery for API-only models (models without Filament Resources)
        // This is useful when you want to manage API permissions for all models
        'discover_models' => false,

        'models' => [
            'app/Models',
        ],
        'resources' => [
            'app/Filament/Resources',
            'app/Filament/*/Resources',
        ],
        'pages' => [
            'app/Filament/Pages',
            'app/Filament/*/Pages',
        ],
        'widgets' => [
            'app/Filament/Widgets',
            'app/Filament/*/Widgets',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Module Configuration (HMVC Support)
    |--------------------------------------------------------------------------
    |
    | Configure module discovery for HMVC architectures like nwidart/laravel-modules.
    |
    */
    'modules' => [
        'enabled' => false,
        'namespace' => 'Modules',
        'path' => base_path('Modules'),

        // Discovery paths within each module
        'discovery_paths' => [
            'models' => '{module}/Models',
            'resources' => '{module}/Filament/Resources',
            'pages' => '{module}/Filament/Pages',
            'widgets' => '{module}/Filament/Widgets',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Navigation Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the navigation settings for Shield Manager resources.
    |
    */
    'navigation' => [
        'group' => 'Access Control',
        'icon' => 'heroicon-o-shield-check',
        'sort' => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Actions
    |--------------------------------------------------------------------------
    |
    | Define additional custom action permissions for specific models.
    | Use '*' to apply to all models.
    |
    */
    'custom_actions' => [
        '*' => [], // Global actions for all models
        // 'User' => ['export', 'impersonate'],
        // 'Order' => ['approve', 'cancel', 'refund'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Field Discovery Configuration
    |--------------------------------------------------------------------------
    |
    | Configure automatic field discovery for permission generation.
    | When enabled, fields will be detected from various sources.
    |
    | AVAILABLE SOURCES:
    | -----------------
    | 'config'   - Read from 'field_permissions' array below (manual configuration)
    |              Best for: Fine-grained control over which fields have permissions
    |              Example: 'User' => ['email', 'salary', 'phone']
    |
    | 'fillable' - Read from model's $fillable property (RECOMMENDED)
    |              Best for: Auto-detect editable fields defined in your models
    |              Example: protected $fillable = ['name', 'email', 'phone']
    |              Note: Only detects fields you've marked as mass-assignable
    |
    | 'database' - Read all columns from the database table schema
    |              Best for: Complete coverage of all database columns
    |              Example: Schema::getColumnListing('users') returns all columns
    |              Note: May include many system columns - use exclusions
    |
    | 'resource' - Parse fields from Filament Resource form() method
    |              Best for: Match permissions to actual Filament form fields
    |              Example: Detects TextInput::make('email'), Select::make('role')
    |              Note: Only works if you have Filament Resources defined
    |
    | SOURCE ORDER: Sources are checked in order. If a model already has fields
    | from 'config', it won't be checked in 'fillable'. Put preferred source first.
    |
    */
    'field_discovery' => [
        // Enable automatic field discovery
        // Set to true to auto-detect fields from the configured sources
        'enabled' => false,

        // Discovery sources - order matters (first match wins for each model)
        // Available: 'config', 'fillable', 'database', 'resource'
        // Recommended: ['config', 'fillable'] - Manual config takes precedence, then model fillable
        'sources' => ['config', 'fillable'],

        // Default fields to exclude (always applied to all models)
        // These are typically system fields that shouldn't have permissions
        'default_excluded' => [
            'id',
            'uuid',
            'created_at',
            'updated_at',
            'deleted_at',
            'remember_token',
            'email_verified_at',
            'two_factor_secret',
            'two_factor_recovery_codes',
            'two_factor_confirmed_at',
        ],

        // Additional fields to exclude per model
        // '*' applies to all models, or specify model name for model-specific exclusions
        'excluded' => [
            '*' => ['password'],  // Exclude password from all models
            // 'User' => ['api_token'],  // Exclude api_token only for User model
            // 'Employee' => ['ssn'],    // Exclude ssn only for Employee model
        ],

        // Patterns to identify sensitive fields (for flagging/reporting)
        // Fields matching these patterns are considered sensitive
        'sensitive_patterns' => [
            'password',
            'secret',
            'token',
            'ssn',
            'social_security',
            'credit_card',
            'cvv',
            'pin',
            'api_key',
            'private_key',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Field Permissions
    |--------------------------------------------------------------------------
    |
    | Define which fields should have granular permissions.
    | Permissions will be: view_field_{model}_{field}, update_field_{model}_{field}
    | Note: If field_discovery.enabled is true, these will be merged with
    | auto-discovered fields.
    |
    */
    'field_permissions' => [
        // '*' => [], // Global fields for all models
        // 'User' => ['email', 'password', 'salary'],
        // 'Employee' => ['salary', 'ssn'],

        'User' => ['email', 'password', 'salary', 'phone', 'address'],
        'Product' => ['name', 'price', 'description', 'stock'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Column Discovery Configuration
    |--------------------------------------------------------------------------
    |
    | Configure automatic column discovery for table permission generation.
    | When enabled, columns will be detected from various sources.
    |
    | AVAILABLE SOURCES:
    | -----------------
    | 'config'   - Read from 'column_permissions' array below (manual configuration)
    |              Best for: Fine-grained control over which columns have permissions
    |              Example: 'User' => ['email', 'salary', 'phone']
    |
    | 'database' - Read all columns from the database table schema (RECOMMENDED)
    |              Best for: Complete coverage of all displayable columns
    |              Example: Schema::getColumnListing('users') returns all columns
    |              Note: Use exclusions to filter out sensitive columns
    |
    | 'resource' - Parse columns from Filament Resource table() method
    |              Best for: Match permissions to actual Filament table columns
    |              Example: Detects TextColumn::make('email'), BadgeColumn::make('status')
    |              Note: Only works if you have Filament Resources defined
    |
    | SOURCE ORDER: Sources are checked in order. If a model already has columns
    | from 'config', it won't be checked in 'database'. Put preferred source first.
    |
    | NOTE: Unlike fields, columns don't have 'fillable' source because columns
    | represent what's displayed in tables, not what's editable in forms.
    |
    */
    'column_discovery' => [
        // Enable automatic column discovery
        // Set to true to auto-detect columns from the configured sources
        'enabled' => false,

        // Discovery sources - order matters (first match wins for each model)
        // Available: 'config', 'database', 'resource'
        // Recommended: ['config', 'database'] - Manual config takes precedence, then DB schema
        'sources' => ['config', 'database'],

        // Default columns to exclude (always applied to all models)
        // These are typically sensitive columns that shouldn't be visible
        'default_excluded' => [
            'password',
            'remember_token',
            'two_factor_secret',
            'two_factor_recovery_codes',
        ],

        // Additional columns to exclude per model
        // '*' applies to all models, or specify model name for model-specific exclusions
        'excluded' => [
            '*' => [],  // Global exclusions for all models
            // 'User' => ['api_token'],    // Exclude api_token only for User model
            // 'Employee' => ['salary'],   // Exclude salary only for Employee model
        ],

        // Patterns to identify sensitive columns (for flagging/reporting)
        // Columns matching these patterns are considered sensitive
        'sensitive_patterns' => [
            'password',
            'secret',
            'token',
            'ssn',
            'salary',
            'income',
            'credit_card',
            'bank_account',
            'api_key',
            'private_key',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Column Permissions
    |--------------------------------------------------------------------------
    |
    | Define which table columns should have visibility permissions.
    | Note: If column_discovery.enabled is true, these will be merged with
    | auto-discovered columns.
    |
    */
    'column_permissions' => [
        // '*' => [], // Global columns for all models
        // 'User' => ['email', 'phone', 'salary'],
        // 'Employee' => ['salary', 'performance_rating'],
        'User' => ['email', 'phone', 'salary', 'created_at'],
        'Product' => ['name', 'price', 'stock', 'created_at'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Relation Permissions
    |--------------------------------------------------------------------------
    |
    | Define which relation managers should have access permissions.
    |
    */
    'relation_permissions' => [
        '*' => [], // Global relations for all models
        // 'User' => ['roles', 'posts', 'comments'],
        // 'Order' => ['items', 'payments'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure caching for permission checks to improve performance.
    |
    */
    'cache' => [
        'enabled' => true,
        'driver' => null, // null = default cache driver
        'prefix' => 'gatekeeper',
        'ttl' => 3600, // 1 hour in seconds
        'tags' => ['gatekeeper'], // Cache tags (requires tagged cache driver)
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto-Apply Strategy
    |--------------------------------------------------------------------------
    |
    | Configure automatic trait application strategies.
    |
    */
    'auto_apply' => [
        'base_classes' => true,
        'publish_stubs' => true,
        'make_commands' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Stub Configuration
    |--------------------------------------------------------------------------
    |
    | Configure stub publishing settings.
    |
    */
    'stubs' => [
        'path' => base_path('stubs/gatekeeper'),
        'override_defaults' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Base Classes
    |--------------------------------------------------------------------------
    |
    | Configure the base classes provided by Shield Manager.
    |
    */
    'base_classes' => [
        'resource' => GatekeeperResource::class,
        'page' => GatekeeperPage::class,
        'widget' => GatekeeperWidget::class,
        'model' => GatekeeperModel::class,
        'authenticatable' => GatekeeperAuthenticatable::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Excluded Models
    |--------------------------------------------------------------------------
    |
    | List of models to exclude from permission discovery.
    | Useful for excluding pivot tables, internal models, etc.
    |
    */
    'excluded_models' => [
        // 'App\\Models\\Pivot',
        // 'App\\Models\\PersonalAccessToken',
    ],

    /*
    |--------------------------------------------------------------------------
    | Excluded Resources
    |--------------------------------------------------------------------------
    |
    | List of resources to exclude from permission discovery.
    |
    */
    'excluded_resources' => [
        // 'App\\Filament\\Resources\\InternalResource',
    ],

    /*
    |--------------------------------------------------------------------------
    | Excluded Pages
    |--------------------------------------------------------------------------
    |
    | List of pages to exclude from permission discovery.
    |
    */
    'excluded_pages' => [
        // 'App\\Filament\\Pages\\Dashboard',
    ],

    /*
    |--------------------------------------------------------------------------
    | Excluded Widgets
    |--------------------------------------------------------------------------
    |
    | List of widgets to exclude from permission discovery.
    |
    */
    'excluded_widgets' => [
        // 'App\\Filament\\Widgets\\AccountWidget',
    ],

    /*
    |--------------------------------------------------------------------------
    | Generator Settings
    |--------------------------------------------------------------------------
    |
    | Configure permission name generation.
    |
    */
    'generator' => [
        // Use snake_case for permission names (view_any_user vs viewAnyUser)
        'snake_case' => true,

        // Separator for permission parts
        'separator' => '_',

        // Include guard name in permission (view_any_user_web)
        'include_guard' => false,
    ],
];
