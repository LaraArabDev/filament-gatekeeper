<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Concerns;

use LaraArabDev\FilamentGatekeeper\Facades\Gatekeeper;

/**
 * Trait HasResourcePermissions
 *
 * Add this trait to Laravel API Resources for easy permission checking.
 *
 * Usage Option 1 - With Trait (automatic):
 * ```php
 * class UserResource extends JsonResource
 * {
 *     use HasResourcePermissions;
 *
 *     protected string $shieldModel = 'User';
 *
 *     public function toArray($request): array
 *     {
 *         return [
 *             'id' => $this->id,
 *             'name' => $this->name,
 *             'email' => $this->whenCanView('email', $this->email),
 *             'salary' => $this->whenCanView('salary', $this->salary),
 *             'phone' => $this->whenCanViewColumn('phone', $this->phone),
 *         ];
 *     }
 * }
 * ```
 *
 * Usage Option 2 - Without Trait (manual):
 * ```php
 * class UserResource extends JsonResource
 * {
 *     public function toArray($request): array
 *     {
 *         $shield = Gatekeeper::guard('api');
 *
 *         return [
 *             'id' => $this->id,
 *             'name' => $this->name,
 *             'email' => $this->when(
 *                 $shield->canViewField('User', 'email'),
 *                 $this->email
 *             ),
 *         ];
 *     }
 * }
 * ```
 */
trait HasResourcePermissions
{
    /**
     * Get the model name for permission checks.
     * Override this or set $shieldModel property.
     *
     * @return string
     */
    protected function getGatekeeperModel(): string
    {
        if (property_exists($this, 'shieldModel')) {
            return $this->shieldModel;
        }

        // Try to extract from resource name (UserResource -> User)
        $className = class_basename(static::class);

        return str($className)
            ->replace('Resource', '')
            ->replace('Collection', '')
            ->toString();
    }

    /**
     * Get the guard for permission checks.
     *
     * @return string
     */
    protected function getShieldGuard(): string
    {
        if (property_exists($this, 'shieldGuard')) {
            return $this->shieldGuard;
        }

        return 'api';
    }

    /**
     * Get the Shield instance with configured guard.
     *
     * @return \LaraArabDev\FilamentGatekeeper\Gatekeeper
     */
    protected function shield(): \LaraArabDev\FilamentGatekeeper\Gatekeeper
    {
        return Gatekeeper::guard($this->getShieldGuard());
    }

    /**
     * Include field value only if user can view the field.
     *
     * @param string $field
     * @param mixed $value
     * @param mixed $default
     * @return \Illuminate\Http\Resources\MissingValue|mixed
     */
    protected function whenCanView(string $field, mixed $value, mixed $default = null)
    {
        return $this->when(
            $this->shield()->canViewField($this->getGatekeeperModel(), $field),
            $value,
            $default
        );
    }

    /**
     * Include column value only if user can view the column.
     *
     * @param string $column
     * @param mixed $value
     * @param mixed $default
     * @return \Illuminate\Http\Resources\MissingValue|mixed
     */
    protected function whenCanViewColumn(string $column, mixed $value, mixed $default = null)
    {
        return $this->when(
            $this->shield()->canViewColumn($this->getGatekeeperModel(), $column),
            $value,
            $default
        );
    }

    /**
     * Include relation only if user has permission.
     *
     * @param string $relation
     * @param mixed $value
     * @return \Illuminate\Http\Resources\MissingValue|mixed
     */
    protected function whenCanViewRelation(string $relation, mixed $value)
    {
        return $this->when(
            $this->shield()->canViewRelation($this->getGatekeeperModel(), $relation),
            $value
        );
    }

    /**
     * Include nested resource only if user can view the relation.
     *
     * @param string $relation
     * @param string $resourceClass
     * @return \Illuminate\Http\Resources\MissingValue|mixed
     */
    protected function whenCanLoadRelation(string $relation, string $resourceClass)
    {
        if (! $this->shield()->canViewRelation($this->getGatekeeperModel(), $relation)) {
            return $this->when(false, null);
        }

        return $this->whenLoaded($relation, function () use ($relation, $resourceClass) {
            $relationData = $this->resource->{$relation};

            if (is_iterable($relationData)) {
                return $resourceClass::collection($relationData);
            }

            return new $resourceClass($relationData);
        });
    }

    /**
     * Filter an array to only include permitted fields.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function filterByFieldPermissions(array $data): array
    {
        $visibleFields = $this->shield()->getVisibleFields($this->getGatekeeperModel());

        // If no specific field permissions defined, return all
        if (empty($visibleFields)) {
            return $data;
        }

        return array_intersect_key($data, array_flip($visibleFields));
    }

    /**
     * Get all model attributes filtered by permissions.
     *
     * @param array<string> $except Fields to exclude regardless of permissions
     * @return array<string, mixed>
     */
    protected function permittedAttributes(array $except = []): array
    {
        $attributes = $this->resource->attributesToArray();

        // Remove excepted fields
        $attributes = array_diff_key($attributes, array_flip($except));

        return $this->filterByFieldPermissions($attributes);
    }

    /**
     * Check if user can view a specific field.
     *
     * @param string $field
     * @return bool
     */
    protected function canViewField(string $field): bool
    {
        return $this->shield()->canViewField($this->getGatekeeperModel(), $field);
    }

    /**
     * Check if user can view a specific column.
     *
     * @param string $column
     * @return bool
     */
    protected function canViewColumn(string $column): bool
    {
        return $this->shield()->canViewColumn($this->getGatekeeperModel(), $column);
    }

    /**
     * Check if user can view a specific relation.
     *
     * @param string $relation
     * @return bool
     */
    protected function canViewRelation(string $relation): bool
    {
        return $this->shield()->canViewRelation($this->getGatekeeperModel(), $relation);
    }
}
