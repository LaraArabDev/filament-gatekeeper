<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Unit\Services;

use LaraArabDev\FilamentGatekeeper\Models\Permission;
use LaraArabDev\FilamentGatekeeper\Services\PermissionRegistrar;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;

/**
 * Tests for the PermissionRegistrar delete methods.
 */
class PermissionRegistrarDeleteTest extends TestCase
{
    protected PermissionRegistrar $registrar;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registrar = new PermissionRegistrar();
        $this->createTestPermissions();
    }

    /**
     * Create test permissions for various types.
     */
    protected function createTestPermissions(): void
    {
        // Field permissions for User
        Permission::factory()->field()->create([
            'name' => 'view_user_email_field',
            'guard_name' => 'web',
        ]);
        Permission::factory()->field()->create([
            'name' => 'update_user_email_field',
            'guard_name' => 'web',
        ]);
        Permission::factory()->field()->create([
            'name' => 'view_user_salary_field',
            'guard_name' => 'web',
        ]);
        Permission::factory()->field()->create([
            'name' => 'view_user_salary_field',
            'guard_name' => 'api',
        ]);

        // Field permissions for Post
        Permission::factory()->field()->create([
            'name' => 'view_post_title_field',
            'guard_name' => 'web',
        ]);

        // Column permissions
        Permission::factory()->column()->create([
            'name' => 'view_user_email_column',
            'guard_name' => 'web',
        ]);
        Permission::factory()->column()->create([
            'name' => 'view_user_salary_column',
            'guard_name' => 'web',
        ]);

        // Resource permissions
        Permission::factory()->resource()->create([
            'name' => 'view_any_user',
            'guard_name' => 'web',
        ]);
        Permission::factory()->resource()->create([
            'name' => 'view_user',
            'guard_name' => 'web',
        ]);
    }

    /** @test */
    public function it_deletes_all_field_permissions_for_model(): void
    {
        $count = $this->registrar->deleteFieldPermissions('User');

        expect($count)->toBeGreaterThan(0);
        expect(Permission::where('type', Permission::TYPE_FIELD)
            ->where('name', 'like', '%_user_%')
            ->where('guard_name', 'web')
            ->exists())->toBeFalse();

        // Post field permissions should still exist
        expect(Permission::where('name', 'view_post_title_field')->exists())->toBeTrue();
    }

    /** @test */
    public function it_deletes_specific_field_permissions(): void
    {
        $count = $this->registrar->deleteFieldPermissions('User', ['email']);

        expect($count)->toBeGreaterThan(0);

        // Email permissions should be deleted
        expect(Permission::where('name', 'view_user_email_field')->exists())->toBeFalse();
        expect(Permission::where('name', 'update_user_email_field')->exists())->toBeFalse();

        // Salary permissions should still exist
        expect(Permission::where('name', 'view_user_salary_field')->exists())->toBeTrue();
    }

    /** @test */
    public function it_deletes_field_permissions_for_specific_guard(): void
    {
        $count = $this->registrar->deleteFieldPermissions('User', null, 'web');

        expect($count)->toBeGreaterThan(0);

        // Web guard permissions should be deleted
        expect(Permission::where('type', Permission::TYPE_FIELD)
            ->where('name', 'like', '%_user_%')
            ->where('guard_name', 'web')
            ->exists())->toBeFalse();

        // API guard permissions should still exist
        expect(Permission::where('name', 'view_user_salary_field')
            ->where('guard_name', 'api')
            ->exists())->toBeTrue();
    }

    /** @test */
    public function it_deletes_all_column_permissions_for_model(): void
    {
        $count = $this->registrar->deleteColumnPermissions('User');

        expect($count)->toBeGreaterThan(0);
        expect(Permission::where('type', Permission::TYPE_COLUMN)
            ->where('name', 'like', '%_user_%')
            ->exists())->toBeFalse();
    }

    /** @test */
    public function it_deletes_specific_column_permissions(): void
    {
        $count = $this->registrar->deleteColumnPermissions('User', ['email']);

        expect($count)->toBeGreaterThan(0);

        // Email column permission should be deleted
        expect(Permission::where('name', 'view_user_email_column')->exists())->toBeFalse();

        // Salary column permission should still exist
        expect(Permission::where('name', 'view_user_salary_column')->exists())->toBeTrue();
    }

    /** @test */
    public function it_deletes_all_permissions_for_model(): void
    {
        $count = $this->registrar->deleteModelPermissions('User');

        expect($count)->toBeGreaterThan(0);

        // All User permissions should be deleted
        expect(Permission::where('name', 'like', '%_user%')->exists())->toBeFalse();

        // Post permissions should still exist
        expect(Permission::where('name', 'view_post_title_field')->exists())->toBeTrue();
    }

    /** @test */
    public function it_respects_dry_run_for_field_delete(): void
    {
        $initialCount = Permission::where('type', Permission::TYPE_FIELD)->count();

        $deletedCount = $this->registrar->dryRun()->deleteFieldPermissions('User');

        expect($deletedCount)->toBeGreaterThan(0);
        expect(Permission::where('type', Permission::TYPE_FIELD)->count())->toBe($initialCount);
    }

    /** @test */
    public function it_respects_dry_run_for_column_delete(): void
    {
        $initialCount = Permission::where('type', Permission::TYPE_COLUMN)->count();

        $deletedCount = $this->registrar->dryRun()->deleteColumnPermissions('User');

        expect($deletedCount)->toBeGreaterThan(0);
        expect(Permission::where('type', Permission::TYPE_COLUMN)->count())->toBe($initialCount);
    }

    /** @test */
    public function it_respects_dry_run_for_model_delete(): void
    {
        $initialCount = Permission::count();

        $deletedCount = $this->registrar->dryRun()->deleteModelPermissions('User');

        expect($deletedCount)->toBeGreaterThan(0);
        expect(Permission::count())->toBe($initialCount);
    }

    /** @test */
    public function it_logs_delete_operations(): void
    {
        $this->registrar->deleteFieldPermissions('User');

        $log = $this->registrar->getSyncLog();

        expect($log)->toHaveKey('delete_field')
            ->and($log['delete_field'])->not->toBeEmpty();
    }

    /** @test */
    public function it_returns_zero_when_no_permissions_match(): void
    {
        $count = $this->registrar->deleteFieldPermissions('NonExistentModel');

        expect($count)->toBe(0);
    }

    /** @test */
    public function it_provides_access_to_field_discovery(): void
    {
        $fieldDiscovery = $this->registrar->getFieldDiscovery();

        expect($fieldDiscovery)->toBeInstanceOf(\LaraArabDev\FilamentGatekeeper\Support\Discovery\FieldDiscovery::class);
    }

    /** @test */
    public function it_provides_access_to_column_discovery(): void
    {
        $columnDiscovery = $this->registrar->getColumnDiscovery();

        expect($columnDiscovery)->toBeInstanceOf(\LaraArabDev\FilamentGatekeeper\Support\Discovery\ColumnDiscovery::class);
    }
}
