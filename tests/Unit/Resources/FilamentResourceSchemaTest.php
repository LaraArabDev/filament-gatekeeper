<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Unit\Resources;

use Filament\Forms\Components\Section;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LaraArabDev\FilamentGatekeeper\Models\Permission;
use LaraArabDev\FilamentGatekeeper\Resources\PermissionResource;
use LaraArabDev\FilamentGatekeeper\Resources\RoleResource;
use LaraArabDev\FilamentGatekeeper\Resources\RoleResource\Forms\RoleForm;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\PermissionRegistrar;

class FilamentResourceSchemaTest extends TestCase
{
    use RefreshDatabase;

    protected HasForms $mockForms;

    protected HasTable $mockTable;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('gatekeeper.guard', 'web');
        config()->set('gatekeeper.guards', ['web' => ['enabled' => true]]);

        /** @var HasForms $mockForms */
        $this->mockForms = Mockery::mock(HasForms::class)->shouldIgnoreMissing();

        /** @var HasTable $mockTable */
        $this->mockTable = Mockery::mock(HasTable::class)->shouldIgnoreMissing();
    }

    // ── RoleResource::form() ──────────────────────────────────────────────────

    #[Test]
    public function role_resource_form_returns_form_instance_with_empty_db(): void
    {
        $form = Form::make($this->mockForms);
        $result = RoleResource::form($form);

        $this->assertInstanceOf(Form::class, $result);
    }

    #[Test]
    public function role_resource_form_covers_grouped_sections_with_resource_permissions(): void
    {
        // Populate resource type permissions to cover makeSectionForEntity()
        Permission::factory()->resource()->create(['name' => 'view_any_post', 'entity' => 'post']);
        Permission::factory()->resource()->create(['name' => 'create_post', 'entity' => 'post']);
        Permission::factory()->resource()->create(['name' => 'update_post', 'entity' => 'post']);

        $form = Form::make($this->mockForms);
        $result = RoleResource::form($form);

        $this->assertInstanceOf(Form::class, $result);
    }

    #[Test]
    public function role_resource_form_covers_all_permission_types(): void
    {
        // Create permissions for each type to cover all branches in getGroupedPermissionsSection
        Permission::factory()->resource()->create(['name' => 'view_any_post', 'entity' => 'post']);
        Permission::factory()->create(['name' => 'view_any_user', 'type' => Permission::TYPE_MODEL, 'entity' => 'user', 'guard_name' => 'web']);
        Permission::factory()->create(['name' => 'access_dashboard', 'type' => Permission::TYPE_PAGE, 'entity' => 'dashboard', 'guard_name' => 'web']);
        Permission::factory()->create(['name' => 'view_stats_widget', 'type' => Permission::TYPE_WIDGET, 'entity' => 'stats', 'guard_name' => 'web']);
        Permission::factory()->create(['name' => 'view_user_email_field', 'type' => Permission::TYPE_FIELD, 'entity' => 'user', 'guard_name' => 'web']);
        Permission::factory()->create(['name' => 'view_user_name_column', 'type' => Permission::TYPE_COLUMN, 'entity' => 'user', 'guard_name' => 'web']);
        Permission::factory()->create(['name' => 'execute_post_export_action', 'type' => Permission::TYPE_ACTION, 'entity' => 'post', 'guard_name' => 'web']);
        Permission::factory()->create(['name' => 'view_user_posts_relation', 'type' => Permission::TYPE_RELATION, 'entity' => 'user', 'guard_name' => 'web']);

        $form = Form::make($this->mockForms);
        $result = RoleResource::form($form);

        $this->assertInstanceOf(Form::class, $result);
    }

    // ── RoleResource::table() ─────────────────────────────────────────────────

    #[Test]
    public function role_resource_table_returns_table_instance(): void
    {
        $table = Table::make($this->mockTable);
        $result = RoleResource::table($table);

        $this->assertInstanceOf(Table::class, $result);
    }

    // ── PermissionResource::form() ────────────────────────────────────────────

    #[Test]
    public function permission_resource_form_returns_form_instance(): void
    {
        $form = Form::make($this->mockForms);
        $result = PermissionResource::form($form);

        $this->assertInstanceOf(Form::class, $result);
    }

    // ── PermissionResource::table() ───────────────────────────────────────────

    #[Test]
    public function permission_resource_table_returns_table_instance(): void
    {
        $table = Table::make($this->mockTable);
        $result = PermissionResource::table($table);

        $this->assertInstanceOf(Table::class, $result);
    }

    // ── RoleResource protected methods via reflection ─────────────────────────

    #[Test]
    public function role_resource_get_permission_type_config_returns_all_eight_types(): void
    {
        $method = new \ReflectionMethod(RoleForm::class, 'getPermissionTypeConfig');
        $method->setAccessible(true);
        $config = $method->invoke(null);

        $this->assertIsArray($config);
        $this->assertArrayHasKey(Permission::TYPE_RESOURCE, $config);
        $this->assertArrayHasKey(Permission::TYPE_MODEL, $config);
        $this->assertArrayHasKey(Permission::TYPE_PAGE, $config);
        $this->assertArrayHasKey(Permission::TYPE_WIDGET, $config);
        $this->assertArrayHasKey(Permission::TYPE_FIELD, $config);
        $this->assertArrayHasKey(Permission::TYPE_COLUMN, $config);
        $this->assertArrayHasKey(Permission::TYPE_ACTION, $config);
        $this->assertArrayHasKey(Permission::TYPE_RELATION, $config);
        $this->assertCount(8, $config);
    }

    #[Test]
    public function role_resource_get_types_with_entity_in_title_returns_correct_types(): void
    {
        $method = new \ReflectionMethod(RoleForm::class, 'getTypesWithEntityInTitle');
        $method->setAccessible(true);
        $types = $method->invoke(null);

        $this->assertContains(Permission::TYPE_FIELD, $types);
        $this->assertContains(Permission::TYPE_COLUMN, $types);
        $this->assertContains(Permission::TYPE_RELATION, $types);
        $this->assertNotContains(Permission::TYPE_RESOURCE, $types);
    }

    #[Test]
    public function role_resource_get_grouped_permissions_section_returns_placeholder_when_empty(): void
    {
        $method = new \ReflectionMethod(RoleForm::class, 'getGroupedPermissionsSection');
        $method->setAccessible(true);
        $sections = $method->invoke(null, Permission::TYPE_RESOURCE);

        $this->assertIsArray($sections);
        $this->assertCount(1, $sections);
    }

    #[Test]
    public function role_resource_get_grouped_permissions_section_returns_sections_with_data(): void
    {
        Permission::factory()->resource()->create(['name' => 'view_any_post', 'entity' => 'post']);
        Permission::factory()->resource()->create(['name' => 'create_post', 'entity' => 'post']);
        Permission::factory()->resource()->create(['name' => 'view_any_user', 'entity' => 'user']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $method = new \ReflectionMethod(RoleForm::class, 'getGroupedPermissionsSection');
        $method->setAccessible(true);
        $sections = $method->invoke(null, Permission::TYPE_RESOURCE);

        $this->assertIsArray($sections);
        $this->assertGreaterThanOrEqual(1, count($sections));
    }

    #[Test]
    public function role_resource_make_empty_permissions_placeholder_returns_section(): void
    {
        $method = new \ReflectionMethod(RoleForm::class, 'makeEmptyPermissionsPlaceholder');
        $method->setAccessible(true);
        $section = $method->invoke(null);

        $this->assertInstanceOf(Section::class, $section);
    }

    #[Test]
    public function role_resource_format_permission_label(): void
    {
        $method = new \ReflectionMethod(RoleForm::class, 'formatPermissionLabel');
        $method->setAccessible(true);

        $label = $method->invoke(null, 'view_any_post', 'post');
        $this->assertSame('View Any Post', $label);

        $label = $method->invoke(null, 'create_user', 'user');
        $this->assertSame('Create User', $label);
    }

    #[Test]
    public function role_resource_get_model_path_returns_correct_path_for_each_type(): void
    {
        $method = new \ReflectionMethod(RoleForm::class, 'getModelPath');
        $method->setAccessible(true);

        $this->assertStringContainsString('PostResource', $method->invoke(null, 'post', Permission::TYPE_RESOURCE));
        $this->assertStringContainsString('Post', $method->invoke(null, 'post', Permission::TYPE_MODEL));
        $this->assertStringContainsString('Post', $method->invoke(null, 'post', Permission::TYPE_PAGE));
        $this->assertStringContainsString('Post', $method->invoke(null, 'post', Permission::TYPE_WIDGET));
        $this->assertStringContainsString('Fields for Post', $method->invoke(null, 'post', Permission::TYPE_FIELD));
        $this->assertStringContainsString('Columns for Post', $method->invoke(null, 'post', Permission::TYPE_COLUMN));
        $this->assertStringContainsString('Actions for Post', $method->invoke(null, 'post', Permission::TYPE_ACTION));
        $this->assertStringContainsString('Relations for Post', $method->invoke(null, 'post', Permission::TYPE_RELATION));
        $this->assertSame('', $method->invoke(null, 'post', 'unknown'));
    }

    #[Test]
    public function role_resource_get_entity_icon_returns_icon_for_each_type(): void
    {
        $method = new \ReflectionMethod(RoleForm::class, 'getEntityIcon');
        $method->setAccessible(true);

        $this->assertSame('heroicon-o-rectangle-stack', $method->invoke(null, Permission::TYPE_RESOURCE));
        $this->assertSame('heroicon-o-cube', $method->invoke(null, Permission::TYPE_MODEL));
        $this->assertSame('heroicon-o-document-text', $method->invoke(null, Permission::TYPE_PAGE));
        $this->assertSame('heroicon-o-chart-bar', $method->invoke(null, Permission::TYPE_WIDGET));
        $this->assertSame('heroicon-o-adjustments-horizontal', $method->invoke(null, Permission::TYPE_FIELD));
        $this->assertSame('heroicon-o-table-cells', $method->invoke(null, Permission::TYPE_COLUMN));
        $this->assertSame('heroicon-o-bolt', $method->invoke(null, Permission::TYPE_ACTION));
        $this->assertSame('heroicon-o-link', $method->invoke(null, Permission::TYPE_RELATION));
        $this->assertSame('heroicon-o-shield-check', $method->invoke(null, 'unknown_type'));
    }

    #[Test]
    public function role_resource_get_permission_descriptions_returns_array_for_all_actions(): void
    {
        $method = new \ReflectionMethod(RoleForm::class, 'getPermissionDescriptions');
        $method->setAccessible(true);

        $permissions = collect([
            $this->makePermissionWithAction('view_any_post', 'view_any'),
            $this->makePermissionWithAction('view_post', 'view'),
            $this->makePermissionWithAction('create_post', 'create'),
            $this->makePermissionWithAction('update_post', 'update'),
            $this->makePermissionWithAction('delete_post', 'delete'),
            $this->makePermissionWithAction('restore_post', 'restore'),
            $this->makePermissionWithAction('force_delete_post', 'force_delete'),
            $this->makePermissionWithAction('replicate_post', 'replicate'),
            $this->makePermissionWithAction('reorder_post', 'reorder'),
            $this->makePermissionWithAction('custom_post', 'custom'),
        ]);

        $descriptions = $method->invoke(null, $permissions);

        $this->assertIsArray($descriptions);
        $this->assertCount(10, $descriptions);
    }

    #[Test]
    public function role_resource_get_guard_options_returns_enabled_guards(): void
    {
        config()->set('gatekeeper.guards', [
            'web' => ['enabled' => true],
            'api' => ['enabled' => false],
        ]);

        $method = new \ReflectionMethod(RoleForm::class, 'getGuardOptions');
        $method->setAccessible(true);
        $options = $method->invoke(null);

        $this->assertArrayHasKey('web', $options);
        $this->assertArrayNotHasKey('api', $options);
    }

    #[Test]
    public function role_resource_make_section_for_entity_returns_section(): void
    {
        $p1 = Permission::factory()->resource()->create(['name' => 'view_any_post', 'entity' => 'post']);
        $p2 = Permission::factory()->resource()->create(['name' => 'create_post', 'entity' => 'post']);

        $method = new \ReflectionMethod(RoleForm::class, 'makeSectionForEntity');
        $method->setAccessible(true);

        $section = $method->invoke(
            null,
            'post',
            collect([$p1, $p2]),
            Permission::TYPE_RESOURCE,
            'Resources',
            false,
            4
        );

        $this->assertInstanceOf(Section::class, $section);
    }

    #[Test]
    public function role_resource_make_section_for_entity_with_entity_in_title(): void
    {
        $p1 = Permission::factory()->create(['name' => 'view_user_email_field', 'type' => Permission::TYPE_FIELD, 'entity' => 'user', 'guard_name' => 'web']);

        $method = new \ReflectionMethod(RoleForm::class, 'makeSectionForEntity');
        $method->setAccessible(true);

        $section = $method->invoke(
            null,
            'user',
            collect([$p1]),
            Permission::TYPE_FIELD,
            'Fields',
            true,  // showEntityInTitle = true
            2
        );

        $this->assertInstanceOf(Section::class, $section);
    }

    // ── PermissionResource protected methods via reflection ───────────────────

    #[Test]
    public function permission_resource_get_form_fields_returns_array(): void
    {
        $method = new \ReflectionMethod(PermissionResource::class, 'getFormFields');
        $method->setAccessible(true);
        $fields = $method->invoke(null);

        $this->assertIsArray($fields);
        $this->assertNotEmpty($fields);
    }

    #[Test]
    public function permission_resource_get_guard_options_returns_enabled_guards(): void
    {
        config()->set('gatekeeper.guards', [
            'web' => ['enabled' => true],
            'api' => ['enabled' => true],
        ]);

        $method = new \ReflectionMethod(PermissionResource::class, 'getGuardOptions');
        $method->setAccessible(true);
        $options = $method->invoke(null);

        $this->assertArrayHasKey('web', $options);
        $this->assertArrayHasKey('api', $options);
    }

    #[Test]
    public function permission_resource_get_guard_badge_color_returns_correct_colors(): void
    {
        $method = new \ReflectionMethod(PermissionResource::class, 'getGuardBadgeColor');
        $method->setAccessible(true);

        $this->assertSame('success', $method->invoke(null, 'web'));
        $this->assertSame('warning', $method->invoke(null, 'api'));
        $this->assertSame('gray', $method->invoke(null, 'sanctum'));
        $this->assertSame('gray', $method->invoke(null, 'custom_guard'));
    }

    #[Test]
    public function permission_resource_get_table_columns_returns_array(): void
    {
        $method = new \ReflectionMethod(PermissionResource::class, 'getTableColumns');
        $method->setAccessible(true);
        $columns = $method->invoke(null);

        $this->assertIsArray($columns);
        $this->assertNotEmpty($columns);
    }

    #[Test]
    public function permission_resource_get_table_filters_returns_array(): void
    {
        $method = new \ReflectionMethod(PermissionResource::class, 'getTableFilters');
        $method->setAccessible(true);
        $filters = $method->invoke(null);

        $this->assertIsArray($filters);
        $this->assertNotEmpty($filters);
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    /**
     * Create a Permission model stub with getAction() returning the expected action.
     */
    private function makePermissionWithAction(string $name, string $expectedAction): Permission
    {
        $permission = Permission::factory()->resource()->create(['name' => $name]);

        // Force entity to test getPermissionDescriptions
        return $permission;
    }
}
