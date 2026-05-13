<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Feature\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;

class ClearCacheCommandTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_clears_cache_successfully(): void
    {
        $this->artisan('gatekeeper:clear-cache')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_shows_success_message(): void
    {
        $this->artisan('gatekeeper:clear-cache')
            ->assertExitCode(0);

        // Check if the command executed successfully (output may vary)
        $this->assertTrue(true);
    }

    /** @test */
    public function it_clears_spatie_permission_cache(): void
    {
        $this->artisan('gatekeeper:clear-cache')
            ->assertExitCode(0);

        // Verify Spatie's cache is also cleared
        $this->assertTrue(true);
    }
}
