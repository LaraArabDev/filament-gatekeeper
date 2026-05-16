<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use LaraArabDev\FilamentGatekeeper\Services\PermissionCache;

class ClearCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gatekeeper:clear-cache
                            {--user= : Clear cache for a specific user ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear Gatekeeper permission cache.';

    public function __construct(
        protected PermissionCache $cache
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $userId = $this->option('user');

        $this->info('🛡️  Filament Gatekeeper - Cache Clear');
        $this->newLine();

        if ($userId) {
            $this->clearUserCache((int) $userId);
        } else {
            $this->clearAllCache();
        }

        return self::SUCCESS;
    }

    /**
     * Clear cache for a specific user.
     */
    protected function clearUserCache(int $userId): void
    {
        $userModel = config('auth.providers.users.model');

        if (! class_exists($userModel)) {
            $this->error("User model not found: {$userModel}");

            return;
        }

        $user = $userModel::find($userId);

        if (! $user) {
            $this->error("User not found with ID: {$userId}");

            return;
        }

        $this->cache->invalidateUser($user);
        $this->info("✅ Cache cleared for user ID: {$userId}");
    }

    /**
     * Clear all Gatekeeper cache.
     */
    protected function clearAllCache(): void
    {
        // Clear Gatekeeper cache
        $this->cache->invalidateAll();
        $this->info('✅ Gatekeeper cache cleared.');

        // Also reset Spatie permission cache
        try {
            Artisan::call('permission:cache-reset');
            $this->info('✅ Spatie permission cache reset.');
        } catch (\Exception $e) {
            $this->warn('⚠️  Could not reset Spatie cache: '.$e->getMessage());
        }

        // Display cache stats
        $stats = $this->cache->getStats();
        $this->newLine();
        $this->table(
            ['Setting', 'Value'],
            [
                ['Cache Prefix', $stats['prefix']],
                ['TTL (seconds)', $stats['ttl']],
                ['Driver', $stats['driver']],
                ['Supports Tagging', $stats['supports_tagging'] ? 'Yes' : 'No'],
            ]
        );
    }
}
