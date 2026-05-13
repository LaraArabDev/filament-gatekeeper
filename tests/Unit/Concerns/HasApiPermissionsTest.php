<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Unit\Concerns;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LaraArabDev\FilamentGatekeeper\Concerns\HasApiPermissions;
use LaraArabDev\FilamentGatekeeper\Models\Permission;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;

class HasApiPermissionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_authorize_index(): void
    {
        $user = $this->createUser();

        Permission::factory()->resource()->create([
            'name' => 'view_any_user',
        ]);

        $user->givePermissionTo('view_any_user');

        $this->actingAs($user);

        $controller = new TestApiController();

        // Should not throw exception
        $controller->authorizeIndex();
        $this->assertTrue(true);
    }

    /** @test */
    public function it_throws_exception_when_index_not_authorized(): void
    {
        $user = $this->createUser();

        $this->actingAs($user);

        $controller = new TestApiController();

        // Gatekeeper throws HttpException, not AuthorizationException
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $controller->authorizeIndex();
    }

    /** @test */
    public function it_can_authorize_show(): void
    {
        $user = $this->createUser();

        Permission::factory()->resource()->create([
            'name' => 'view_user',
        ]);

        $user->givePermissionTo('view_user');

        $this->actingAs($user);

        $controller = new TestApiController();

        $controller->authorizeShow();
        $this->assertTrue(true);
    }

    /** @test */
    public function it_can_authorize_store(): void
    {
        $user = $this->createUser();

        Permission::factory()->resource()->create([
            'name' => 'create_user',
        ]);

        $user->givePermissionTo('create_user');

        $this->actingAs($user);

        $controller = new TestApiController();

        $controller->authorizeStore();
        $this->assertTrue(true);
    }

    /** @test */
    public function it_can_authorize_update(): void
    {
        $user = $this->createUser();

        Permission::factory()->resource()->create([
            'name' => 'update_user',
        ]);

        $user->givePermissionTo('update_user');

        $this->actingAs($user);

        $controller = new TestApiController();

        $controller->authorizeUpdate();
        $this->assertTrue(true);
    }

    /** @test */
    public function it_can_authorize_destroy(): void
    {
        $user = $this->createUser();

        Permission::factory()->resource()->create([
            'name' => 'delete_user',
        ]);

        $user->givePermissionTo('delete_user');

        $this->actingAs($user);

        $controller = new TestApiController();

        $controller->authorizeDestroy();
        $this->assertTrue(true);
    }

    /** @test */
    public function it_can_authorize_restore(): void
    {
        $user = $this->createUser();

        Permission::factory()->resource()->create([
            'name' => 'restore_user',
        ]);

        $user->givePermissionTo('restore_user');

        $this->actingAs($user);

        $controller = new TestApiController();

        $controller->authorizeRestore();
        $this->assertTrue(true);
    }

    /** @test */
    public function it_can_authorize_force_delete(): void
    {
        $user = $this->createUser();

        Permission::factory()->resource()->create([
            'name' => 'force_delete_user',
        ]);

        $user->givePermissionTo('force_delete_user');

        $this->actingAs($user);

        $controller = new TestApiController();

        $controller->authorizeForceDelete();
        $this->assertTrue(true);
    }

    /** @test */
    public function it_bypasses_for_super_admin(): void
    {
        $user = $this->createSuperAdmin();

        $this->actingAs($user);

        $controller = new TestApiController();

        // All should pass without specific permissions
        $controller->authorizeIndex();
        $controller->authorizeShow();
        $controller->authorizeStore();
        $controller->authorizeUpdate();
        $controller->authorizeDestroy();
        $controller->authorizeRestore();
        $controller->authorizeForceDelete();

        $this->assertTrue(true);
    }

    /** @test */
    public function it_can_authorize_custom_permission(): void
    {
        $user = $this->createUser();

        Permission::factory()->action()->create([
            'name' => 'export_user',
        ]);

        $user->givePermissionTo('export_user');

        $this->actingAs($user);

        $controller = new TestApiController();

        $controller->authorizePermission('export_user');
        $this->assertTrue(true);
    }

    /** @test */
    public function it_can_check_permission_without_throwing(): void
    {
        $user = $this->createUser();

        Permission::factory()->resource()->create([
            'name' => 'view_any_user',
        ]);

        $user->givePermissionTo('view_any_user');

        $this->actingAs($user);

        $controller = new TestApiController();

        $this->assertTrue($controller->canIndex());
        $this->assertFalse($controller->canStore());
    }
}

class TestApiController
{
    use HasApiPermissions;

    protected string $permissionModel = 'user';
    protected string $shieldGuard = 'web';
}
