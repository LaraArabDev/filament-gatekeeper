<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Feature\Commands;

use LaraArabDev\FilamentGatekeeper\Models\Permission;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;

/**
 * Tests for the DeletePermissionsCommand.
 *
 * @group feature
 * @group commands
 */
class DeletePermissionsCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestPermissions();
    }

    /**
     * Create test permissions for various types.
     */
    protected function createTestPermissions(): void
    {
        // Field permissions
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
        Permission::factory()->resource()->create([
            'name' => 'create_user',
            'guard_name' => 'web',
        ]);
    }

    /** @test */
    public function it_deletes_field_permissions_for_model(): void
    {
        $initialCount = Permission::where('type', Permission::TYPE_FIELD)->count();
        expect($initialCount)->toBeGreaterThan(0);

        $this->artisan('gatekeeper:delete', [
            '--type' => 'field',
            '--model' => 'User',
            '--force' => true,
        ])->assertSuccessful();

        $remainingCount = Permission::where('type', Permission::TYPE_FIELD)
            ->where('name', 'like', '%_user_%')
            ->count();

        expect($remainingCount)->toBe(0);
    }

    /** @test */
    public function it_deletes_specific_field_permissions(): void
    {
        $this->artisan('gatekeeper:delete', [
            '--type' => 'field',
            '--model' => 'User',
            '--fields' => ['email'],
            '--force' => true,
        ])->assertSuccessful();

        // Email field permissions should be deleted
        expect(Permission::where('name', 'view_user_email_field')->exists())->toBeFalse();
        expect(Permission::where('name', 'update_user_email_field')->exists())->toBeFalse();

        // Salary field permission should still exist
        expect(Permission::where('name', 'view_user_salary_field')->exists())->toBeTrue();
    }

    /** @test */
    public function it_deletes_column_permissions_for_model(): void
    {
        $initialCount = Permission::where('type', Permission::TYPE_COLUMN)->count();
        expect($initialCount)->toBeGreaterThan(0);

        $this->artisan('gatekeeper:delete', [
            '--type' => 'column',
            '--model' => 'User',
            '--force' => true,
        ])->assertSuccessful();

        $remainingCount = Permission::where('type', Permission::TYPE_COLUMN)
            ->where('name', 'like', '%_user_%')
            ->count();

        expect($remainingCount)->toBe(0);
    }

    /** @test */
    public function it_deletes_specific_column_permissions(): void
    {
        $this->artisan('gatekeeper:delete', [
            '--type' => 'column',
            '--model' => 'User',
            '--columns' => ['email'],
            '--force' => true,
        ])->assertSuccessful();

        // Email column permission should be deleted
        expect(Permission::where('name', 'view_user_email_column')->exists())->toBeFalse();

        // Salary column permission should still exist
        expect(Permission::where('name', 'view_user_salary_column')->exists())->toBeTrue();
    }

    /** @test */
    public function it_deletes_all_model_permissions(): void
    {
        $initialCount = Permission::where('name', 'like', '%_user%')->count();
        expect($initialCount)->toBeGreaterThan(0);

        $this->artisan('gatekeeper:delete', [
            '--type' => 'model',
            '--model' => 'User',
            '--force' => true,
        ])->assertSuccessful();

        $remainingCount = Permission::where('name', 'like', '%_user%')->count();
        expect($remainingCount)->toBe(0);
    }

    /** @test */
    public function it_supports_dry_run_mode(): void
    {
        $initialCount = Permission::where('type', Permission::TYPE_FIELD)->count();

        $this->artisan('gatekeeper:delete', [
            '--type' => 'field',
            '--model' => 'User',
            '--dry-run' => true,
            '--force' => true,
        ])->assertSuccessful()
            ->expectsOutput('🔍 Running in dry-run mode (no changes will be made)');

        // Permissions should still exist
        $afterCount = Permission::where('type', Permission::TYPE_FIELD)->count();
        expect($afterCount)->toBe($initialCount);
    }

    /** @test */
    public function it_deletes_permissions_for_specific_guard(): void
    {
        // Create API guard permissions
        Permission::factory()->field()->create([
            'name' => 'view_user_email_field',
            'guard_name' => 'api',
        ]);

        $this->artisan('gatekeeper:delete', [
            '--type' => 'field',
            '--model' => 'User',
            '--guard' => 'web',
            '--force' => true,
        ])->assertSuccessful();

        // Web guard permission should be deleted
        expect(Permission::where('name', 'view_user_email_field')
            ->where('guard_name', 'web')
            ->exists())->toBeFalse();

        // API guard permission should still exist
        expect(Permission::where('name', 'view_user_email_field')
            ->where('guard_name', 'api')
            ->exists())->toBeTrue();
    }

    /** @test */
    public function it_handles_invalid_type_gracefully(): void
    {
        $this->artisan('gatekeeper:delete', [
            '--type' => 'invalid',
            '--model' => 'User',
            '--force' => true,
        ])->assertFailed();
    }
}
