<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use LaraArabDev\FilamentGatekeeper\Models\Permission;

/**
 * @extends Factory<Permission>
 */
class PermissionFactory extends Factory
{
    protected $model = Permission::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->slug(3),
            'guard_name' => 'web',
            'type' => Permission::TYPE_RESOURCE,
            'entity' => null,
        ];
    }

    /**
     * Derive entity (snake_case) from permission name and type.
     * For field/column/relation the name format is {action}_{entity}_{type}; we use first segment as model.
     */
    public static function deriveEntityFromName(string $name, string $type): ?string
    {
        $parts = explode('_', $name);
        if (count($parts) < 2) {
            return null;
        }

        // New format: action_entity_type (e.g. view_user_email_field) — entity is middle, model is first segment
        if (in_array($type, [Permission::TYPE_FIELD, Permission::TYPE_RELATION, Permission::TYPE_COLUMN, Permission::TYPE_ACTION], true)) {
            $between = array_slice($parts, 1, -1);

            return $between !== [] ? ($between[0] ?? null) : null;
        }

        $skipWords = ['view', 'create', 'update', 'delete', 'restore', 'force', 'replicate', 'reorder', 'any', 'page', 'widget', 'execute'];
        $modelParts = [];
        foreach ($parts as $part) {
            if (in_array($part, $skipWords)) {
                continue;
            }
            $modelParts[] = $part;
        }
        if (empty($modelParts)) {
            $modelParts = [end($parts)];
        }
        return implode('_', array_map('strtolower', $modelParts));
    }

    /**
     * Configure the model factory to set entity when name/type are present and entity not explicitly set.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Permission $permission): void {
            if (($permission->entity === null || $permission->entity === '') && $permission->name && $permission->type) {
                $derived = self::deriveEntityFromName($permission->name, $permission->type);
                if ($derived !== null) {
                    $permission->entity = $derived;
                }
            }
        });
    }

    public function resource(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => Permission::TYPE_RESOURCE,
        ]);
    }

    public function page(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => Permission::TYPE_PAGE,
        ]);
    }

    public function widget(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => Permission::TYPE_WIDGET,
        ]);
    }

    public function field(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => Permission::TYPE_FIELD,
        ]);
    }

    public function column(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => Permission::TYPE_COLUMN,
        ]);
    }

    public function action(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => Permission::TYPE_ACTION,
        ]);
    }

    public function relation(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => Permission::TYPE_RELATION,
        ]);
    }

    public function model(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => Permission::TYPE_MODEL,
        ]);
    }

    public function forGuard(string $guard): static
    {
        return $this->state(fn(array $attributes) => [
            'guard_name' => $guard,
        ]);
    }

    /**
     * Set the entity (model/resource key) for the permission.
     */
    public function forEntity(string $entity): static
    {
        return $this->state(fn(array $attributes) => [
            'entity' => str($entity)->snake()->toString(),
        ]);
    }

    public function forModel(string $model): static
    {
        return $this->forEntity($model);
    }
}
