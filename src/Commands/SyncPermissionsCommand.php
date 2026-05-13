<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use LaraArabDev\FilamentGatekeeper\Services\PermissionCache;
use LaraArabDev\FilamentGatekeeper\Services\PermissionRegistrar;

class SyncPermissionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gatekeeper:sync
                            {--only= : Sync only specific type (resources, pages, widgets, fields, columns, actions, relations)}
                            {--panel= : Sync for a specific Filament panel}
                            {--guard= : Sync for a specific guard}
                            {--dry-run : Show what would be synced without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync all Shield Manager permissions for resources, pages, widgets, fields, columns, actions, and relations.';

    public function __construct(
        protected PermissionRegistrar $registrar,
        protected PermissionCache $cache
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🛡️  Shield Manager - Permission Sync');
        $this->newLine();

        // Reset permission cache first
        $this->resetPermissionCache();

        $dryRun = $this->option('dry-run');
        $only = $this->option('only');

        if ($dryRun) {
            $this->warn('🔍 Running in dry-run mode. No changes will be made.');
            $this->newLine();
        }

        $this->registrar->dryRun($dryRun);

        $startTime = microtime(true);

        if ($only) {
            $this->info("Syncing only: {$only}");
            $log = $this->registrar->syncOnly($only);
        } else {
            $this->info('Syncing all permissions...');
            $log = $this->registrar->syncAll();
        }

        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2);

        $this->displayResults($log);

        $this->newLine();
        $this->info("✅ Sync completed in {$duration}ms");

        // Clear Shield Manager cache
        if (! $dryRun) {
            $this->cache->invalidateAll();
            $this->info('🗑️  Permission cache cleared.');
        }

        return self::SUCCESS;
    }

    /**
     * Reset Spatie permission cache.
     */
    protected function resetPermissionCache(): void
    {
        try {
            Artisan::call('permission:cache-reset');
            $this->info('🔄 Spatie permission cache reset.');
        } catch (\Exception $e) {
            $this->warn('⚠️  Could not reset Spatie permission cache: '.$e->getMessage());
        }
    }

    /**
     * Display sync results.
     *
     * @param  array<string, array<string>>  $log
     */
    protected function displayResults(array $log): void
    {
        $this->newLine();

        $totalCount = 0;
        $tableData = [];

        foreach ($log as $type => $messages) {
            $count = count($messages);
            $totalCount += $count;
            $tableData[] = [ucfirst($type), $count];
        }

        $this->table(
            ['Permission Type', 'Count'],
            $tableData
        );

        $this->newLine();
        $this->info("📊 Total permissions synced: {$totalCount}");

        // Show detailed output if verbose
        if ($this->output->isVerbose()) {
            $this->newLine();
            $this->info('Detailed log:');

            foreach ($log as $type => $messages) {
                $this->newLine();
                $this->comment("[$type]");
                foreach ($messages as $message) {
                    $this->line("  - {$message}");
                }
            }
        }
    }
}

