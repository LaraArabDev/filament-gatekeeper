<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use LaraArabDev\FilamentGatekeeper\Models\Role;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->slug(2),
            'guard_name' => 'web',
        ];
    }

    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => config('gatekeeper.super_admin.role', 'super-admin'),
        ]);
    }

    public function forGuard(string $guard): static
    {
        return $this->state(fn (array $attributes) => [
            'guard_name' => $guard,
        ]);
    }

    public function withDescription(string $description): static
    {
        return $this->state(fn (array $attributes) => [
            'description' => $description,
        ]);
    }

    public function withFieldPermissions(array $fieldPermissions): static
    {
        return $this->state(fn (array $attributes) => [
            'field_permissions' => $fieldPermissions,
        ]);
    }
}
