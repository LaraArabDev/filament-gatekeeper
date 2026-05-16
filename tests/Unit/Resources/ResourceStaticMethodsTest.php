<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Unit\Resources;

use LaraArabDev\FilamentGatekeeper\Models\Permission;
use LaraArabDev\FilamentGatekeeper\Models\Role;
use LaraArabDev\FilamentGatekeeper\Resources\PermissionResource;
use LaraArabDev\FilamentGatekeeper\Resources\PermissionResource\Pages\ListPermissions;
use LaraArabDev\FilamentGatekeeper\Resources\RoleResource;
use LaraArabDev\FilamentGatekeeper\Resources\RoleResource\Pages\CreateRole;
use LaraArabDev\FilamentGatekeeper\Resources\RoleResource\Pages\EditRole;
use LaraArabDev\FilamentGatekeeper\Resources\RoleResource\Pages\ListRoles;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ResourceStaticMethodsTest extends TestCase
{
    // ── RoleResource ─────────────────────────────────────────────────────────

    #[Test]
    public function role_resource_uses_role_model(): void
    {
        $this->assertSame(Role::class, RoleResource::getModel());
    }

    #[Test]
    public function role_resource_has_navigation_label(): void
    {
        $this->assertIsString(RoleResource::getNavigationLabel());
        $this->assertNotEmpty(RoleResource::getNavigationLabel());
    }

    #[Test]
    public function role_resource_has_model_label(): void
    {
        $this->assertIsString(RoleResource::getModelLabel());
        $this->assertNotEmpty(RoleResource::getModelLabel());
    }

    #[Test]
    public function role_resource_has_plural_model_label(): void
    {
        $this->assertIsString(RoleResource::getPluralModelLabel());
        $this->assertNotEmpty(RoleResource::getPluralModelLabel());
    }

    #[Test]
    public function role_resource_returns_navigation_group_from_config(): void
    {
        config()->set('gatekeeper.navigation.group', 'Security');

        $this->assertSame('Security', RoleResource::getNavigationGroup());
    }

    #[Test]
    public function role_resource_returns_correct_pages(): void
    {
        $pages = RoleResource::getPages();

        $this->assertArrayHasKey('index', $pages);
        $this->assertArrayHasKey('create', $pages);
        $this->assertArrayHasKey('edit', $pages);
    }

    #[Test]
    public function role_resource_returns_empty_relations(): void
    {
        $this->assertSame([], RoleResource::getRelations());
    }

    // ── PermissionResource ────────────────────────────────────────────────────

    #[Test]
    public function permission_resource_uses_permission_model(): void
    {
        $this->assertSame(Permission::class, PermissionResource::getModel());
    }

    #[Test]
    public function permission_resource_has_navigation_label(): void
    {
        $this->assertIsString(PermissionResource::getNavigationLabel());
        $this->assertNotEmpty(PermissionResource::getNavigationLabel());
    }

    #[Test]
    public function permission_resource_has_model_label(): void
    {
        $this->assertIsString(PermissionResource::getModelLabel());
        $this->assertNotEmpty(PermissionResource::getModelLabel());
    }

    #[Test]
    public function permission_resource_has_plural_model_label(): void
    {
        $this->assertIsString(PermissionResource::getPluralModelLabel());
        $this->assertNotEmpty(PermissionResource::getPluralModelLabel());
    }

    #[Test]
    public function permission_resource_returns_navigation_group_from_config(): void
    {
        config()->set('gatekeeper.navigation.group', 'Access Control');

        $this->assertSame('Access Control', PermissionResource::getNavigationGroup());
    }

    #[Test]
    public function permission_resource_navigation_badge_color_is_primary(): void
    {
        $this->assertSame('primary', PermissionResource::getNavigationBadgeColor());
    }

    #[Test]
    public function permission_resource_cannot_create(): void
    {
        $this->assertFalse(PermissionResource::canCreate());
    }

    #[Test]
    public function permission_resource_returns_correct_pages(): void
    {
        $pages = PermissionResource::getPages();

        $this->assertArrayHasKey('index', $pages);
    }

    #[Test]
    public function permission_resource_returns_empty_relations(): void
    {
        $this->assertSame([], PermissionResource::getRelations());
    }

    // ── Page classes ──────────────────────────────────────────────────────────

    #[Test]
    public function create_role_page_uses_role_resource(): void
    {
        $this->assertSame(RoleResource::class, CreateRole::getResource());
    }

    #[Test]
    public function edit_role_page_uses_role_resource(): void
    {
        $this->assertSame(RoleResource::class, EditRole::getResource());
    }

    #[Test]
    public function list_roles_page_uses_role_resource(): void
    {
        $this->assertSame(RoleResource::class, ListRoles::getResource());
    }

    #[Test]
    public function list_permissions_page_uses_permission_resource(): void
    {
        $this->assertSame(PermissionResource::class, ListPermissions::getResource());
    }

    #[Test]
    public function permission_resource_returns_navigation_badge_count(): void
    {
        // No permissions → count = 0
        $badge = PermissionResource::getNavigationBadge();
        $this->assertSame('0', $badge);
    }
}
