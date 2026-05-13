<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Database\Seeders;

use Illuminate\Database\Seeder;
use LaraArabDev\FilamentGatekeeper\Services\PermissionRegistrar;

/**
 * Seeds roles and permissions with entity set.
 * Run after migrations. For full sync use: php artisan gatekeeper:sync
 */
class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->dryRun(false);

        // Sync all permissions (sets type + entity)
        $registrar->syncAll();
    }
}
