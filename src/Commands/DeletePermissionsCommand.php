<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Commands;

use Illuminate\Console\Command;
use LaraArabDev\FilamentGatekeeper\Services\PermissionRegistrar;

/**
 * Command to delete permissions from the database.
 *
 * Provides various options to delete field, column, or model permissions
 * with support for dry-run mode.
 */
class DeletePermissionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gatekeeper:delete
                            {--type= : Permission type to delete (field, column, model, orphaned)}
                            {--model= : Model name to delete permissions for}
                            {--fields=* : Specific fields to delete}
                            {--columns=* : Specific columns to delete}
                            {--guard= : Specific guard to delete for}
                            {--dry-run : Preview what would be deleted without making changes}
                            {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete Gatekeeper permissions';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $type = $this->option('type');
        $model = $this->option('model');
        $fields = $this->option('fields');
        $columns = $this->option('columns');
        $guard = $this->option('guard');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        if (! $type) {
            $type = $this->choice(
                'What type of permissions do you want to delete?',
                ['field', 'column', 'model', 'orphaned'],
                0
            );
        }

        $registrar = app(PermissionRegistrar::class);

        if ($dryRun) {
            $registrar->dryRun();
            $this->info('🔍 Running in dry-run mode (no changes will be made)');
            $this->newLine();
        }

        $count = match ($type) {
            'field' => $this->deleteFieldPermissions($registrar, $model, $fields, $guard, $force),
            'column' => $this->deleteColumnPermissions($registrar, $model, $columns, $guard, $force),
            'model' => $this->deleteModelPermissions($registrar, $model, $guard, $force),
            'orphaned' => $this->deleteOrphanedPermissions($registrar, $force),
            default => $this->invalidType($type),
        };

        if ($count === -1) {
            return self::FAILURE;
        }

        $this->newLine();

        if ($dryRun) {
            $this->info("📋 Would delete {$count} permission(s)");
        } else {
            $this->info("✅ Deleted {$count} permission(s)");
        }

        return self::SUCCESS;
    }

    /**
     * Delete field permissions.
     *
     * @param  array<int, string>  $fields
     */
    protected function deleteFieldPermissions(
        PermissionRegistrar $registrar,
        ?string $model,
        array $fields,
        ?string $guard,
        bool $force
    ): int {
        if (! $model) {
            $model = $this->ask('Which model\'s field permissions do you want to delete?');
        }

        if (! $model) {
            $this->error('Model name is required');

            return -1;
        }

        $fieldsToDelete = $fields === [] ? null : $fields;

        $description = $fieldsToDelete
            ? 'field permissions for: '.implode(', ', $fieldsToDelete)
            : 'all field permissions';

        if (! $force && ! $this->confirm("Delete {$description} for model '{$model}'?")) {
            $this->info('Operation cancelled');

            return 0;
        }

        return $registrar->deleteFieldPermissions($model, $fieldsToDelete, $guard);
    }

    /**
     * Delete column permissions.
     *
     * @param  array<int, string>  $columns
     */
    protected function deleteColumnPermissions(
        PermissionRegistrar $registrar,
        ?string $model,
        array $columns,
        ?string $guard,
        bool $force
    ): int {
        if (! $model) {
            $model = $this->ask('Which model\'s column permissions do you want to delete?');
        }

        if (! $model) {
            $this->error('Model name is required');

            return -1;
        }

        $columnsToDelete = $columns === [] ? null : $columns;

        $description = $columnsToDelete
            ? 'column permissions for: '.implode(', ', $columnsToDelete)
            : 'all column permissions';

        if (! $force && ! $this->confirm("Delete {$description} for model '{$model}'?")) {
            $this->info('Operation cancelled');

            return 0;
        }

        return $registrar->deleteColumnPermissions($model, $columnsToDelete, $guard);
    }

    /**
     * Delete all permissions for a model.
     */
    protected function deleteModelPermissions(
        PermissionRegistrar $registrar,
        ?string $model,
        ?string $guard,
        bool $force
    ): int {
        if (! $model) {
            $model = $this->ask('Which model\'s permissions do you want to delete?');
        }

        if (! $model) {
            $this->error('Model name is required');

            return -1;
        }

        if (! $force && ! $this->confirm("Delete ALL permissions for model '{$model}'? This includes resource, field, column, action, and relation permissions.")) {
            $this->info('Operation cancelled');

            return 0;
        }

        return $registrar->deleteModelPermissions($model, $guard);
    }

    /**
     * Delete orphaned permissions.
     */
    protected function deleteOrphanedPermissions(PermissionRegistrar $registrar, bool $force): int
    {
        if (! $force && ! $this->confirm('Delete all orphaned permissions (permissions for entities that no longer exist)?')) {
            $this->info('Operation cancelled');

            return 0;
        }

        return $registrar->deleteOrphanedPermissions();
    }

    /**
     * Handle invalid permission type.
     */
    protected function invalidType(string $type): int
    {
        $this->error("Invalid permission type: {$type}");
        $this->info('Valid types: field, column, model, orphaned');

        return -1;
    }
}
