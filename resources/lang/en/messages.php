<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Navigation
    |--------------------------------------------------------------------------
    */
    'navigation' => [
        'group' => 'Access Control',
        'roles' => 'Roles',
        'permissions' => 'Permissions',
    ],

    /*
    |--------------------------------------------------------------------------
    | Labels
    |--------------------------------------------------------------------------
    */
    'labels' => [
        'role' => 'Role',
        'roles' => 'Roles',
        'permission' => 'Permission',
        'permissions' => 'Permissions',
        'permission_details' => 'Permission Details',
        'name' => 'Name',
        'guard' => 'Guard',
        'type' => 'Type',
        'model' => 'Model',
        'entity' => 'Entity',
        'action' => 'Action',
        'permissions_count' => 'Permissions Count',
        'created_at' => 'Created At',
        'updated_at' => 'Updated At',
        'select_all' => 'Select All Permissions',
    ],

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */
    'helpers' => [
        'select_all' => 'Enable this to select all available permissions at once.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Descriptions
    |--------------------------------------------------------------------------
    */
    'descriptions' => [
        'permission_info' => 'View permission details and associated information.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Filters
    |--------------------------------------------------------------------------
    */
    'filters' => [
        'unused_permissions' => 'Unused Permissions',
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'copied' => 'Copied to clipboard!',
    ],

    /*
    |--------------------------------------------------------------------------
    | Sections
    |--------------------------------------------------------------------------
    */
    'sections' => [
        'role_details' => 'Role Details',
        'role_details_description' => 'Enter the role name and select the guard.',
        'permission_group' => 'Manage permissions for :name',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tabs
    |--------------------------------------------------------------------------
    */
    'tabs' => [
        'resources' => 'Resources',
        'models' => 'Models (API)',
        'pages' => 'Pages',
        'widgets' => 'Widgets',
        'fields' => 'Fields',
        'columns' => 'Columns',
        'actions' => 'Actions',
        'relations' => 'Relations',
    ],

    /*
    |--------------------------------------------------------------------------
    | Placeholders
    |--------------------------------------------------------------------------
    */
    'placeholders' => [
        'no_permissions' => 'No permissions available',
        'no_permissions_hint' => 'You can sync permissions using the "Sync Permissions" action from the Roles list page, or run the command below directly.',
        'no_permissions_command' => 'php artisan gatekeeper:sync',
    ],

    /*
    |--------------------------------------------------------------------------
    | Actions
    |--------------------------------------------------------------------------
    */
    'actions' => [
        'sync_permissions' => 'Sync Permissions',
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'permissions_synced' => 'Permissions synced successfully!',
    ],

    /*
    |--------------------------------------------------------------------------
    | Messages
    |--------------------------------------------------------------------------
    */
    'messages' => [
        'unauthorized' => 'You are not authorized to perform this action.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Permission Types
    |--------------------------------------------------------------------------
    */
    'types' => [
        'resource' => 'Resource',
        'page' => 'Page',
        'widget' => 'Widget',
        'field' => 'Field',
        'column' => 'Column',
        'action' => 'Action',
        'relation' => 'Relation',
    ],

    /*
    |--------------------------------------------------------------------------
    | Permission Actions
    |--------------------------------------------------------------------------
    */
    'permission_actions' => [
        'view_any' => 'View Any',
        'view' => 'View',
        'create' => 'Create',
        'update' => 'Update',
        'delete' => 'Delete',
        'delete_any' => 'Delete Any',
        'restore' => 'Restore',
        'restore_any' => 'Restore Any',
        'force_delete' => 'Force Delete',
        'force_delete_any' => 'Force Delete Any',
        'replicate' => 'Replicate',
        'reorder' => 'Reorder',
    ],

    /*
    |--------------------------------------------------------------------------
    | Permission Descriptions (Tooltips)
    |--------------------------------------------------------------------------
    */
    'permission_descriptions' => [
        'view_any' => 'Can view the list of all records',
        'view' => 'Can view a single record',
        'create' => 'Can create new records',
        'update' => 'Can update existing records',
        'delete' => 'Can delete records',
        'restore' => 'Can restore soft-deleted records',
        'force_delete' => 'Can permanently delete records',
        'replicate' => 'Can duplicate/copy records',
        'reorder' => 'Can change the order of records',
    ],
];
