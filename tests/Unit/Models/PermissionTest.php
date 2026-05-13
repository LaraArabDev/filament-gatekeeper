<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Unit\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LaraArabDev\FilamentGatekeeper\Models\Permission;
use LaraArabDev\FilamentGatekeeper\Models\Role;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;

class PermissionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_permission(): void
    {
        $permission = Permission::factory()->create([
            'name' => 'view_any_user',
            'guard_name' => 'web',
            'type' => Permission::TYPE_RESOURCE,
        ]);

        $this->assertDatabaseHas('permissions', [
            'name' => 'view_any_user',
            'guard_name' => 'web',
            'type' => Permission::TYPE_RESOURCE,
        ]);
    }

    /** @test */
    public function it_can_create_permission_with_all_types(): void
    {
        $types = [
            Permission::TYPE_RESOURCE => 'view_any_user',
            Permission::TYPE_PAGE => 'view_dashboard_page',
            Permission::TYPE_WIDGET => 'view_stats_widget',
            Permission::TYPE_FIELD => 'view_user_email_field',
            Permission::TYPE_COLUMN => 'view_user_email_column',
            Permission::TYPE_ACTION => 'execute_user_export_action',
            Permission::TYPE_RELATION => 'view_user_roles_relation',
            Permission::TYPE_MODEL => 'view_product',
        ];

        foreach ($types as $type => $name) {
            Permission::factory()->create([
                'name' => $name,
                'guard_name' => 'web',
                'type' => $type,
            ]);
        }

        $this->assertEquals(count($types), Permission::count());
    }

    /** @test */
    public function it_can_scope_by_type(): void
    {
        Permission::factory()->resource()->create(['name' => 'view_any_user']);
        Permission::factory()->resource()->create(['name' => 'create_user']);
        Permission::factory()->page()->create(['name' => 'view_page_dashboard']);
        Permission::factory()->widget()->create(['name' => 'view_widget_stats']);
        Permission::factory()->field()->create(['name' => 'view_user_email_field']);
        Permission::factory()->column()->create(['name' => 'view_user_email_column']);
        Permission::factory()->action()->create(['name' => 'execute_user_export_action']);
        Permission::factory()->relation()->create(['name' => 'view_user_roles_relation']);
        Permission::factory()->model()->create(['name' => 'view_product']);

        $this->assertEquals(2, Permission::resources()->count());
        $this->assertEquals(1, Permission::pages()->count());
        $this->assertEquals(1, Permission::widgets()->count());
        $this->assertEquals(1, Permission::fields()->count());
        $this->assertEquals(1, Permission::columns()->count());
        $this->assertEquals(1, Permission::actions()->count());
        $this->assertEquals(1, Permission::relations()->count());
        $this->assertEquals(1, Permission::models()->count());
    }

    /** @test */
    public function it_can_scope_by_guard(): void
    {
        Permission::factory()->forGuard('web')->create(['name' => 'view_any_user_web']);
        Permission::factory()->forGuard('api')->create(['name' => 'view_any_user_api']);
        Permission::factory()->forGuard('api')->create(['name' => 'create_user_api']);

        $this->assertEquals(1, Permission::forGuard('web')->count());
        $this->assertEquals(2, Permission::forGuard('api')->count());
    }

    /** @test */
    public function it_can_scope_by_model(): void
    {
        Permission::factory()->forModel('User')->create(['name' => 'view_any_user']);
        Permission::factory()->forModel('User')->create(['name' => 'create_user']);
        Permission::factory()->forModel('Post')->create(['name' => 'view_any_post']);

        $this->assertEquals(2, Permission::forModel('User')->count());
        $this->assertEquals(1, Permission::forModel('Post')->count());
    }

    /** @test */
    public function it_can_get_model_name_from_permission(): void
    {
        $permission = Permission::factory()->resource()->create([
            'name' => 'view_any_user',
        ]);

        $this->assertEquals('User', $permission->getModelName());
    }

    /** @test */
    public function it_can_get_model_name_from_various_permission_formats(): void
    {
        $testCases = [
            ['view_any_user', 'User'],
            ['view_user', 'User'],
            ['create_user', 'User'],
            ['update_user', 'User'],
            ['delete_user', 'User'],
            ['force_delete_user', 'User'],
            ['restore_user', 'User'],
            ['replicate_user', 'User'],
            ['reorder_user', 'User'],
            ['view_any_blog_post', 'BlogPost'],
            ['create_order_item', 'OrderItem'],
        ];

        foreach ($testCases as [$permissionName, $expectedModel]) {
            $permission = Permission::factory()->resource()->create([
                'name' => $permissionName,
            ]);

            $this->assertEquals($expectedModel, $permission->getModelName(), "Failed for: {$permissionName}");

            $permission->delete();
        }
    }

    /** @test */
    public function it_can_get_action_from_permission(): void
    {
        $testCases = [
            ['view_any_user', 'view_any'],
            ['view_user', 'view'],
            ['create_user', 'create'],
            ['update_user', 'update'],
            ['delete_user', 'delete'],
            ['force_delete_user', 'force_delete'],
            ['restore_user', 'restore'],
            ['replicate_user', 'replicate'],
            ['reorder_user', 'reorder'],
        ];

        foreach ($testCases as [$permissionName, $expectedAction]) {
            $permission = Permission::factory()->resource()->create([
                'name' => $permissionName,
            ]);

            $this->assertEquals($expectedAction, $permission->getAction(), "Failed for: {$permissionName}");

            $permission->delete();
        }
    }

    /** @test */
    public function it_can_get_entity_name_for_resource_permission(): void
    {
        $permission = Permission::factory()->resource()->create([
            'name' => 'view_any_user',
        ]);

        $this->assertEquals('User', $permission->getEntityName());
    }

    /** @test */
    public function it_can_get_entity_name_for_page_permission(): void
    {
        $permission = Permission::factory()->page()->create([
            'name' => 'view_dashboard_page',
        ]);

        $this->assertEquals('Dashboard', $permission->getEntityName());
    }

    /** @test */
    public function it_can_get_entity_name_for_widget_permission(): void
    {
        $permission = Permission::factory()->widget()->create([
            'name' => 'view_stats_overview_widget',
        ]);

        $this->assertEquals('Stats Overview', $permission->getEntityName());
    }

    /** @test */
    public function it_can_get_entity_name_for_field_permission(): void
    {
        $permission = Permission::factory()->field()->create([
            'name' => 'view_user_email_field',
        ]);

        $entity = $permission->getEntityName();
        $this->assertStringContainsString('Email', $entity);
    }

    /** @test */
    public function it_can_check_permission_type(): void
    {
        $resourcePermission = Permission::factory()->resource()->create([
            'name' => 'view_any_user',
        ]);

        $pagePermission = Permission::factory()->page()->create([
            'name' => 'view_page_dashboard',
        ]);

        $widgetPermission = Permission::factory()->widget()->create([
            'name' => 'view_widget_stats',
        ]);

        $modelPermission = Permission::factory()->model()->create([
            'name' => 'view_product',
        ]);

        $this->assertTrue($resourcePermission->isResourcePermission());
        $this->assertFalse($resourcePermission->isPagePermission());

        $this->assertTrue($pagePermission->isPagePermission());
        $this->assertFalse($pagePermission->isResourcePermission());

        $this->assertTrue($widgetPermission->isWidgetPermission());
        $this->assertTrue($modelPermission->isModelPermission());
    }

    /** @test */
    public function it_returns_all_permission_types(): void
    {
        $types = Permission::getTypes();

        $this->assertArrayHasKey(Permission::TYPE_RESOURCE, $types);
        $this->assertArrayHasKey(Permission::TYPE_PAGE, $types);
        $this->assertArrayHasKey(Permission::TYPE_WIDGET, $types);
        $this->assertArrayHasKey(Permission::TYPE_FIELD, $types);
        $this->assertArrayHasKey(Permission::TYPE_COLUMN, $types);
        $this->assertArrayHasKey(Permission::TYPE_ACTION, $types);
        $this->assertArrayHasKey(Permission::TYPE_RELATION, $types);
        $this->assertArrayHasKey(Permission::TYPE_MODEL, $types);
    }

    /** @test */
    public function it_can_get_type_icon(): void
    {
        $icons = [
            Permission::TYPE_RESOURCE => 'heroicon-o-rectangle-stack',
            Permission::TYPE_PAGE => 'heroicon-o-document',
            Permission::TYPE_WIDGET => 'heroicon-o-chart-bar',
            Permission::TYPE_FIELD => 'heroicon-o-pencil-square',
            Permission::TYPE_COLUMN => 'heroicon-o-view-columns',
            Permission::TYPE_ACTION => 'heroicon-o-bolt',
            Permission::TYPE_RELATION => 'heroicon-o-link',
            Permission::TYPE_MODEL => 'heroicon-o-cube',
        ];

        foreach ($icons as $type => $expectedIcon) {
            $permission = Permission::factory()->create([
                'name' => "test_{$type}",
                'type' => $type,
            ]);

            $this->assertEquals($expectedIcon, $permission->getTypeIcon());
            $permission->delete();
        }
    }

    /** @test */
    public function it_can_get_type_color(): void
    {
        $colors = [
            Permission::TYPE_RESOURCE => 'primary',
            Permission::TYPE_PAGE => 'success',
            Permission::TYPE_WIDGET => 'warning',
            Permission::TYPE_FIELD => 'info',
            Permission::TYPE_COLUMN => 'gray',
            Permission::TYPE_ACTION => 'danger',
            Permission::TYPE_RELATION => 'purple',
            Permission::TYPE_MODEL => 'cyan',
        ];

        foreach ($colors as $type => $expectedColor) {
            $permission = Permission::factory()->create([
                'name' => "test_{$type}",
                'type' => $type,
            ]);

            $this->assertEquals($expectedColor, $permission->getTypeColor());
            $permission->delete();
        }
    }

    /** @test */
    public function it_can_be_assigned_to_role(): void
    {
        $permission = Permission::factory()->resource()->create([
            'name' => 'view_any_user',
        ]);

        $role = Role::factory()->create([
            'name' => 'admin',
        ]);

        $role->givePermissionTo($permission);

        $this->assertTrue($role->hasPermissionTo('view_any_user'));
        $this->assertEquals(1, $permission->roles()->count());
    }

    /** @test */
    public function it_has_fillable_attributes(): void
    {
        $permission = new Permission();

        $this->assertTrue(in_array('name', $permission->getFillable()));
        $this->assertTrue(in_array('guard_name', $permission->getFillable()));
        $this->assertTrue(in_array('type', $permission->getFillable()));
    }

    /** @test */
    public function it_getTypeEnum_returns_null_for_null_type(): void
    {
        $permission = new Permission(['name' => 'test_perm_null', 'type' => null]);
        $this->assertNull($permission->getTypeEnum());
    }

    /** @test */
    public function it_getTypeEnum_returns_null_for_empty_type(): void
    {
        $permission = new Permission(['name' => 'test_perm_empty', 'type' => '']);
        $this->assertNull($permission->getTypeEnum());
    }

    /** @test */
    public function it_getEntityName_for_page_type_with_suffix(): void
    {
        $permission = Permission::factory()->page()->create([
            'name' => 'view_settings_page',
            'entity' => null,
        ]);
        $result = $permission->getEntityName();
        $this->assertNotNull($result);
    }

    /** @test */
    public function it_getEntityName_for_widget_type(): void
    {
        $permission = Permission::factory()->widget()->create([
            'name' => 'view_stats_overview_widget',
            'entity' => null,
        ]);
        $result = $permission->getEntityName();
        $this->assertNotNull($result);
    }

    /** @test */
    public function it_getEntityName_for_field_type(): void
    {
        $permission = Permission::factory()->field()->create([
            'name' => 'view_user_email_field',
            'entity' => null,
        ]);
        $result = $permission->getEntityName();
        $this->assertStringContainsString('Field', $result);
    }

    /** @test */
    public function it_getEntityName_for_column_type(): void
    {
        $permission = Permission::factory()->column()->create([
            'name' => 'view_user_salary_column',
            'entity' => null,
        ]);
        $result = $permission->getEntityName();
        $this->assertStringContainsString('Column', $result);
    }

    /** @test */
    public function it_getEntityName_for_action_type(): void
    {
        $permission = Permission::factory()->action()->create([
            'name' => 'execute_user_export_action',
            'entity' => null,
        ]);
        $result = $permission->getEntityName();
        $this->assertStringContainsString('Action', $result);
    }

    /** @test */
    public function it_getEntityName_for_relation_type(): void
    {
        $permission = Permission::factory()->relation()->create([
            'name' => 'view_user_posts_relation',
            'entity' => null,
        ]);
        $result = $permission->getEntityName();
        $this->assertStringContainsString('Relation', $result);
    }

    /** @test */
    public function it_getEntityGroupKey_uses_entity_column_when_set(): void
    {
        $permission = Permission::factory()->resource()->create([
            'name' => 'view_any_user',
            'entity' => 'User',
        ]);
        $this->assertEquals('user', $permission->getEntityGroupKey());
    }

    /** @test */
    public function it_getEntityGroupKey_returns_other_for_empty_model(): void
    {
        $permission = Permission::factory()->create([
            'name' => 'x',
            'type' => Permission::TYPE_RESOURCE,
            'entity' => null,
        ]);
        // 'x' has no underscore so getModelName returns null → 'other'
        $this->assertEquals('other', $permission->getEntityGroupKey());
    }

    /** @test */
    public function it_getEntityDisplayName_uses_entity_column(): void
    {
        $permission = Permission::factory()->resource()->create([
            'name' => 'view_any_user',
            'entity' => 'user',
        ]);
        $this->assertEquals('User', $permission->getEntityDisplayName());
    }

    /** @test */
    public function it_getEntityDisplayName_falls_back_to_getEntityName(): void
    {
        $permission = Permission::factory()->resource()->create([
            'name' => 'view_any_user',
            'entity' => null,
        ]);
        // Should return something non-null
        $result = $permission->getEntityDisplayName();
        $this->assertNotNull($result);
    }

    /** @test */
    public function it_getAction_returns_null_for_single_part_name(): void
    {
        $permission = new Permission(['name' => 'admin', 'type' => null]);
        $this->assertNull($permission->getAction());
    }

    /** @test */
    public function it_getAction_returns_prefix_for_resource_permission(): void
    {
        $permission = Permission::factory()->resource()->create(['name' => 'view_any_user']);
        $result = $permission->getAction();
        $this->assertNotNull($result);
        $this->assertStringContainsString('view', $result);
    }

    /** @test */
    public function it_getActionLabel_for_field_type_is_headline(): void
    {
        $permission = Permission::factory()->field()->create([
            'name' => 'view_user_email_field',
            'entity' => 'user',
        ]);
        $label = $permission->getActionLabel();
        $this->assertNotEmpty($label);
    }

    /** @test */
    public function it_getActionLabel_for_resource_type(): void
    {
        $permission = Permission::factory()->resource()->create([
            'name' => 'view_any_user',
            'entity' => 'user',
        ]);
        $label = $permission->getActionLabel();
        $this->assertNotEmpty($label);
    }

    /** @test */
    public function it_getTypeIcon_returns_default_for_null_type(): void
    {
        $permission = new Permission(['name' => 'test_icon_null', 'type' => null]);
        $this->assertEquals('heroicon-o-shield-check', $permission->getTypeIcon());
    }

    /** @test */
    public function it_getTypeColor_returns_default_for_null_type(): void
    {
        $permission = new Permission(['name' => 'test_color_null', 'type' => null]);
        $this->assertEquals('gray', $permission->getTypeColor());
    }

    /** @test */
    public function it_scopeMatching_filters_by_pattern(): void
    {
        Permission::factory()->resource()->create(['name' => 'view_any_user']);
        Permission::factory()->resource()->create(['name' => 'create_user']);
        Permission::factory()->resource()->create(['name' => 'view_any_post']);

        $results = Permission::matching('view_%')->get();

        $this->assertTrue($results->contains('name', 'view_any_user'));
        $this->assertTrue($results->contains('name', 'view_any_post'));
        $this->assertFalse($results->contains('name', 'create_user'));
    }

    /** @test */
    public function it_scopeForEntity_filters_by_entity(): void
    {
        Permission::factory()->resource()->create(['name' => 'view_any_user', 'entity' => 'user']);
        Permission::factory()->resource()->create(['name' => 'create_user', 'entity' => 'user']);
        Permission::factory()->resource()->create(['name' => 'view_any_post', 'entity' => 'post']);

        $results = Permission::forEntity('user')->get();

        $this->assertEquals(2, $results->count());
        $this->assertTrue($results->every(fn($p) => $p->entity === 'user'));
    }

    /** @test */
    public function it_getDistinctEntityOptionsForFilter_returns_array(): void
    {
        Permission::factory()->resource()->create(['name' => 'view_any_user', 'entity' => 'user']);
        Permission::factory()->resource()->create(['name' => 'view_any_post', 'entity' => 'post']);

        $options = Permission::getDistinctEntityOptionsForFilter();

        $this->assertIsArray($options);
        $this->assertArrayHasKey('user', $options);
        $this->assertArrayHasKey('post', $options);
    }

    /** @test */
    public function it_getModelName_handles_snake_case_multi_word(): void
    {
        $permission = Permission::factory()->resource()->create([
            'name' => 'view_any_blog_post',
            'entity' => null,
        ]);
        $name = $permission->getModelName();
        $this->assertNotNull($name);
    }

    /** @test */
    public function it_scopeForGuard_filters_by_guard(): void
    {
        Permission::factory()->forGuard('web')->resource()->create(['name' => 'view_any_user_web_only']);
        Permission::factory()->forGuard('api')->resource()->create(['name' => 'view_any_user_api_only']);

        $webPerms = Permission::forGuard('web')->get();
        $apiPerms = Permission::forGuard('api')->get();

        $this->assertTrue($webPerms->every(fn($p) => $p->guard_name === 'web'));
        $this->assertTrue($apiPerms->every(fn($p) => $p->guard_name === 'api'));
    }
}
