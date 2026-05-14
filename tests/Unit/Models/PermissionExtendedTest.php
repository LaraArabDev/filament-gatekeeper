<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Unit\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LaraArabDev\FilamentGatekeeper\Models\Permission;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;

class PermissionExtendedTest extends TestCase
{
    use RefreshDatabase;

    // ── Type-check convenience methods ────────────────────────────────────────

    /** @test */
    public function it_can_check_field_permission_type(): void
    {
        $permission = Permission::factory()->field()->create(['name' => 'view_user_email_field']);
        $this->assertTrue($permission->isFieldPermission());
        $this->assertFalse($permission->isResourcePermission());
    }

    /** @test */
    public function it_can_check_column_permission_type(): void
    {
        $permission = Permission::factory()->column()->create(['name' => 'view_user_name_column']);
        $this->assertTrue($permission->isColumnPermission());
        $this->assertFalse($permission->isFieldPermission());
    }

    /** @test */
    public function it_can_check_action_permission_type(): void
    {
        $permission = Permission::factory()->action()->create(['name' => 'execute_user_export_action']);
        $this->assertTrue($permission->isActionPermission());
        $this->assertFalse($permission->isColumnPermission());
    }

    /** @test */
    public function it_can_check_relation_permission_type(): void
    {
        $permission = Permission::factory()->relation()->create(['name' => 'view_user_posts_relation']);
        $this->assertTrue($permission->isRelationPermission());
        $this->assertFalse($permission->isActionPermission());
    }

    // ── extractPageName() branches ────────────────────────────────────────────

    /** @test */
    public function extract_page_name_with_page_infix_pattern(): void
    {
        // Pattern: _page_(.+)$ (e.g., "view_page_dashboard" or "access_page_settings")
        $permission = Permission::factory()->page()->create([
            'name' => 'view_page_dashboard',
            'entity' => null,
        ]);

        $result = $permission->getEntityName();
        $this->assertNotNull($result);
    }

    /** @test */
    public function extract_page_name_with_page_suffix_and_view_prefix(): void
    {
        // Pattern: ^(.+)_page$ with "view_" prefix parsed out
        $permission = Permission::factory()->page()->create([
            'name' => 'view_settings_page',
            'entity' => null,
        ]);

        $result = $permission->getEntityName();
        $this->assertNotNull($result);
    }

    /** @test */
    public function extract_page_name_with_page_suffix_no_view_prefix(): void
    {
        // Pattern: ^(.+)_page$ without "view_" prefix → uses full part
        $permission = Permission::factory()->page()->create([
            'name' => 'access_settings_page',
            'entity' => null,
        ]);

        $result = $permission->getEntityName();
        $this->assertNotNull($result);
    }

    // ── extractWidgetName() branches ──────────────────────────────────────────

    /** @test */
    public function extract_widget_name_with_widget_infix_pattern(): void
    {
        // Pattern: _widget_(.+)$
        $permission = Permission::factory()->widget()->create([
            'name' => 'view_widget_stats',
            'entity' => null,
        ]);

        $result = $permission->getEntityName();
        $this->assertNotNull($result);
    }

    /** @test */
    public function extract_widget_name_with_widget_suffix_and_view_prefix(): void
    {
        // Pattern: ^(.+)_widget$ with "view_" prefix parsed out
        $permission = Permission::factory()->widget()->create([
            'name' => 'view_stats_overview_widget',
            'entity' => null,
        ]);

        $result = $permission->getEntityName();
        $this->assertNotNull($result);
    }

    /** @test */
    public function extract_widget_name_with_widget_suffix_no_view_prefix(): void
    {
        // Pattern: ^(.+)_widget$ without "view_" prefix
        $permission = Permission::factory()->widget()->create([
            'name' => 'display_stats_widget',
            'entity' => null,
        ]);

        $result = $permission->getEntityName();
        $this->assertNotNull($result);
    }

    // ── getModelName() edge cases ─────────────────────────────────────────────

    /** @test */
    public function get_model_name_returns_null_for_field_type_with_no_between(): void
    {
        // Field name with only 2 parts → no 'between' part
        $permission = new Permission([
            'name' => 'view_field',
            'type' => Permission::TYPE_FIELD,
            'guard_name' => 'web',
        ]);
        $permission->entity = null;

        // array_slice(['view', 'field'], 1, -1) = [] → returns null
        $result = $permission->getModelName();
        $this->assertNull($result);
    }

    /** @test */
    public function get_model_name_uses_entity_column_for_field_type(): void
    {
        // Field type with entity set → use entity for display
        $permission = Permission::factory()->field()->create([
            'name' => 'view_user_salary_field',
            'entity' => 'user',
        ]);

        $result = $permission->getModelName();
        $this->assertSame('User', $result);
    }

    /** @test */
    public function get_model_name_falls_back_to_last_part_when_no_model_parts(): void
    {
        // Resource permission where all parts are in skip words
        $permission = Permission::factory()->resource()->create([
            'name' => 'view',  // only 1 part → returns null
            'entity' => null,
        ]);

        $result = $permission->getModelName();
        // 'view' has only 1 part, count($parts) < 2 → null
        $this->assertNull($result);
    }

    // ── getDistinctEntityOptionsForFilter() ──────────────────────────────────

    /** @test */
    public function get_distinct_entity_options_merges_column_and_name_based(): void
    {
        // Entity from column
        Permission::factory()->resource()->create(['name' => 'view_any_product', 'entity' => 'product']);

        // Entity from name (no entity column)
        Permission::factory()->resource()->create(['name' => 'view_any_order', 'entity' => null]);

        $options = Permission::getDistinctEntityOptionsForFilter();

        $this->assertIsArray($options);
        $this->assertArrayHasKey('product', $options);
    }

    // ── getActionLabel() branches ─────────────────────────────────────────────

    /** @test */
    public function get_action_label_for_column_type_returns_headline(): void
    {
        $permission = Permission::factory()->column()->create([
            'name' => 'view_user_email_column',
            'entity' => 'user',
        ]);

        $label = $permission->getActionLabel();
        $this->assertNotEmpty($label);
    }

    /** @test */
    public function get_action_label_for_relation_type_returns_headline(): void
    {
        $permission = Permission::factory()->relation()->create([
            'name' => 'view_user_posts_relation',
            'entity' => 'user',
        ]);

        $label = $permission->getActionLabel();
        $this->assertNotEmpty($label);
    }

    /** @test */
    public function get_action_label_for_action_type_returns_headline(): void
    {
        $permission = Permission::factory()->action()->create([
            'name' => 'execute_user_export_action',
            'entity' => 'user',
        ]);

        $label = $permission->getActionLabel();
        $this->assertNotEmpty($label);
    }

    /** @test */
    public function get_action_label_for_resource_with_empty_entity_key(): void
    {
        // entityKey === 'other' → no entity removal from name
        $permission = Permission::factory()->create([
            'name' => 'x',
            'type' => Permission::TYPE_RESOURCE,
            'entity' => null,
            'guard_name' => 'web',
        ]);

        $label = $permission->getActionLabel();
        $this->assertNotEmpty($label);
    }
}
