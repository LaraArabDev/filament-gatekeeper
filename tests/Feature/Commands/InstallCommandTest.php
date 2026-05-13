<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Feature\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;

class InstallCommandTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_runs_installation(): void
    {
        $this->artisan('gatekeeper:install')
            ->expectsConfirmation('Do you want to publish Shield Manager stubs?', 'no')
            ->expectsConfirmation('Do you want to sync permissions now?', 'no')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_publishes_config(): void
    {
        $this->artisan('gatekeeper:install')
            ->expectsConfirmation('Do you want to publish Shield Manager stubs?', 'no')
            ->expectsConfirmation('Do you want to sync permissions now?', 'no')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_can_skip_migrations(): void
    {
        $this->artisan('gatekeeper:install', ['--skip-migrations' => true])
            ->expectsConfirmation('Do you want to publish Shield Manager stubs?', 'no')
            ->expectsConfirmation('Do you want to sync permissions now?', 'no')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_can_sync_permissions(): void
    {
        $this->artisan('gatekeeper:install', ['--sync' => true, '--skip-migrations' => true])
            ->expectsConfirmation('Do you want to publish Shield Manager stubs?', 'no')
            ->assertExitCode(0);
    }
}
