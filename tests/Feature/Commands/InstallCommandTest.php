<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Feature\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LaraArabDev\FilamentGatekeeper\Commands\InstallCommand;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;

class InstallCommandTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_outputs_installation_header(): void
    {
        $this->artisan(InstallCommand::class, ['--skip-migrations' => true, '--no-interaction' => true])
            ->expectsOutputToContain('Filament Gatekeeper - Installation')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_outputs_success_message_on_completion(): void
    {
        $this->artisan(InstallCommand::class, ['--skip-migrations' => true, '--no-interaction' => true])
            ->expectsOutputToContain('Filament Gatekeeper installed successfully')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_skips_migrations_when_flag_is_set(): void
    {
        $this->artisan(InstallCommand::class, ['--skip-migrations' => true, '--no-interaction' => true])
            ->expectsOutputToContain('Skipping migrations')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_does_not_prompt_for_stubs_in_non_interactive_mode(): void
    {
        // Should complete without hanging waiting for input
        $this->artisan(InstallCommand::class, ['--skip-migrations' => true, '--no-interaction' => true])
            ->assertExitCode(0);
    }

    /** @test */
    public function it_prompts_for_gatekeeper_stubs_in_interactive_mode(): void
    {
        $this->artisan(InstallCommand::class, ['--skip-migrations' => true])
            ->expectsConfirmation('Do you want to publish Gatekeeper stubs?', 'no')
            ->expectsConfirmation('Do you want to sync permissions now?', 'no')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_outputs_config_publish_message(): void
    {
        $this->artisan(InstallCommand::class, ['--skip-migrations' => true, '--no-interaction' => true])
            ->expectsOutputToContain('Publishing config file')
            ->assertExitCode(0);
    }
}
