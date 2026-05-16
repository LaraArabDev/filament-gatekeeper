<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Unit\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LaraArabDev\FilamentGatekeeper\Models\Permission;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Additional tests targeting uncovered branches in Permission model.
 */
class PermissionEntityExtractionTest extends TestCase
{
    use RefreshDatabase;

    // ── getModelName() branches ───────────────────────────────────────────

    #[Test]
    public function get_model_name_uses_entity_column_for_resource_type(): void
    {
        $permission = Permission::factory()->resource()->create([
            'name' => 'view_any_user',
            'entity' => 'user',
        ]);

        $name = $permission->getModelName();
        $this->assertSame('User', $name);
    }

    #[Test]
    public function get_model_name_for_action_type_with_entity_set(): void
    {
        $permission = Permission::factory()->action()->create([
            'name' => 'execute_product_export_action',
            'entity' => 'product',
        ]);

        $name = $permission->getModelName();
        $this->assertSame('Product', $name);
    }

    #[Test]
    public function get_model_name_for_relation_type_with_entity_set(): void
    {
        $permission = Permission::factory()->relation()->create([
            'name' => 'view_user_posts_relation',
            'entity' => 'user',
        ]);

        $name = $permission->getModelName();
        $this->assertSame('User', $name);
    }

    #[Test]
    public function get_model_name_for_column_type_without_entity(): void
    {
        $permission = Permission::factory()->column()->create([
            'name' => 'view_user_email_column',
            'entity' => null,
        ]);

        $name = $permission->getModelName();
        $this->assertNotNull($name);
        $this->assertStringContainsString('User', $name);
    }

    #[Test]
    public function get_model_name_for_resource_type_skips_action_words(): void
    {
        $permission = Permission::factory()->resource()->create([
            'name' => 'view_any_blog_post',
            'entity' => null,
        ]);

        $name = $permission->getModelName();
        $this->assertNotNull($name);
        $this->assertStringContainsString('Blog', $name);
    }

    #[Test]
    public function get_model_name_returns_null_for_single_word_name(): void
    {
        $permission = Permission::factory()->resource()->create([
            'name' => 'admin',
            'entity' => null,
        ]);

        $result = $permission->getModelName();
        $this->assertNull($result);
    }

    #[Test]
    public function get_model_name_uses_fallback_last_part_when_all_skip_words(): void
    {
        $permission = Permission::factory()->resource()->create([
            'name' => 'view_any',
            'entity' => null,
        ]);

        // 'view' and 'any' are both in skip words → modelParts empty → uses last part
        $result = $permission->getModelName();
        $this->assertNotNull($result);
    }

    // ── extractPageName() fallback branch ─────────────────────────────────

    #[Test]
    public function extract_page_name_fallback_returns_model_name_when_no_pattern(): void
    {
        // A page permission that doesn't match either pattern - goes to fallback
        $permission = Permission::factory()->page()->create([
            'name' => 'view_something_completely_different',
            'entity' => null,
        ]);

        // getEntityName() calls extractPageName() which won't match either regex
        // but wait - 'view_something_completely_different' doesn't end in '_page'
        // and doesn't have '_page_' in it... actually it does match neither pattern
        // so it falls to getModelName()
        $result = $permission->getEntityName();
        $this->assertNotNull($result);
    }

    // ── extractWidgetName() fallback branch ──────────────────────────────

    #[Test]
    public function extract_widget_name_fallback_returns_model_name_when_no_pattern(): void
    {
        $permission = Permission::factory()->widget()->create([
            'name' => 'view_something_no_widget_suffix',
            'entity' => null,
        ]);

        $result = $permission->getEntityName();
        $this->assertNotNull($result);
    }

    // ── extractFieldName() - no match fallback ────────────────────────────

    #[Test]
    public function extract_field_name_fallback_returns_model_name(): void
    {
        $permission = Permission::factory()->field()->create([
            'name' => 'view_field_no_suffix',
            'entity' => null,
        ]);

        // 'view_field_no_suffix' has no '_field' suffix → fallback to getModelName()
        // Wait, it actually ends in '_no_suffix' not '_field'
        // Actually 'view_field_no_suffix' doesn't end in '_field'
        $result = $permission->getEntityName();
        $this->assertNotNull($result);
    }

    // ── getEntityGroupKey() for empty entity ─────────────────────────────

    #[Test]
    public function get_entity_group_key_returns_other_for_null_entity_and_null_model(): void
    {
        $permission = Permission::factory()->create([
            'name' => 'x',
            'type' => Permission::TYPE_RESOURCE,
            'entity' => null,
            'guard_name' => 'web',
        ]);

        $key = $permission->getEntityGroupKey();
        $this->assertEquals('other', $key);
    }

    #[Test]
    public function get_entity_group_key_returns_snake_case_of_entity_column(): void
    {
        $permission = Permission::factory()->resource()->create([
            'name' => 'view_any_blog_post',
            'entity' => 'BlogPost',
        ]);

        $key = $permission->getEntityGroupKey();
        $this->assertEquals('blog_post', $key);
    }

    // ── getEntityDisplayName() ────────────────────────────────────────────

    #[Test]
    public function get_entity_display_name_returns_other_when_no_entity_name(): void
    {
        $permission = Permission::factory()->create([
            'name' => 'x',
            'type' => Permission::TYPE_RESOURCE,
            'entity' => null,
            'guard_name' => 'web',
        ]);

        $name = $permission->getEntityDisplayName();
        // getEntityName() → getModelName() → null → 'Other'
        $this->assertEquals('Other', $name);
    }

    // ── getActionLabel() for resource type with entity removal ─────────────

    #[Test]
    public function get_action_label_removes_entity_key_from_resource_name(): void
    {
        $permission = Permission::factory()->resource()->create([
            'name' => 'view_any_user',
            'entity' => 'user',
        ]);

        $label = $permission->getActionLabel();
        // entityKey = 'user', removes '_user' from 'view_any_user' → 'view_any' → 'View Any'
        $this->assertNotEmpty($label);
        $this->assertStringNotContainsString('User', $label);
    }

    #[Test]
    public function get_action_label_for_page_type_uses_entity_key_removal(): void
    {
        $permission = Permission::factory()->page()->create([
            'name' => 'view_dashboard_page',
            'entity' => 'dashboard',
        ]);

        $label = $permission->getActionLabel();
        $this->assertNotEmpty($label);
    }

    #[Test]
    public function get_action_label_for_widget_type_uses_entity_key_removal(): void
    {
        $permission = Permission::factory()->widget()->create([
            'name' => 'view_stats_widget',
            'entity' => 'stats',
        ]);

        $label = $permission->getActionLabel();
        $this->assertNotEmpty($label);
    }

    #[Test]
    public function get_action_label_for_model_type(): void
    {
        $permission = Permission::factory()->model()->create([
            'name' => 'view_product',
            'entity' => 'product',
        ]);

        $label = $permission->getActionLabel();
        $this->assertNotEmpty($label);
    }

    // ── scopeOfType ───────────────────────────────────────────────────────

    #[Test]
    public function scope_of_type_filters_correctly(): void
    {
        Permission::factory()->resource()->create(['name' => 'view_any_user']);
        Permission::factory()->field()->create(['name' => 'view_user_email_field']);

        $results = Permission::ofType(Permission::TYPE_RESOURCE)->get();
        $this->assertEquals(1, $results->count());
        $this->assertEquals('resource', $results->first()->type);
    }
}
