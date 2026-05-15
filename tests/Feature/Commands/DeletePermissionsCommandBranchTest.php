<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Feature\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LaraArabDev\FilamentGatekeeper\Models\Permission;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;

/**
 * Branch coverage tests for DeletePermissionsCommand.
 * Targets:
 * - deleteOrphanedPermissions()
 * - cancelation branches
 * - model required error paths
 */
class DeletePermissionsCommandBranchTest extends TestCase
{
    use RefreshDatabase;

    // ── deleteOrphanedPermissions ─────────────────────────────────────────

    /** @test */
    public function it_deletes_orphaned_permissions_with_force_flag(): void
    {
        Permission::factory()->resource()->create(['name' => 'view_any_deleted_model']);

        $this->artisan('gatekeeper:delete', [
            '--type' => 'orphaned',
            '--force' => true,
        ])->assertSuccessful();
    }

    /** @test */
    public function it_cancels_orphaned_permissions_when_not_confirmed(): void
    {
        $this->artisan('gatekeeper:delete', ['--type' => 'orphaned'])
            ->expectsConfirmation(
                'Delete all orphaned permissions (permissions for entities that no longer exist)?',
                'no'
            )
            ->expectsOutputToContain('Operation cancelled')
            ->assertSuccessful();
    }

    /** @test */
    public function it_confirms_and_deletes_orphaned_permissions(): void
    {
        Permission::factory()->resource()->create(['name' => 'view_orphaned_entity']);

        $this->artisan('gatekeeper:delete', ['--type' => 'orphaned'])
            ->expectsConfirmation(
                'Delete all orphaned permissions (permissions for entities that no longer exist)?',
                'yes'
            )
            ->assertSuccessful();
    }

    // ── deleteFieldPermissions - cancellation ─────────────────────────────

    /** @test */
    public function it_cancels_field_deletion_when_not_confirmed(): void
    {
        Permission::factory()->field()->create(['name' => 'view_user_email_field']);

        $this->artisan('gatekeeper:delete', ['--type' => 'field', '--model' => 'User'])
            ->expectsConfirmation(
                "Delete all field permissions for model 'User'?",
                'no'
            )
            ->expectsOutputToContain('Operation cancelled')
            ->assertSuccessful();
    }

    /** @test */
    public function it_returns_failure_when_model_not_provided_for_field_deletion(): void
    {
        $this->artisan('gatekeeper:delete', ['--type' => 'field', '--force' => true])
            ->expectsQuestion("Which model's field permissions do you want to delete?", '')
            ->expectsOutputToContain('Model name is required')
            ->assertFailed();
    }

    // ── deleteColumnPermissions - cancellation ────────────────────────────

    /** @test */
    public function it_cancels_column_deletion_when_not_confirmed(): void
    {
        Permission::factory()->column()->create(['name' => 'view_user_email_column']);

        $this->artisan('gatekeeper:delete', ['--type' => 'column', '--model' => 'User'])
            ->expectsConfirmation(
                "Delete all column permissions for model 'User'?",
                'no'
            )
            ->expectsOutputToContain('Operation cancelled')
            ->assertSuccessful();
    }

    /** @test */
    public function it_returns_failure_when_model_not_provided_for_column_deletion(): void
    {
        $this->artisan('gatekeeper:delete', ['--type' => 'column', '--force' => true])
            ->expectsQuestion("Which model's column permissions do you want to delete?", '')
            ->expectsOutputToContain('Model name is required')
            ->assertFailed();
    }

    // ── deleteModelPermissions - cancellation ─────────────────────────────

    /** @test */
    public function it_cancels_model_deletion_when_not_confirmed(): void
    {
        Permission::factory()->resource()->create(['name' => 'view_any_product']);

        $this->artisan('gatekeeper:delete', ['--type' => 'model', '--model' => 'Product'])
            ->expectsConfirmation(
                "Delete ALL permissions for model 'Product'? This includes resource, field, column, action, and relation permissions.",
                'no'
            )
            ->expectsOutputToContain('Operation cancelled')
            ->assertSuccessful();
    }

    /** @test */
    public function it_returns_failure_when_model_not_provided_for_model_deletion(): void
    {
        $this->artisan('gatekeeper:delete', ['--type' => 'model', '--force' => true])
            ->expectsQuestion("Which model's permissions do you want to delete?", '')
            ->expectsOutputToContain('Model name is required')
            ->assertFailed();
    }

    // ── interactive type selection ────────────────────────────────────────

    /** @test */
    public function it_prompts_for_type_when_not_specified(): void
    {
        Permission::factory()->resource()->create(['name' => 'view_any_order']);

        $this->artisan('gatekeeper:delete', ['--model' => 'Order', '--force' => true])
            ->expectsChoice(
                'What type of permissions do you want to delete?',
                'model',
                ['field', 'column', 'model', 'orphaned']
            )
            ->assertSuccessful();
    }

    // ── deleteFieldPermissions - with specific fields description ──────────

    /** @test */
    public function it_shows_specific_fields_in_confirmation_when_specified(): void
    {
        Permission::factory()->field()->create(['name' => 'view_product_price_field']);

        $this->artisan('gatekeeper:delete', [
            '--type' => 'field',
            '--model' => 'Product',
            '--fields' => ['price'],
        ])
            ->expectsConfirmation(
                "Delete field permissions for: price for model 'Product'?",
                'no'
            )
            ->expectsOutputToContain('Operation cancelled')
            ->assertSuccessful();
    }

    // ── deleteColumnPermissions - with specific columns description ────────

    /** @test */
    public function it_shows_specific_columns_in_confirmation_when_specified(): void
    {
        Permission::factory()->column()->create(['name' => 'view_product_price_column']);

        $this->artisan('gatekeeper:delete', [
            '--type' => 'column',
            '--model' => 'Product',
            '--columns' => ['price'],
        ])
            ->expectsConfirmation(
                "Delete column permissions for: price for model 'Product'?",
                'no'
            )
            ->expectsOutputToContain('Operation cancelled')
            ->assertSuccessful();
    }
}
