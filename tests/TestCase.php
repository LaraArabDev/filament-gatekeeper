<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use LaraArabDev\FilamentGatekeeper\Database\Factories\PermissionFactory;
use LaraArabDev\FilamentGatekeeper\Models\Permission;
use LaraArabDev\FilamentGatekeeper\Models\Role;
use LaraArabDev\FilamentGatekeeper\GatekeeperServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\Permission\PermissionServiceProvider;
use Spatie\Permission\Traits\HasRoles;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn(string $modelName) => 'LaraArabDev\\FilamentGatekeeper\\Database\\Factories\\' . class_basename($modelName) . 'Factory'
        );

        // Reset permission cache
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function getPackageProviders($app): array
    {
        return [
            PermissionServiceProvider::class,
            GatekeeperServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Set auth provider model
        config()->set('auth.providers.users.model', TestUser::class);

        // Configure auth guards
        config()->set('auth.guards.web', [
            'driver' => 'session',
            'provider' => 'users',
        ]);
        config()->set('auth.guards.api', [
            'driver' => 'token',
            'provider' => 'users',
        ]);

        // Set permission models
        config()->set('permission.models.permission', Permission::class);
        config()->set('permission.models.role', Role::class);

        // Run migrations
        $migration = include __DIR__ . '/../vendor/spatie/laravel-permission/database/migrations/create_permission_tables.php.stub';
        $migration->up();

        // Run package migrations
        $typeMigration = include __DIR__ . '/../database/migrations/add_type_to_permissions_table.php.stub';
        $typeMigration->up();

        $entityMigration = include __DIR__ . '/../database/migrations/add_entity_to_permissions_table.php.stub';
        $entityMigration->up();

        $fieldPermissionsMigration = include __DIR__ . '/../database/migrations/add_field_permissions_to_roles_table.php.stub';
        $fieldPermissionsMigration->up();

        // Add description column to roles table if it doesn't exist
        $schema = $app['db']->connection()->getSchemaBuilder();
        if (! $schema->hasColumn('roles', 'description')) {
            $schema->table('roles', function ($table) {
                $table->text('description')->nullable()->after('guard_name');
            });
        }

        // Create users table for testing
        $app['db']->connection()->getSchemaBuilder()->create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });
    }

    /**
     * Create a test user using factory.
     */
    protected function createUser(array $attributes = [], ?string $guard = null): TestUser
    {
        $user = TestUser::factory()->create($attributes);

        if ($guard) {
            $user->setGuardName($guard);
        }

        return $user;
    }

    /**
     * Create a test user with super admin role.
     */
    protected function createSuperAdmin(array $attributes = []): TestUser
    {
        config()->set('gatekeeper.super_admin.enabled', true);
        config()->set('gatekeeper.super_admin.role', 'super-admin');

        $user = $this->createUser($attributes);

        $role = Role::factory()->superAdmin()->create();

        $user->assignRole($role);

        return $user;
    }

    /**
     * Create a permission using factory.
     * Entity is derived from name when it follows permission naming convention.
     */
    protected function createPermission(string $name, string $type = Permission::TYPE_RESOURCE, string $guard = 'web', ?string $entity = null): Permission
    {
        return Permission::factory()->create([
            'name' => $name,
            'type' => $type,
            'guard_name' => $guard,
            'entity' => $entity ?? PermissionFactory::deriveEntityFromName($name, $type),
        ]);
    }

    /**
     * Create a role using factory.
     */
    protected function createRole(string $name, string $guard = 'web'): Role
    {
        return Role::factory()->create([
            'name' => $name,
            'guard_name' => $guard,
        ]);
    }
}

/**
 * Test user model with HasRoles trait.
 */
class TestUser extends Authenticatable
{
    use HasRoles;
    use HasFactory;

    protected $table = 'users';
    protected $fillable = ['name', 'email', 'password'];
    protected $guard_name = 'web';

    /**
     * Get the guard name for the user.
     */
    public function getGuardName(): string
    {
        return $this->guard_name ?? 'web';
    }

    /**
     * Set the guard name for the user.
     */
    public function setGuardName(string $guard): static
    {
        $this->guard_name = $guard;

        return $this;
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): \LaraArabDev\FilamentGatekeeper\Database\Factories\UserFactory
    {
        return \LaraArabDev\FilamentGatekeeper\Database\Factories\UserFactory::new();
    }
}
