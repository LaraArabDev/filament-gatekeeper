<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Feature\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LaraArabDev\FilamentGatekeeper\Commands\InstallCommand;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;

/**
 * Extended tests for InstallCommand targeting uncovered branches:
 * - displayNextSteps() output
 * - publishStubs() when confirmed
 * - syncPermissions() when --sync flag is used
 * - runMigrations() with --fresh flag
 */
class InstallCommandExtendedTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_outputs_next_steps_after_installation(): void
    {
        $this->artisan(InstallCommand::class, ['--skip-migrations' => true, '--no-interaction' => true])
            ->expectsOutputToContain('Next Steps')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_outputs_plugin_registration_instruction(): void
    {
        $this->artisan(InstallCommand::class, ['--skip-migrations' => true, '--no-interaction' => true])
            ->expectsOutputToContain('GatekeeperPlugin')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_outputs_resource_usage_instruction(): void
    {
        $this->artisan(InstallCommand::class, ['--skip-migrations' => true, '--no-interaction' => true])
            ->expectsOutputToContain('GatekeeperResource')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_syncs_permissions_when_sync_flag_is_set(): void
    {
        $this->artisan(InstallCommand::class, ['--skip-migrations' => true, '--sync' => true, '--no-interaction' => true])
            ->expectsOutputToContain('Syncing permissions')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_publishes_stubs_when_confirmed_in_interactive_mode(): void
    {
        $this->artisan(InstallCommand::class, ['--skip-migrations' => true])
            ->expectsConfirmation('Do you want to publish Gatekeeper stubs?', 'yes')
            ->expectsConfirmation('Do you want to sync permissions now?', 'no')
            ->expectsOutputToContain('Publishing stubs')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_syncs_when_confirmed_in_interactive_mode(): void
    {
        $this->artisan(InstallCommand::class, ['--skip-migrations' => true])
            ->expectsConfirmation('Do you want to publish Gatekeeper stubs?', 'no')
            ->expectsConfirmation('Do you want to sync permissions now?', 'yes')
            ->expectsOutputToContain('Syncing permissions')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_publishes_migrations(): void
    {
        $this->artisan(InstallCommand::class, ['--skip-migrations' => true, '--no-interaction' => true])
            ->expectsOutputToContain('Publishing migrations')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_outputs_documentation_link(): void
    {
        $this->artisan(InstallCommand::class, ['--skip-migrations' => true, '--no-interaction' => true])
            ->expectsOutputToContain('Documentation')
            ->assertExitCode(0);
    }
}
