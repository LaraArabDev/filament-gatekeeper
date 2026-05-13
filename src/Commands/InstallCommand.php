<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gatekeeper:install
                            {--fresh : Run fresh migrations}
                            {--sync : Sync permissions after install}
                            {--skip-migrations : Skip running migrations}
                            {--create-super-admin : Create a super admin user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install Shield Manager package.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🛡️  Shield Manager - Installation');
        $this->newLine();

        // Step 1: Publish config
        $this->publishConfig();

        // Step 2: Publish migrations
        $this->publishMigrations();

        // Step 3: Run migrations
        $this->runMigrations();

        // Step 4: Publish stubs (optional)
        if (! $this->option('no-interaction') && $this->confirm('Do you want to publish Shield Manager stubs?', false)) {
            $this->publishStubs();
        }

        // Step 5: Sync permissions
        if ($this->option('sync') || (! $this->option('no-interaction') && $this->confirm('Do you want to sync permissions now?', true))) {
            $this->syncPermissions();
        }

        $this->newLine();
        $this->info('✅ Shield Manager installed successfully!');
        $this->newLine();

        $this->displayNextSteps();

        return self::SUCCESS;
    }

    /**
     * Publish the config file.
     */
    protected function publishConfig(): void
    {
        $this->info('📄 Publishing config file...');

        Artisan::call('vendor:publish', [
            '--tag' => 'gatekeeper-config',
            '--force' => true,
        ]);

        $this->info('   Config published to: config/gatekeeper.php');
    }

    /**
     * Publish migrations.
     */
    protected function publishMigrations(): void
    {
        $this->info('📄 Publishing migrations...');

        Artisan::call('vendor:publish', [
            '--tag' => 'gatekeeper-migrations',
            '--force' => true,
        ]);

        $this->info('   Migrations published.');
    }

    /**
     * Run migrations.
     */
    protected function runMigrations(): void
    {
        if ($this->option('skip-migrations')) {
            $this->info('⏭️  Skipping migrations...');
            return;
        }

        $this->info('🗃️  Running migrations...');

        $command = $this->option('fresh') ? 'migrate:fresh' : 'migrate';

        Artisan::call($command, [
            '--force' => true,
        ]);

        $this->info('   Migrations completed.');
    }

    /**
     * Publish stubs.
     */
    protected function publishStubs(): void
    {
        $this->info('📝 Publishing stubs...');

        Artisan::call('vendor:publish', [
            '--tag' => 'gatekeeper-stubs',
            '--force' => true,
        ]);

        $this->info('   Stubs published to: stubs/gatekeeper/');
    }

    /**
     * Sync permissions.
     */
    protected function syncPermissions(): void
    {
        $this->info('🔄 Syncing permissions...');

        Artisan::call('gatekeeper:sync');

        $this->info('   Permissions synced.');
    }

    /**
     * Display next steps.
     */
    protected function displayNextSteps(): void
    {
        $this->comment('📋 Next Steps:');
        $this->newLine();

        $this->line('1. Register the plugin in your Filament panel provider:');
        $this->newLine();
        $this->line('   <fg=cyan>use LaraArabDev\FilamentGatekeeper\GatekeeperPlugin;</>');
        $this->newLine();
        $this->line('   <fg=cyan>->plugins([</>');
        $this->line('   <fg=cyan>    GatekeeperPlugin::make()</>');
        $this->line('   <fg=cyan>        ->superAdminRole(\'super-admin\')</>');
        $this->line('   <fg=cyan>        ->bypassForSuperAdmin(true),</>');
        $this->line('   <fg=cyan>])</>');
        $this->newLine();

        $this->line('2. Use Shield traits in your resources:');
        $this->newLine();
        $this->line('   <fg=cyan>use LaraArabDev\FilamentGatekeeper\Base\GatekeeperResource;</>');
        $this->newLine();
        $this->line('   <fg=cyan>class UserResource extends GatekeeperResource { ... }</>');
        $this->newLine();

        $this->line('3. Or use make commands to generate shielded files:');
        $this->newLine();
        $this->line('   <fg=cyan>php artisan shield:make-resource UserResource --model=User</>');
        $this->line('   <fg=cyan>php artisan shield:make-model Product</>');
        $this->newLine();

        $this->info('📚 Documentation: https://github.com/laraarabdev/filament-gatekeeper');
    }
}
