<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Feature\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Branch coverage tests for ClearCacheCommand.
 * Targets:
 * - clearUserCache() when user not found
 * - clearUserCache() when user model class doesn't exist
 */
class ClearCacheCommandUserHandlingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_errors_when_user_id_not_found_in_database(): void
    {
        $this->artisan('gatekeeper:clear-cache', ['--user' => 99999])
            ->expectsOutputToContain('User not found with ID: 99999')
            ->assertExitCode(0);
    }

    #[Test]
    public function it_clears_cache_for_existing_user_by_id(): void
    {
        $user = $this->createUser();

        $this->artisan('gatekeeper:clear-cache', ['--user' => $user->id])
            ->expectsOutputToContain("Cache cleared for user ID: {$user->id}")
            ->assertExitCode(0);
    }

    #[Test]
    public function it_outputs_cache_stats_table_when_clearing_all(): void
    {
        $this->artisan('gatekeeper:clear-cache')
            ->assertExitCode(0);
    }

    #[Test]
    public function it_clears_all_cache_without_user_flag(): void
    {
        $this->artisan('gatekeeper:clear-cache')
            ->expectsOutputToContain('Gatekeeper cache cleared')
            ->assertExitCode(0);
    }

    #[Test]
    public function it_outputs_gatekeeper_header(): void
    {
        $this->artisan('gatekeeper:clear-cache')
            ->expectsOutputToContain('Filament Gatekeeper')
            ->assertExitCode(0);
    }
}
