<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Unit\Resources;

use LaraArabDev\FilamentGatekeeper\Models\Permission;
use LaraArabDev\FilamentGatekeeper\Models\Role;
use LaraArabDev\FilamentGatekeeper\Resources\PermissionResource;
use LaraArabDev\FilamentGatekeeper\Resources\RoleResource;
use LaraArabDev\FilamentGatekeeper\Resources\RoleResource\Pages\CreateRole;
use LaraArabDev\FilamentGatekeeper\Resources\RoleResource\Pages\EditRole;
use LaraArabDev\FilamentGatekeeper\Resources\RoleResource\Pages\ListRoles;
use LaraArabDev\FilamentGatekeeper\Resources\PermissionResource\Pages\ListPermissions;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;

class ResourceStaticMethodsTest extends TestCase
{
    // ── RoleResource ─────────────────────────────────────────────────────────

    /** @test */
    public function role_resource_uses_role_model(): void
    {
        $this->assertSame(Role::class, RoleResource::getModel());
    }

    /** @test */
    public function role_resource_has_navigation_label(): void
    {
        $this->assertIsString(RoleResource::getNavigationLabel());
        $this->assertNotEmpty(RoleResource::getNavigationLabel());
    }

    /** @test */
    public function role_resource_has_model_label(): void
    {
        $this->assertIsString(RoleResource::getModelLabel());
        $this->assertNotEmpty(RoleResource::getModelLabel());
    }

    /** @test */
    public function role_resource_has_plural_model_label(): void
    {
        $this->assertIsString(RoleResource::getPluralModelLabel());
        $this->assertNotEmpty(RoleResource::getPluralModelLabel());
    }

    /** @test */
    public function role_resource_returns_navigation_group_from_config(): void
    {
        config()->set('gatekeeper.navigation.group', 'Security');

        $this->assertSame('Security', RoleResource::getNavigationGroup());
    }

    /** @test */
    public function role_resource_returns_correct_pages(): void
    {
        $pages = RoleResource::getPages();

        $this->assertArrayHasKey('index', $pages);
        $this->assertArrayHasKey('create', $pages);
        $this->assertArrayHasKey('edit', $pages);
    }

    /** @test */
    public function role_resource_returns_empty_relations(): void
    {
        $this->assertSame([], RoleResource::getRelations());
    }

    // ── PermissionResource ────────────────────────────────────────────────────

    /** @test */
    public function permission_resource_uses_permission_model(): void
    {
        $this->assertSame(Permission::class, PermissionResource::getModel());
    }

    /** @test */
    public function permission_resource_has_navigation_label(): void
    {
        $this->assertIsString(PermissionResource::getNavigationLabel());
        $this->assertNotEmpty(PermissionResource::getNavigationLabel());
    }

    /** @test */
    public function permission_resource_has_model_label(): void
    {
        $this->assertIsString(PermissionResource::getModelLabel());
        $this->assertNotEmpty(PermissionResource::getModelLabel());
    }

    /** @test */
    public function permission_resource_has_plural_model_label(): void
    {
        $this->assertIsString(PermissionResource::getPluralModelLabel());
        $this->assertNotEmpty(PermissionResource::getPluralModelLabel());
    }

    /** @test */
    public function permission_resource_returns_navigation_group_from_config(): void
    {
        config()->set('gatekeeper.navigation.group', 'Access Control');

        $this->assertSame('Access Control', PermissionResource::getNavigationGroup());
    }

    /** @test */
    public function permission_resource_navigation_badge_color_is_primary(): void
    {
        $this->assertSame('primary', PermissionResource::getNavigationBadgeColor());
    }

    /** @test */
    public function permission_resource_cannot_create(): void
    {
        $this->assertFalse(PermissionResource::canCreate());
    }

    /** @test */
    public function permission_resource_returns_correct_pages(): void
    {
        $pages = PermissionResource::getPages();

        $this->assertArrayHasKey('index', $pages);
    }

    /** @test */
    public function permission_resource_returns_empty_relations(): void
    {
        $this->assertSame([], PermissionResource::getRelations());
    }

    // ── Page classes ──────────────────────────────────────────────────────────

    /** @test */
    public function create_role_page_uses_role_resource(): void
    {
        $this->assertSame(RoleResource::class, CreateRole::getResource());
    }

    /** @test */
    public function edit_role_page_uses_role_resource(): void
    {
        $this->assertSame(RoleResource::class, EditRole::getResource());
    }

    /** @test */
    public function list_roles_page_uses_role_resource(): void
    {
        $this->assertSame(RoleResource::class, ListRoles::getResource());
    }

    /** @test */
    public function list_permissions_page_uses_permission_resource(): void
    {
        $this->assertSame(PermissionResource::class, ListPermissions::getResource());
    }
}
