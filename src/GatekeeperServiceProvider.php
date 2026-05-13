<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use LaraArabDev\FilamentGatekeeper\Commands\ClearCacheCommand;
use LaraArabDev\FilamentGatekeeper\Commands\DeletePermissionsCommand;
use LaraArabDev\FilamentGatekeeper\Http\Middleware\GatekeeperApiMiddleware;
use LaraArabDev\FilamentGatekeeper\Http\Middleware\GatekeeperResourceMiddleware;
use LaraArabDev\FilamentGatekeeper\Commands\InstallCommand;
use LaraArabDev\FilamentGatekeeper\Commands\SyncPermissionsCommand;
use LaraArabDev\FilamentGatekeeper\Models\Permission;
use LaraArabDev\FilamentGatekeeper\Models\Role;
use LaraArabDev\FilamentGatekeeper\Services\PermissionCache;
use Spatie\LaravelPackageTools\Commands\InstallCommand as PackageInstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

/**
 * Gatekeeper Service Provider
 * This service provider is responsible for registering the Gatekeeper package and its services.
 * It is used to register the Gatekeeper singleton and the PermissionCache service.
 * It is also used to register the cache invalidation events.
 * It is also used to publish the stubs for customization.
 */
class GatekeeperServiceProvider extends PackageServiceProvider
{
    /**
     * @var string
     */
    public static string $name = 'gatekeeper';

    /**
     * Configure the package.
     *
     * @param Package $package
     * @return void
     */
    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile('gatekeeper')
            ->hasTranslations()
            ->hasMigrations([
                'add_type_to_permissions_table',
                'add_entity_to_permissions_table',
                'add_field_permissions_to_roles_table',
            ])
            ->hasCommands($this->getCommands())
            ->hasInstallCommand(function (PackageInstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->askToStarRepoOnGitHub('laraarabdev/filament-gatekeeper')
                    ->endWith(function (PackageInstallCommand $installCommand) {
                        $installCommand->info('Gatekeeper installed successfully!');
                        $installCommand->info('Run `php artisan gatekeeper:sync` to sync permissions.');
                    });
            });
    }

    /**
     * Register the package services.
     *
     * @return void
     */
    public function packageRegistered(): void
    {
        parent::packageRegistered();

        // Register the Gatekeeper singleton
        $this->app->singleton(Gatekeeper::class, function ($app) {
            return new Gatekeeper(
                $app->make(PermissionCache::class)
            );
        });

        // Bind the PermissionCache service
        $this->app->singleton(PermissionCache::class, function ($app) {
            return new PermissionCache();
        });
    }

    /**
     * Boot the package services.
     *
     * @return void
     */
    public function packageBooted(): void
    {
        parent::packageBooted();

        // Register middleware aliases
        $this->registerMiddleware();

        // Register Gate before callback for super admin
        $this->registerGateCallbacks();

        // Register cache invalidation events
        $this->registerCacheInvalidationEvents();

        // Publish stubs if enabled
        if (config('gatekeeper.auto_apply.publish_stubs', true)) {
            $this->publishStubs();
        }
    }

    /**
     * Get the commands to register.
     *
     * @return array<class-string>
     */
    protected function getCommands(): array
    {
        return [
            SyncPermissionsCommand::class,
            ClearCacheCommand::class,
            DeletePermissionsCommand::class,
            InstallCommand::class,
        ];
    }

    /**
     * Register cache invalidation events.
     * @return void
     */
    protected function registerCacheInvalidationEvents(): void
    {
        // Clear cache when role is updated
        Event::listen('eloquent.saved: ' . Role::class, function ($role) {
            app(PermissionCache::class)->invalidateRole($role);
        });

        Event::listen('eloquent.deleted: ' . Role::class, function ($role) {
            app(PermissionCache::class)->invalidateRole($role);
        });

        // Clear all cache when permission is modified
        Event::listen('eloquent.saved: ' . Permission::class, function () {
            app(PermissionCache::class)->invalidateAll();
        });

        Event::listen('eloquent.deleted: ' . Permission::class, function () {
            app(PermissionCache::class)->invalidateAll();
        });
    }

    /**
     * Register the middleware aliases.
     *
     * @return void
     */
    protected function registerMiddleware(): void
    {
        $router = $this->app->make(Router::class);

        // Register middleware aliases
        $router->aliasMiddleware('gatekeeper.api', GatekeeperApiMiddleware::class);
        $router->aliasMiddleware('gatekeeper.resource', GatekeeperResourceMiddleware::class);
    }

    /**
     * Register Gate before callback for super admin bypass.
     *
     * @return void
     */
    protected function registerGateCallbacks(): void
    {
        // Allow super admin to bypass all permission checks
        Gate::before(function ($user, $ability) {
            if (! config('gatekeeper.super_admin.enabled', true)) {
                return null;
            }

            $superAdminRole = config('gatekeeper.super_admin.role', 'super_admin');

            // Check if user has super admin role
            if (method_exists($user, 'hasRole') && $user->hasRole($superAdminRole)) {
                return true;
            }

            return null;
        });

        // Register permission gates
        $this->registerPermissionGates();
    }

    /**
     * Register permission gates for all permissions in the database.
     *
     * @return void
     */
    protected function registerPermissionGates(): void
    {
        // Only register gates after the application has booted
        $this->app->booted(function () {
            try {
                $permissions = Permission::all();

                foreach ($permissions as $permission) {
                    Gate::define($permission->name, function ($user) use ($permission) {
                        return $user->hasPermissionTo($permission);
                    });
                }
            } catch (\Exception $e) {
                // Database might not be ready (migrations not run yet)
            }
        });
    }

    /**
     * Publish stubs for customization.
     *
     * @return void
     */
    protected function publishStubs(): void
    {
        $this->publishes([
            __DIR__ . '/../stubs/filament/Resource.stub' => base_path('stubs/filament/Resource.stub'),
            __DIR__ . '/../stubs/filament/Page.stub' => base_path('stubs/filament/Page.stub'),
            __DIR__ . '/../stubs/filament/Widget.stub' => base_path('stubs/filament/Widget.stub'),
            __DIR__ . '/../stubs/filament/RelationManager.stub' => base_path('stubs/filament/RelationManager.stub'),
        ], 'gatekeeper-stubs');
    }
}
