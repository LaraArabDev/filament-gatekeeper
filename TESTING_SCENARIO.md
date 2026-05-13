# Complete Testing Scenario - Filament Gatekeeper

This document provides a comprehensive **manual testing scenario** to verify all package features from the Filament dashboard UI.

> **Note:** This is a **manual testing guide** for dashboard UI features. For automated unit/feature tests, see the `tests/` directory. The automated tests cover backend logic, while this guide covers UI interactions and dashboard functionality.

## Table of Contents

- [Prerequisites](#prerequisites)
- [Initial Setup](#initial-setup)
- [Role Management Testing](#role-management-testing)
- [Permission Management Testing](#permission-management-testing)
- [Field Permissions Testing](#field-permissions-testing)
- [Column Permissions Testing](#column-permissions-testing)
- [Action Permissions Testing](#action-permissions-testing)
- [Page Permissions Testing](#page-permissions-testing)
- [Widget Permissions Testing](#widget-permissions-testing)
- [Relation Permissions Testing](#relation-permissions-testing)
- [Super Admin Testing](#super-admin-testing)
- [Multi-Guard Testing](#multi-guard-testing)
- [Auto-Discovery Testing](#auto-discovery-testing)
- [Permission Sync Testing](#permission-sync-testing)
- [Cache Testing](#cache-testing)
- [Localization Testing](#localization-testing)

## Prerequisites

Before starting, ensure:

1. ✅ Spatie Laravel Permission is installed and configured
2. ✅ Shield Manager is installed and configured
3. ✅ Migrations are run
4. ✅ Plugin is registered in Filament Panel Provider
5. ✅ User model has `HasRoles` trait
6. ✅ At least one Filament Resource exists (e.g., UserResource)

## Initial Setup

### Step 1: Verify Installation

1. Navigate to your Filament admin panel
2. Check navigation menu for:
   - ✅ "Roles" menu item
   - ✅ "Permissions" menu item
3. Both should be visible in the navigation group you configured

### Step 2: Create Test Data

Create test models and resources:

```bash
# Create a test model
php artisan make:model Product -m

# Create a Filament resource
php artisan make:filament-resource Product
```

### Step 3: Configure Test Permissions

Update `config/gatekeeper.php`:

```php
'field_permissions' => [
    'User' => ['email', 'password', 'salary', 'phone', 'address'],
    'Product' => ['name', 'price', 'description', 'stock'],
],

'column_permissions' => [
    'User' => ['email', 'phone', 'salary', 'created_at'],
    'Product' => ['name', 'price', 'stock', 'created_at'],
],

'custom_actions' => [
    'User' => ['export', 'impersonate', 'suspend', 'activate'],
    'Product' => ['publish', 'unpublish', 'duplicate'],
],

'relation_permissions' => [
    'User' => ['roles', 'posts', 'orders'],
    'Product' => ['category', 'reviews', 'tags'],
],
```

### Step 4: Sync Permissions

1. Navigate to **Roles** page
2. Click **"Sync Permissions"** action button (or run `php artisan gatekeeper:sync`)
3. Verify success message appears
4. Check that permissions are created in database

## Role Management Testing

### Test 1: Create Role

1. Navigate to **Roles** page
2. Click **"New Role"** button
3. Fill in form:
   - **Name**: `editor`
   - **Guard**: `web`
   - **Description**: `Can edit content but not delete`
4. Click **"Create"**
5. ✅ Verify role is created and appears in table

### Test 2: View Role Details

1. Click on a role name in the table
2. ✅ Verify role details page shows:
   - Role name
   - Guard name
   - Description
   - Permissions grouped by entity
   - Field permissions section (if enabled)
   - Column permissions section (if enabled)

### Test 3: Edit Role

1. Click **"Edit"** action on a role
2. Change description
3. Click **"Save"**
4. ✅ Verify changes are saved

### Test 4: Assign Permissions to Role

1. Open a role for editing
2. In **Permissions** section:
   - ✅ Expand "User" entity section
   - ✅ Check `view_any_user` permission
   - ✅ Check `create_user` permission
   - ✅ Check `update_user` permission
   - ✅ Uncheck `delete_user` permission
3. In **Field Permissions** section (if enabled):
   - ✅ Expand "User" model
   - ✅ Check `view` for `email` field
   - ✅ Check `update` for `email` field
   - ✅ Check `view` for `salary` field
   - ✅ Uncheck `update` for `salary` field
4. In **Column Permissions** section (if enabled):
   - ✅ Expand "User" model
   - ✅ Check `view` for `email` column
   - ✅ Uncheck `view` for `salary` column
5. Click **"Save"**
6. ✅ Verify permissions are assigned correctly

### Test 5: Filter Permissions by Entity

1. In role edit page, use search/filter
2. ✅ Type "User" in search
3. ✅ Verify only User-related permissions are shown
4. ✅ Type "Product" in search
5. ✅ Verify only Product-related permissions are shown

### Test 6: Collapsible Sections

1. In role edit page, permissions section
2. ✅ Click on "User" entity header
3. ✅ Verify section collapses/expands
4. ✅ Verify icons change (chevron up/down)

### Test 7: Delete Role

1. Click **"Delete"** action on a test role
2. ✅ Verify confirmation dialog appears
3. Confirm deletion
4. ✅ Verify role is deleted
5. ✅ Verify associated permissions are removed from users

### Test 8: Bulk Actions (if available)

1. Select multiple roles using checkboxes
2. ✅ Verify bulk actions appear
3. Test bulk delete (if available)
4. ✅ Verify selected roles are deleted

## Permission Management Testing

### Test 1: View Permissions Table

1. Navigate to **Permissions** page
2. ✅ Verify table displays:
   - Permission name
   - Entity name (User, Product, etc.)
   - Action (view, create, update, etc.)
   - Type (resource, field, column, action, relation)
   - Guard name
   - Roles count (number of roles with this permission)
   - Icons for type
   - Color badges for actions

### Test 2: Search Permissions

1. In permissions table, use search box
2. ✅ Type "user" - verify only user-related permissions shown
3. ✅ Type "field" - verify only field permissions shown
4. ✅ Type "view_any" - verify permissions starting with "view_any" shown
5. ✅ Clear search - verify all permissions shown

### Test 3: Filter by Type

1. Click **"Type"** filter dropdown
2. ✅ Select "Resource" - verify only resource permissions shown
3. ✅ Select "Field" - verify only field permissions shown
4. ✅ Select "Column" - verify only column permissions shown
5. ✅ Select "Action" - verify only action permissions shown
6. ✅ Select "Relation" - verify only relation permissions shown
7. ✅ Select "Page" - verify only page permissions shown
8. ✅ Select "Widget" - verify only widget permissions shown
9. ✅ Select "Model" - verify only model permissions shown

### Test 4: Filter by Guard

1. Click **"Guard"** filter dropdown
2. ✅ Select "web" - verify only web guard permissions shown
3. ✅ Select "api" - verify only api guard permissions shown
4. ✅ Clear filter - verify all permissions shown

### Test 5: Filter by Entity

1. Click **"Entity"** filter dropdown
2. ✅ Select "User" - verify only User entity permissions shown
3. ✅ Select "Product" - verify only Product entity permissions shown
4. ✅ Verify entity names match permission types correctly

### Test 6: Filter Unused Permissions

1. Click **"Unused Permissions"** toggle filter
2. ✅ Verify only permissions with 0 roles are shown
3. ✅ Toggle off - verify all permissions shown

### Test 7: Sort Permissions

1. Click column headers to sort
2. ✅ Click "Name" - verify sorted alphabetically
3. ✅ Click "Type" - verify sorted by type
4. ✅ Click "Roles" - verify sorted by role count
5. ✅ Verify sort direction changes on second click

### Test 8: Pagination

1. If many permissions exist, verify pagination appears
2. ✅ Click page numbers - verify page changes
3. ✅ Change items per page (25, 50, 100, all)
4. ✅ Verify correct number of items displayed

### Test 9: View Permission Details

1. Click **"View"** action on a permission
2. ✅ Verify permission details modal/page shows:
   - Permission name
   - Entity name
   - Action
   - Type
   - Guard
   - Roles using this permission
   - Created/Updated dates

### Test 10: Copy Permission Name

1. Click copy icon next to permission name
2. ✅ Verify permission name is copied to clipboard
3. ✅ Paste in text editor - verify correct name

### Test 11: Delete Permission

1. Click **"Delete"** action on a permission
2. ✅ Verify confirmation dialog
3. Confirm deletion
4. ✅ Verify permission is deleted
5. ✅ Verify permission removed from all roles

### Test 12: Auto-Refresh

1. Open permissions page
2. ✅ Wait 60 seconds (if poll enabled)
3. ✅ Verify page auto-refreshes
4. ✅ Verify new permissions appear if synced elsewhere

## Field Permissions Testing

### Test 1: Configure Field Permissions

1. Update `config/gatekeeper.php`:
```php
'field_permissions' => [
    'User' => ['email', 'password', 'salary', 'phone'],
],
```

2. Run `php artisan gatekeeper:sync`
3. ✅ Verify field permissions are created:
   - `view_field_user_email`
   - `update_field_user_email`
   - `view_field_user_salary`
   - `update_field_user_salary`
   - etc.

### Test 2: Assign Field Permissions to Role

1. Edit a role
2. Scroll to **Field Permissions** section
3. ✅ Expand "User" model
4. ✅ For `email` field:
   - Check `view` checkbox
   - Check `update` checkbox
5. ✅ For `salary` field:
   - Check `view` checkbox
   - Uncheck `update` checkbox (read-only)
6. Save role
7. ✅ Verify permissions are saved

### Test 3: Test Field Visibility in Resource

1. Assign role with field permissions to a test user
2. Login as that user
3. Navigate to User Resource
4. Create/Edit a user
5. ✅ Verify:
   - Fields with `view` permission are visible
   - Fields without `view` permission are hidden
   - Fields with `update` permission are editable
   - Fields without `update` permission are disabled (read-only)

### Test 4: Field Auto-Discovery

1. Enable field discovery in config:
```php
'field_discovery' => [
    'enabled' => true,
    'sources' => ['fillable'],
],
```

2. Add fields to model `$fillable`:
```php
protected $fillable = ['name', 'email', 'phone', 'address'];
```

3. Run `php artisan gatekeeper:sync`
4. ✅ Verify field permissions are auto-created for fillable fields

### Test 5: Field Exclusions

1. Configure exclusions:
```php
'field_discovery' => [
    'excluded' => [
        '*' => ['password', 'remember_token'],
        'User' => ['api_token'],
    ],
],
```

2. Run sync
3. ✅ Verify excluded fields don't get permissions created

## Column Permissions Testing

### Test 1: Configure Column Permissions

1. Update config:
```php
'column_permissions' => [
    'User' => ['email', 'phone', 'salary', 'created_at'],
],
```

2. Run `php artisan gatekeeper:sync`
3. ✅ Verify column permissions created:
   - `view_column_user_email`
   - `view_column_user_phone`
   - `view_column_user_salary`
   - etc.

### Test 2: Assign Column Permissions to Role

1. Edit a role
2. Scroll to **Column Permissions** section
3. ✅ Expand "User" model
4. ✅ Check columns user should see:
   - Check `email`
   - Check `phone`
   - Uncheck `salary` (hidden)
5. Save role
6. ✅ Verify permissions saved

### Test 3: Test Column Visibility in Resource

1. Assign role to test user
2. Login as that user
3. Navigate to User Resource table
4. ✅ Verify:
   - Columns with permission are visible
   - Columns without permission are hidden

### Test 4: Column Auto-Discovery

1. Enable column discovery:
```php
'column_discovery' => [
    'enabled' => true,
    'sources' => ['database'],
],
```

2. Run sync
3. ✅ Verify permissions created for all database columns (except excluded)

## Action Permissions Testing

### Test 1: Configure Custom Actions

1. Update config:
```php
'custom_actions' => [
    'User' => ['export', 'impersonate', 'suspend'],
],
```

2. Run `php artisan gatekeeper:sync`
3. ✅ Verify action permissions created:
   - `execute_user_export_action`
   - `execute_user_impersonate_action`
   - `execute_user_suspend_action`

### Test 2: Assign Action Permissions

1. Edit a role
2. ✅ Find action permissions in permissions list
3. ✅ Check actions role should have
4. Save role

### Test 3: Test Action Visibility

1. In UserResource, add actions:
```php
public static function table(Table $table): Table
{
    return $table
        ->actions([
            Action::make('export')
                ->visible(fn () => static::canExecuteAction('export')),
            Action::make('impersonate')
                ->visible(fn () => static::canExecuteAction('impersonate')),
        ]);
}
```

2. Assign role to user
3. Login and view User table
4. ✅ Verify:
   - Actions with permission are visible
   - Actions without permission are hidden

## Page Permissions Testing

### Test 1: Create Page with Permissions

1. Create a Filament page:
```php
use LaraArabDev\FilamentGatekeeper\Base\GatekeeperPage;

class SettingsPage extends GatekeeperPage
{
    protected static string $view = 'filament.pages.settings';
}
```

2. Run `php artisan gatekeeper:sync`
3. ✅ Verify permission created: `view_settings_page`

### Test 2: Test Page Access

1. Assign permission to role
2. Assign role to user
3. Login as user
4. ✅ Verify:
   - User with permission can access page
   - User without permission cannot access page (redirected or 403)

## Widget Permissions Testing

### Test 1: Create Widget with Permissions

1. Create widget:
```php
use LaraArabDev\FilamentGatekeeper\Base\GatekeeperWidget;

class StatsOverview extends GatekeeperWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Users', User::count()),
        ];
    }
}
```

2. Run sync
3. ✅ Verify permission created: `view_stats_overview_widget`

### Test 2: Test Widget Visibility

1. Assign permission to role
2. Assign role to user
3. Login as user
4. ✅ Verify widget appears for users with permission
5. ✅ Verify widget hidden for users without permission

## Relation Permissions Testing

### Test 1: Configure Relation Permissions

1. Update config:
```php
'relation_permissions' => [
    'User' => ['roles', 'posts', 'orders'],
],
```

2. Run sync
3. ✅ Verify permissions created:
   - `view_relation_user_roles`
   - `view_relation_user_posts`
   - `view_relation_user_orders`

### Test 2: Test Relation Manager Access

1. In UserResource:
```php
use LaraArabDev\FilamentGatekeeper\Concerns\HasRelationPermissions;

public static function getRelations(): array
{
    return static::getPermittedRelations([
        RolesRelationManager::class,
        PostsRelationManager::class,
    ]);
}
```

2. Assign permissions to role
3. Login as user
4. ✅ Verify:
   - Relations with permission are accessible
   - Relations without permission are hidden

## Super Admin Testing

### Test 1: Create Super Admin Role

1. Run `php artisan gatekeeper:sync`
2. ✅ Verify super admin role is created (default: `super-admin`)
3. ✅ Verify all permissions are assigned to super admin

### Test 2: Assign Super Admin to User

1. Edit a user
2. Assign `super-admin` role
3. ✅ Verify user has super admin role

### Test 3: Test Super Admin Bypass

1. Login as super admin user
2. ✅ Verify:
   - Can access all resources
   - Can view all fields
   - Can update all fields
   - Can view all columns
   - Can execute all actions
   - Can access all pages
   - Can view all widgets
   - Can access all relations

### Test 4: Disable Super Admin Bypass

1. In plugin config:
```php
GatekeeperPlugin::make()
    ->bypassForSuperAdmin(false)
```

2. Login as super admin
3. ✅ Verify super admin must have explicit permissions

## Multi-Guard Testing

### Test 1: Configure Multiple Guards

1. Update config:
```php
'guards' => [
    'web' => ['enabled' => true],
    'api' => ['enabled' => true],
],
```

2. Run sync
3. ✅ Verify permissions created for both guards

### Test 2: Create Role for API Guard

1. Create new role
2. Set guard to `api`
3. Assign permissions
4. ✅ Verify role is guard-specific

### Test 3: Test Guard Separation

1. Create `web` guard role with permissions
2. Create `api` guard role with different permissions
3. ✅ Verify:
   - Web user can only use web permissions
   - API user can only use api permissions
   - Guards are properly separated

## Auto-Discovery Testing

### Test 1: Field Discovery from Fillable

1. Configure:
```php
'field_discovery' => [
    'enabled' => true,
    'sources' => ['fillable'],
],
```

2. Add to model:
```php
protected $fillable = ['name', 'email', 'phone', 'address'];
```

3. Run sync
4. ✅ Verify field permissions created for all fillable fields

### Test 2: Field Discovery from Database

1. Configure:
```php
'field_discovery' => [
    'enabled' => true,
    'sources' => ['database'],
],
```

2. Run sync
3. ✅ Verify permissions for all database columns

### Test 3: Field Discovery from Resource

1. Configure:
```php
'field_discovery' => [
    'enabled' => true,
    'sources' => ['resource'],
],
```

2. Ensure resource has form() method with fields
3. Run sync
4. ✅ Verify permissions for fields in resource form

### Test 4: Column Discovery from Database

1. Configure:
```php
'column_discovery' => [
    'enabled' => true,
    'sources' => ['database'],
],
```

2. Run sync
3. ✅ Verify column permissions for all table columns

### Test 5: Column Discovery from Resource

1. Configure:
```php
'column_discovery' => [
    'enabled' => true,
    'sources' => ['resource'],
],
```

2. Ensure resource has table() method with columns
3. Run sync
4. ✅ Verify permissions for columns in resource table

### Test 6: Multiple Sources Priority

1. Configure:
```php
'field_discovery' => [
    'sources' => ['config', 'fillable', 'database'],
],
```

2. Set config for User model
3. Run sync
4. ✅ Verify config takes priority (config first, then fillable, then database)

## Permission Sync Testing

### Test 1: Sync All Permissions

1. Navigate to Roles page
2. Click **"Sync Permissions"** action
3. ✅ Verify:
   - Success message appears
   - All permissions are synced
   - Log shows what was created/updated

### Test 2: Sync Specific Type

1. Run command: `php artisan gatekeeper:sync --only=resources`
2. ✅ Verify only resource permissions synced

3. Run: `php artisan gatekeeper:sync --only=fields`
4. ✅ Verify only field permissions synced

5. Run: `php artisan gatekeeper:sync --only=columns`
6. ✅ Verify only column permissions synced

### Test 3: Dry Run Mode

1. Run: `php artisan gatekeeper:sync --dry-run`
2. ✅ Verify:
   - Shows what would be created
   - No actual changes made to database

### Test 4: Force Resync

1. Delete some permissions manually
2. Run: `php artisan gatekeeper:sync`
3. ✅ Verify deleted permissions are recreated

## Cache Testing

### Test 1: Clear Cache

1. Assign permissions to role
2. Assign role to user
3. Login and verify permissions work
4. Remove permission from role
5. ✅ Verify user still has access (cached)
6. Run: `php artisan gatekeeper:clear-cache`
7. ✅ Verify user no longer has access (cache cleared)

### Test 2: Cache Invalidation on Role Update

1. Edit a role and change permissions
2. Save role
3. ✅ Verify cache is automatically cleared
4. Login as user with that role
5. ✅ Verify new permissions are active immediately

## Localization Testing

### Test 1: English Language

1. Set app locale to `en`
2. Navigate to Roles/Permissions pages
3. ✅ Verify all labels are in English

### Test 2: Arabic Language

1. Set app locale to `ar`
2. Navigate to Roles/Permissions pages
3. ✅ Verify all labels are in Arabic

### Test 3: Custom Translations

1. Publish language files:
```bash
php artisan vendor:publish --tag=gatekeeper-lang
```

2. Edit translation files
3. ✅ Verify custom translations appear

## Advanced Scenarios

### Scenario 1: Complex Role Hierarchy

1. Create roles:
   - `super-admin` (all permissions)
   - `admin` (most permissions, no delete)
   - `editor` (create, update, view)
   - `viewer` (view only)

2. Assign to different users
3. ✅ Test each user's access level

### Scenario 2: Field-Level Security

1. Create role with:
   - Can view `email` but not `salary`
   - Can update `email` but `salary` is read-only
2. Assign to user
3. ✅ Verify field visibility and editability

### Scenario 3: Column-Level Security

1. Create role with:
   - Can view `name`, `email`, `phone`
   - Cannot view `salary`, `ssn`
2. Assign to user
3. ✅ Verify column visibility in table

### Scenario 4: Action Restrictions

1. Create role with:
   - Can execute `export` action
   - Cannot execute `delete` action
2. Assign to user
3. ✅ Verify action visibility

### Scenario 5: Multi-Model Permissions

1. Configure permissions for multiple models:
   - User model
   - Product model
   - Order model
2. Create role with permissions for all models
3. ✅ Verify permissions work across all models

### Scenario 6: Guard-Specific Permissions

1. Create permissions for `web` guard
2. Create different permissions for `api` guard
3. Create users for each guard
4. ✅ Verify guard separation works correctly

## Checklist Summary

Use this checklist to ensure all features are tested:

### Role Management
- [ ] Create role
- [ ] Edit role
- [ ] Delete role
- [ ] View role details
- [ ] Assign permissions to role
- [ ] Assign field permissions to role
- [ ] Assign column permissions to role
- [ ] Filter permissions by entity
- [ ] Collapsible sections work
- [ ] Search functionality

### Permission Management
- [ ] View permissions table
- [ ] Search permissions
- [ ] Filter by type
- [ ] Filter by guard
- [ ] Filter by entity
- [ ] Filter unused permissions
- [ ] Sort permissions
- [ ] Pagination works
- [ ] View permission details
- [ ] Copy permission name
- [ ] Delete permission
- [ ] Auto-refresh (if enabled)

### Field Permissions
- [ ] Field permissions created
- [ ] Assign field permissions to role
- [ ] Field visibility in forms
- [ ] Field editability in forms
- [ ] Auto-discovery from fillable
- [ ] Auto-discovery from database
- [ ] Auto-discovery from resource
- [ ] Field exclusions work

### Column Permissions
- [ ] Column permissions created
- [ ] Assign column permissions to role
- [ ] Column visibility in tables
- [ ] Auto-discovery from database
- [ ] Auto-discovery from resource
- [ ] Column exclusions work

### Action Permissions
- [ ] Action permissions created
- [ ] Assign action permissions to role
- [ ] Action visibility in resources
- [ ] Action execution works

### Page Permissions
- [ ] Page permissions created
- [ ] Page access control works
- [ ] Navigation visibility works

### Widget Permissions
- [ ] Widget permissions created
- [ ] Widget visibility works

### Relation Permissions
- [ ] Relation permissions created
- [ ] Relation manager access works

### Super Admin
- [ ] Super admin role created
- [ ] All permissions assigned to super admin
- [ ] Super admin bypass works
- [ ] Can disable super admin bypass

### Multi-Guard
- [ ] Multiple guards configured
- [ ] Guard-specific permissions work
- [ ] Guard separation works

### Auto-Discovery
- [ ] Field discovery from fillable
- [ ] Field discovery from database
- [ ] Field discovery from resource
- [ ] Column discovery from database
- [ ] Column discovery from resource
- [ ] Source priority works
- [ ] Exclusions work

### Sync & Cache
- [ ] Sync all permissions
- [ ] Sync specific types
- [ ] Dry run mode
- [ ] Force resync
- [ ] Clear cache
- [ ] Cache invalidation

### Localization
- [ ] English translations
- [ ] Arabic translations
- [ ] Custom translations

## Troubleshooting

### Issue: Permissions not appearing

**Solution:**
1. Run `php artisan gatekeeper:sync`
2. Clear cache: `php artisan gatekeeper:clear-cache`
3. Check config for correct model names

### Issue: Field permissions not working

**Solution:**
1. Verify trait is used: `HasFieldPermissions`
2. Check field names match config
3. Verify permissions are synced
4. Check role has field permissions assigned

### Issue: Super admin not bypassing

**Solution:**
1. Check plugin config: `->bypassForSuperAdmin(true)`
2. Verify super admin role name matches config
3. Check user has super admin role assigned
4. Verify guard matches

### Issue: Auto-discovery not working

**Solution:**
1. Check discovery is enabled in config
2. Verify sources are correct
3. Check model has fillable property (for fillable source)
4. Verify resource methods exist (for resource source)

## Notes

- Always test with different user roles
- Test both positive (has permission) and negative (no permission) cases
- Verify UI updates reflect permission changes immediately
- Test edge cases (empty permissions, all permissions, etc.)
- Keep test data consistent for reproducible results

